<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BotInstance;
use App\Models\Trade;
use App\Models\Position;
use App\Services\ExchangeService;
use Exception;

class EvaluateStrategyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bot;

    public function __construct(BotInstance $bot)
    {
        $this->bot = $bot;
    }

    public function handle(): void
    {
        try {
            // 1. Boot up exchange
            $exchangeService = new ExchangeService($this->bot->brokerAccount);
            
            // 2. Fetch Market Data
            $candles = $exchangeService->fetchOHLCV($this->bot->symbol, $this->bot->timeframe, 100);

            // Skip webhook-driven bots from candle cron evaluation
            if (
                $this->bot->strategy_class === 'Webhook' ||
                ($this->bot->strategy && $this->bot->strategy->type === 'webhook')
            ) {
                return;
            }

            // 3. Initialize Strategy
            $strategyClass = $this->bot->strategy_class ?: ($this->bot->strategy ? $this->bot->strategy->class_name : null);

            // Handle Aliases and Namespaces
            $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $strategyClass ?? ''));
            if (str_contains($normalized, 'sessionsweep') || str_contains($normalized, 'fvg') || str_contains($normalized, 'ict')) {
                $strategyClass = \App\Strategies\SessionSweepFvgStrategy::class;
            } elseif (str_contains($normalized, 'supertrend')) {
                $strategyClass = \App\Strategies\SupertrendStrategy::class;
            } elseif (str_contains($normalized, 'emacrossover')) {
                $strategyClass = \App\Strategies\EmaCrossoverStrategy::class;
            } elseif (str_contains($normalized, 'rsireversal') || str_contains($normalized, 'rsistrategy') || $normalized === 'rsi') {
                $strategyClass = \App\Strategies\RsiStrategy::class;
            } elseif (str_contains($normalized, 'macdmomentum') || str_contains($normalized, 'macdstrategy') || $normalized === 'macd') {
                $strategyClass = \App\Strategies\MacdStrategy::class;
            } elseif (str_contains($normalized, 'smatrend') || str_contains($normalized, 'smacrossoverstrategy') || $normalized === 'sma') {
                $strategyClass = \App\Strategies\SmaCrossoverStrategy::class;
            } elseif (str_contains($normalized, 'bollinger')) {
                $strategyClass = \App\Strategies\BollingerScalpingStrategy::class;
            } elseif (str_contains($normalized, 'emareversal') || str_contains($normalized, 'reversalbreakout')) {
                $strategyClass = \App\Strategies\EmaReversalBreakoutStrategy::class;
            }

            if (!$strategyClass || !class_exists($strategyClass)) {
                $cleanName = class_basename($strategyClass ?? '');
                $namespaced = 'App\\Strategies\\' . $cleanName;
                if (class_exists($namespaced)) {
                    $strategyClass = $namespaced;
                } else {
                    throw new Exception("Strategy class {$strategyClass} not found.");
                }
            }

            $strategy = new $strategyClass();

            $currentPrice = $candles[count($candles)-1][4];

            // 3.5 Fetch Open Position & Sync Ground Truth from Broker
            $openPosition = Position::where('bot_instance_id', $this->bot->id)
                                    ->where('status', 'OPEN')
                                    ->first();

            // Position Sync System
            if ($openPosition) {
                try {
                    $exchangePositions = $exchangeService->getOpenPositions();
                    $isActive = false;

                    if (!empty($exchangePositions)) {
                        foreach ($exchangePositions as $ep) {
                            if (ExchangeService::symbolsMatch($ep['symbol'], $this->bot->symbol)) {
                                $epSide = strtoupper($ep['side'] ?? '');
                                $contracts = floatval($ep['contracts'] ?? $ep['quantity'] ?? 0);

                                if ($contracts > 0 && ($epSide === $openPosition->side || empty($epSide))) {
                                    $isActive = true;

                                    // UPDATE LOCAL POSITION WITH EXACT BROKER DATA (Entry Price AND Quantity)
                                    $updates = [];
                                    $actualEntryPrice = floatval($ep['entry_price'] ?? $ep['entryPrice'] ?? 0);
                                    if ($actualEntryPrice > 0 && abs($actualEntryPrice - floatval($openPosition->entry_price)) > 0.0001) {
                                        $updates['entry_price'] = $actualEntryPrice;
                                    }

                                    if ($contracts > 0 && abs($contracts - floatval($openPosition->quantity)) > 0.000001) {
                                        $updates['quantity'] = $contracts;
                                    }

                                    if (!empty($updates)) {
                                        $openPosition->update($updates);
                                    }

                                    break;
                                }
                            }
                        }
                    }
                } catch (\Throwable $syncError) {
                    \Log::warning("Bot {$this->bot->id} position sync notice: " . $syncError->getMessage());
                }
            }

            // 4. Evaluate Strategy Signal (supports string 'BUY'/'SELL' or array with candle SL/TP)
            $evalResult = $strategy->evaluate($candles, $this->bot->parameters ?? []);
            $signal = 'HOLD';
            $stratSL = null;
            $stratTP = null;

            if (is_array($evalResult)) {
                $signal = $evalResult['signal'] ?? 'HOLD';
                $stratSL = isset($evalResult['stop_loss']) ? floatval($evalResult['stop_loss']) : null;
                $stratTP = isset($evalResult['take_profit']) ? floatval($evalResult['take_profit']) : null;
            } else {
                $signal = (string)$evalResult;
            }

            // 4.5 Evaluate Risk Management (Stop Loss / Take Profit / Smart Trailing SL)
            if ($openPosition) {
                $slPct = floatval($this->bot->parameters['stop_loss_pct'] ?? 1.5); // Default 1.5% Stop Loss
                $tpPct = floatval($this->bot->parameters['take_profit_pct'] ?? 3.0); // Default 3.0% Take Profit
                $enableTrailing = $this->bot->parameters['trailing_sl'] ?? true; // Smart Trailing enabled by default
                $trailTriggerPct = floatval($this->bot->parameters['trail_trigger_pct'] ?? 0.8); // Kicks in at +0.8% profit
                $trailDistancePct = floatval($this->bot->parameters['trail_distance_pct'] ?? 0.5); // Trails 0.5% behind peak

                // Point-based Trailing (e.g. 50 points on BTC Reversal Breakout)
                $isEmaReversal = ($strategyClass === \App\Strategies\EmaReversalBreakoutStrategy::class);
                $trailPoints = floatval($this->bot->parameters['trail_points'] ?? ($isEmaReversal ? 50.0 : 0));
                $usePointTrailing = $trailPoints > 0;
                $tpPoints = floatval($this->bot->parameters['take_profit_points'] ?? ($isEmaReversal ? 400.0 : 0));

                $entryPrice = floatval($openPosition->entry_price);
                $pnlPct = 0;

                if ($openPosition->side === 'LONG') {
                    $pnlPct = (($currentPrice - $entryPrice) / $entryPrice) * 100;
                    $currentProfitPoints = $currentPrice - $entryPrice;

                    // 1. High-water mark (peak price) tracking
                    $peakPrice = floatval($openPosition->peak_price ?: $entryPrice);
                    if ($currentPrice > $peakPrice) {
                        $peakPrice = $currentPrice;
                        $openPosition->peak_price = $peakPrice;
                    }
                    $peakProfitPct = (($peakPrice - $entryPrice) / $entryPrice) * 100;
                    $peakProfitPoints = $peakPrice - $entryPrice;

                    // 2. Base initial Stop Loss Price (only if slPct > 0)
                    $initialSlPrice = $slPct > 0 ? ($entryPrice * (1 - ($slPct / 100))) : 0;
                    $currentTrailingSl = floatval($openPosition->trailing_sl ?: $initialSlPrice);

                    // 3. Dynamic Trailing SL Calculation
                    if ($enableTrailing) {
                        if ($usePointTrailing) {
                            // 50-Point Step Trailing: Hold initial candle SL until +50 points profit.
                            // Once peak reaches >= 50 points, trail 50 points behind peak (Cost to Cost at 50, then +1 pt per +1 pt).
                            if ($peakProfitPoints >= $trailPoints) {
                                $breakEvenPrice = $entryPrice; // Break-even floor
                                $calculatedTrail = $peakPrice - $trailPoints;
                                $newTrailingSl = max($breakEvenPrice, $calculatedTrail, $currentTrailingSl);

                                if ($newTrailingSl > $currentTrailingSl) {
                                    $currentTrailingSl = $newTrailingSl;
                                    $openPosition->trailing_sl = $currentTrailingSl;
                                    \Log::info("Bot {$this->bot->id} (LONG): 50-Pt Trailing SL tightened UP to \${$currentTrailingSl} (Peak profit: \${$peakProfitPoints} pts).");
                                }
                            }
                        } elseif ($peakProfitPct >= $trailTriggerPct) {
                            // Percentage-based Trailing SL fallback for other strategies
                            $breakEvenPrice = $entryPrice * 1.0005;
                            $calculatedTrail = $peakPrice * (1 - ($trailDistancePct / 100));
                            $newTrailingSl = max($breakEvenPrice, $calculatedTrail, $currentTrailingSl);

                            if ($newTrailingSl > $currentTrailingSl) {
                                $currentTrailingSl = $newTrailingSl;
                                $openPosition->trailing_sl = $currentTrailingSl;
                                \Log::info("Bot {$this->bot->id} (LONG): Trailing SL tightened UP to \${$currentTrailingSl} (Peak profit: " . round($peakProfitPct, 2) . "%).");
                            }
                        }
                    } else {
                        if (!$openPosition->trailing_sl) {
                            $openPosition->trailing_sl = $initialSlPrice;
                        }
                    }

                    $openPosition->save();

                    // 4. Check Exit Conditions
                    $isTpHit = ($tpPct > 0 && $pnlPct >= $tpPct) || ($tpPoints > 0 && $currentProfitPoints >= $tpPoints);
                    if ($currentTrailingSl > 0 && $currentPrice <= $currentTrailingSl) {
                        $signal = 'SELL';
                        $trailedGain = (($currentTrailingSl - $entryPrice) / $entryPrice) * 100;
                        \Log::info("Bot {$this->bot->id} (LONG): Stop Loss / Trailing SL hit at \${$currentPrice} (SL was \${$currentTrailingSl}, Gain: " . round($trailedGain, 2) . "%).");
                    } elseif ($isTpHit) {
                        $signal = 'SELL';
                        \Log::info("Bot {$this->bot->id} (LONG): Take Profit hit ({$pnlPct}% / {$currentProfitPoints} pts). Forcing SELL signal.");
                    }
                } else {
                    // SHORT Position
                    $pnlPct = (($entryPrice - $currentPrice) / $entryPrice) * 100;
                    $currentProfitPoints = $entryPrice - $currentPrice;

                    // 1. Low-water mark (peak dip price) tracking
                    $peakPrice = floatval($openPosition->peak_price ?: $entryPrice);
                    if ($peakPrice <= 0 || $currentPrice < $peakPrice) {
                        $peakPrice = $currentPrice;
                        $openPosition->peak_price = $peakPrice;
                    }
                    $peakProfitPct = (($entryPrice - $peakPrice) / $entryPrice) * 100;
                    $peakProfitPoints = $entryPrice - $peakPrice;

                    // 2. Base initial Stop Loss Price for SHORT (only if slPct > 0)
                    $initialSlPrice = $slPct > 0 ? ($entryPrice * (1 + ($slPct / 100))) : 0;
                    $currentTrailingSl = floatval($openPosition->trailing_sl ?: $initialSlPrice);

                    // 3. Dynamic Trailing SL Calculation
                    if ($enableTrailing) {
                        if ($usePointTrailing) {
                            // 50-Point Step Trailing for SHORT: Hold initial candle SL until +50 points profit (dip).
                            // Once peak dip reaches >= 50 points, trail 50 points above peak low (Cost to Cost at 50, then +1 pt per +1 pt).
                            if ($peakProfitPoints >= $trailPoints) {
                                $breakEvenPrice = $entryPrice; // Break-even ceiling
                                $calculatedTrail = $peakPrice + $trailPoints;
                                $newTrailingSl = min($breakEvenPrice, $calculatedTrail, $currentTrailingSl);

                                if ($newTrailingSl < $currentTrailingSl) {
                                    $currentTrailingSl = $newTrailingSl;
                                    $openPosition->trailing_sl = $currentTrailingSl;
                                    \Log::info("Bot {$this->bot->id} (SHORT): 50-Pt Trailing SL tightened DOWN to \${$currentTrailingSl} (Peak profit: \${$peakProfitPoints} pts).");
                                }
                            }
                        } elseif ($peakProfitPct >= $trailTriggerPct) {
                            // Percentage-based Trailing SL fallback for other strategies
                            $breakEvenPrice = $entryPrice * 0.9995;
                            $calculatedTrail = $peakPrice * (1 + ($trailDistancePct / 100));
                            $newTrailingSl = min($breakEvenPrice, $calculatedTrail, $currentTrailingSl);

                            if ($newTrailingSl < $currentTrailingSl) {
                                $currentTrailingSl = $newTrailingSl;
                                $openPosition->trailing_sl = $currentTrailingSl;
                                \Log::info("Bot {$this->bot->id} (SHORT): Trailing SL tightened DOWN to \${$currentTrailingSl} (Peak profit: " . round($peakProfitPct, 2) . "%).");
                            }
                        }
                    } else {
                        if (!$openPosition->trailing_sl) {
                            $openPosition->trailing_sl = $initialSlPrice;
                        }
                    }

                    $openPosition->save();

                    // 4. Check Exit Conditions
                    $isTpHit = ($tpPct > 0 && $pnlPct >= $tpPct) || ($tpPoints > 0 && $currentProfitPoints >= $tpPoints);
                    if ($currentTrailingSl > 0 && $currentPrice >= $currentTrailingSl) {
                        $signal = 'BUY';
                        $trailedGain = (($entryPrice - $currentTrailingSl) / $entryPrice) * 100;
                        \Log::info("Bot {$this->bot->id} (SHORT): Stop Loss / Trailing SL hit at \${$currentPrice} (SL was \${$currentTrailingSl}, Gain: " . round($trailedGain, 2) . "%).");
                    } elseif ($isTpHit) {
                        $signal = 'BUY';
                        \Log::info("Bot {$this->bot->id} (SHORT): Take Profit hit ({$pnlPct}% / {$currentProfitPoints} pts). Forcing BUY signal.");
                    }
                }
            }

            // 5. Execute Trade & Manage Positions
            if ($signal === 'BUY' || $signal === 'SELL') {
                // If we already hold an open position in the same direction, maintain position without duplicate orders
                if ($signal === 'BUY' && $openPosition && $openPosition->side === 'LONG') {
                    return;
                }
                if ($signal === 'SELL' && $openPosition && $openPosition->side === 'SHORT') {
                    return;
                }

                // Check minimum capital threshold
                if (floatval($this->bot->allocated_capital) < 10) {
                    $this->bot->update(['status' => 'stopped']);
                    throw new Exception("Bot stopped: Allocated capital ({$this->bot->allocated_capital}) is below minimum $10 threshold.");
                }

                $contractSize = $exchangeService->getContractSize($this->bot->symbol) ?: 1.0;

                // ----------------------------------------------------
                // CASE A: CLOSING EXISTING OPPOSING POSITION
                // ----------------------------------------------------
                $isClosingShort = ($signal === 'BUY' && $openPosition && $openPosition->side === 'SHORT');
                $isClosingLong = ($signal === 'SELL' && $openPosition && $openPosition->side === 'LONG');

                if ($isClosingShort || $isClosingLong) {
                    // Send exact existing open position quantity to broker to ensure clean 0 balance on broker
                    $closeQuantity = $openPosition->quantity;
                    $order = $exchangeService->closePosition(
                        $this->bot->symbol,
                        $closeQuantity,
                        $openPosition->side
                    );

                    $execPrice = floatval($order['average'] ?? $order['price'] ?? $order['averagePrice'] ?? 0);
                    if ($execPrice <= 0 || (abs($execPrice - $currentPrice) / $currentPrice > 0.2)) {
                        $execPrice = $currentPrice;
                    }

                    $realizedPnl = $isClosingShort
                        ? ($openPosition->entry_price - $execPrice) * ($openPosition->quantity * $contractSize)
                        : ($execPrice - $openPosition->entry_price) * ($openPosition->quantity * $contractSize);

                    $openPosition->update([
                        'exit_price' => $execPrice,
                        'status' => 'CLOSED',
                        'closed_at' => now(),
                        'realized_pnl' => $realizedPnl,
                    ]);

                    // Dynamic Compounding Capital Update
                    $newCapital = max(0, round(floatval($this->bot->allocated_capital) + $realizedPnl, 4));
                    $this->bot->update(['allocated_capital' => $newCapital]);
                    $this->bot->allocated_capital = $newCapital;

                    $orderId = $order['id'] ?? $order['order_id'] ?? ('ORD-' . uniqid());
                    $tradeModel = Trade::create([
                        'bot_instance_id' => $this->bot->id,
                        'user_id' => $this->bot->user_id,
                        'order_id' => $orderId,
                        'symbol' => $this->bot->symbol,
                        'side' => $signal,
                        'type' => 'MARKET',
                        'price' => $execPrice,
                        'quantity' => $closeQuantity,
                        'volume_usd' => $execPrice * ($closeQuantity * $contractSize),
                        'status' => 'FILLED',
                        'realized_pnl' => $realizedPnl,
                        'executed_at' => now(),
                    ]);

                    if ($this->bot->user) {
                        try {
                            $this->bot->user->notify(new \App\Notifications\TradeExecuted($tradeModel));
                        } catch (\Throwable $notifErr) {}
                    }

                    return;
                }

                // ----------------------------------------------------
                // CASE B: OPENING NEW POSITION
                // ----------------------------------------------------
                $customLeverage = isset($this->bot->parameters['leverage']) ? floatval($this->bot->parameters['leverage']) : null;
                $leverage = $exchangeService->getLeverage($this->bot->symbol, $customLeverage);
                $positionValue = $this->bot->allocated_capital * $leverage;

                // Divide by contract size for Forex lots / derivative contracts
                $rawQuantity = ($positionValue / $currentPrice) / $contractSize;
                
                // Format quantity to exchange precision rules
                $quantity = $exchangeService->formatAmount($this->bot->symbol, $rawQuantity);

                if (floatval($quantity) <= 0) {
                    $market = $exchangeService->getMarketInfo($this->bot->symbol);
                    if ($market && isset($market['precision']['amount']) && $market['precision']['amount'] == 1) {
                        $quantity = $exchangeService->formatAmount($this->bot->symbol, $positionValue);
                    } else {
                        throw new Exception("Allocated capital ({$this->bot->allocated_capital}) is too small. Calculated quantity rounded to 0.");
                    }
                }

                $order = $exchangeService->createMarketOrder(
                    $this->bot->symbol,
                    strtolower($signal),
                    $quantity
                );

                // Fetch true execution price (average fill price)
                $execPrice = floatval($order['average'] ?? $order['price'] ?? $order['averagePrice'] ?? 0);
                if ($execPrice <= 0 || (abs($execPrice - $currentPrice) / $currentPrice > 0.2)) {
                    $execPrice = $currentPrice;
                }

                // Actual executed quantity from broker response (Ground Truth)
                $actualFilled = floatval($order['filled'] ?? $order['amount'] ?? $order['contracts'] ?? $order['quantity'] ?? $quantity);
                if ($actualFilled <= 0) {
                    $actualFilled = $quantity;
                }

                $volumeUsd = $execPrice * ($actualFilled * $contractSize);

                // Record Trade Action
                $orderId = $order['id'] ?? $order['order_id'] ?? ('ORD-' . uniqid());
                $tradeModel = Trade::create([
                    'bot_instance_id' => $this->bot->id,
                    'user_id' => $this->bot->user_id,
                    'order_id' => $orderId,
                    'symbol' => $this->bot->symbol,
                    'side' => $signal,
                    'type' => 'MARKET',
                    'price' => $execPrice,
                    'quantity' => $actualFilled,
                    'volume_usd' => $volumeUsd,
                    'status' => 'FILLED',
                    'executed_at' => now(),
                ]);

                // Determine initial SL: Prioritize Strategy's candle High/Low SL, fallback to % (if configured > 0)
                $initialSl = null;
                if ($stratSL && $stratSL > 0) {
                    $initialSl = $stratSL;
                } else {
                    $slPct = floatval($this->bot->parameters['stop_loss_pct'] ?? 1.5);
                    if ($slPct > 0) {
                        $initialSl = $signal === 'BUY'
                            ? $execPrice * (1 - ($slPct / 100))
                            : $execPrice * (1 + ($slPct / 100));
                    }
                }

                // Create Open Position with exact broker executed lot size, price, and candle-based SL
                Position::create([
                    'bot_instance_id' => $this->bot->id,
                    'user_id' => $this->bot->user_id,
                    'symbol' => $this->bot->symbol,
                    'side' => $signal === 'BUY' ? 'LONG' : 'SHORT',
                    'quantity' => $actualFilled,
                    'entry_price' => $execPrice,
                    'peak_price' => $execPrice,
                    'trailing_sl' => $initialSl,
                    'status' => 'OPEN',
                    'opened_at' => now(),
                ]);

                // Send Telegram & Email Notification
                if ($this->bot->user) {
                    try {
                        $this->bot->user->notify(new \App\Notifications\TradeExecuted($tradeModel));
                    } catch (\Throwable $notifErr) {
                        \Log::warning("Notification failed for bot trade: " . $notifErr->getMessage());
                    }
                }
            }

        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            \Log::error("Bot {$this->bot->id} failed: " . $errorMessage);
            
            // Send the automated error notification to the user
            if (isset($this->bot->user)) {
                try {
                    $this->bot->user->notify(new \App\Notifications\BotErrorNotification($this->bot, $errorMessage));
                } catch (\Throwable $notifErr) {
                    // Suppress secondary notification error
                }
            }
        }
    }
}
