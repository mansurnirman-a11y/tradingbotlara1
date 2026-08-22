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

            // 3. Initialize Strategy
            $strategyClass = $this->bot->strategy_class;
            if (!class_exists($strategyClass)) {
                throw new Exception("Strategy class {$strategyClass} not found.");
            }
            $strategy = new $strategyClass();

            $currentPrice = $candles[count($candles)-1][4];

            // 3.5 Fetch Open Position & Sync
            $openPosition = Position::where('bot_instance_id', $this->bot->id)
                                    ->where('status', 'OPEN')
                                    ->first();

            // Position Sync System
            if ($openPosition) {
                try {
                    $exchangePositions = [];
                    // Handle CCXT vs MetaApi method names
                    if (is_callable([$exchangeService->getClient(), 'fetch_positions'])) {
                        $exchangePositions = $exchangeService->getClient()->fetch_positions();
                    } elseif (is_callable([$exchangeService->getClient(), 'fetchPositions'])) {
                        $exchangePositions = $exchangeService->getClient()->fetchPositions();
                    }
                    
                    $isActive = false;
                    
                    foreach ($exchangePositions as $ep) {
                        $epSymbol = $ep['symbol'] ?? $ep['product_symbol'] ?? $ep['info']['product_symbol'] ?? '';
                        if (str_replace(['/', '-', ':'], '', $epSymbol) === str_replace(['/', '-', ':'], '', $this->bot->symbol)) {
                            $contracts = floatval($ep['contracts'] ?? $ep['size'] ?? 0);
                            $exchangeSide = $contracts > 0 ? 'LONG' : ($contracts < 0 ? 'SHORT' : '');
                            
                            if (abs($contracts) > 0 && $exchangeSide === $openPosition->side) {
                                $isActive = true;
                                
                                // UPDATE LOCAL POSITION WITH EXCHANGE DATA
                                $actualEntryPrice = $ep['entryPrice'] ?? $ep['entry_price'] ?? $ep['info']['entry_price'] ?? null;
                                if ($actualEntryPrice && $actualEntryPrice != $openPosition->entry_price) {
                                    $openPosition->update([
                                        'entry_price' => $actualEntryPrice
                                    ]);
                                }
                                
                                break;
                            }
                        }
                    }

                    // If not found active on exchange, it was closed manually/externally
                    if (!$isActive) {
                        $openPosition->update([
                            'status' => 'CLOSED',
                            'closed_at' => now(),
                            'exit_price' => $currentPrice, // Fallback exit price
                        ]);
                        $openPosition = null; // Clear local reference
                    }
                } catch (\Exception $syncError) {
                    // If API fails, assume it's still open to prevent duplicate orders
                }
            }

            // 4. Evaluate Strategy Signal
            $signal = $strategy->evaluate($candles, $this->bot->parameters ?? []);

            // 4.5 Evaluate Risk Management (Stop Loss / Take Profit)
            if ($openPosition) {
                $slPct = $this->bot->parameters['stop_loss_pct'] ?? 1.5; // Default 1.5% Stop Loss
                $tpPct = $this->bot->parameters['take_profit_pct'] ?? 3.0; // Default 3.0% Take Profit

                $entryPrice = $openPosition->entry_price;
                $pnlPct = 0;

                if ($openPosition->side === 'LONG') {
                    $pnlPct = (($currentPrice - $entryPrice) / $entryPrice) * 100;
                } else {
                    $pnlPct = (($entryPrice - $currentPrice) / $entryPrice) * 100;
                }

                // Override signal to force close if SL/TP hit
                if ($pnlPct <= -$slPct) {
                    $signal = ($openPosition->side === 'LONG') ? 'SELL' : 'BUY';
                    \Log::info("Bot {$this->bot->id}: Stop Loss hit ({$pnlPct}%). Forcing {$signal} signal.");
                } elseif ($pnlPct >= $tpPct) {
                    $signal = ($openPosition->side === 'LONG') ? 'SELL' : 'BUY';
                    \Log::info("Bot {$this->bot->id}: Take Profit hit ({$pnlPct}%). Forcing {$signal} signal.");
                }
            }

            // 5. Execute Trade & Manage Positions
            if ($signal === 'BUY' || $signal === 'SELL') {
                $currentPrice = $candles[count($candles)-1][4];
                $rawQuantity = $this->bot->allocated_capital / $currentPrice;
                
                // Format quantity to exchange precision rules
                $quantity = $exchangeService->formatAmount($this->bot->symbol, $rawQuantity);

                if (floatval($quantity) <= 0) {
                    $market = $exchangeService->getMarketInfo($this->bot->symbol);
                    if ($market && isset($market['precision']['amount']) && $market['precision']['amount'] == 1) {
                        // Contract-based market (e.g. Delta BTC/USD where 1 contract = 1 USD)
                        // In this case, amount is the number of contracts. We use allocated capital as the number of contracts.
                        $quantity = $exchangeService->formatAmount($this->bot->symbol, $this->bot->allocated_capital);
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
                $execPrice = $order['average'] ?? $order['price'] ?? null;
                if (!$execPrice || floatval($execPrice) == 0) {
                    try {
                        // Sleep for a moment to allow exchange to process the fill
                        sleep(1);
                        $fetchedOrder = $exchangeService->getClient()->fetchOrder($order['id'], $this->bot->symbol);
                        $execPrice = $fetchedOrder['average'] ?? $fetchedOrder['price'] ?? $currentPrice;
                    } catch (\Exception $e) {
                        $execPrice = $currentPrice;
                    }
                }

                // Fetch contract size to correctly calculate USD Volume and PNL
                $contractSize = $exchangeService->getContractSize($this->bot->symbol);
                $volumeUsd = $execPrice * ($quantity * $contractSize);

                // Record Trade Action
                $tradeModel = Trade::create([
                    'bot_instance_id' => $this->bot->id,
                    'user_id' => $this->bot->user_id,
                    'order_id' => $order['id'],
                    'symbol' => $this->bot->symbol,
                    'side' => $signal,
                    'type' => 'MARKET',
                    'price' => $execPrice,
                    'quantity' => $quantity,
                    'volume_usd' => $volumeUsd,
                    'status' => 'FILLED',
                    'executed_at' => now(),
                ]);

                // Send Telegram & Email Notification
                $this->bot->user->notify(new \App\Notifications\TradeExecuted($tradeModel));

                // (Position is fetched earlier in the job)
                
                
                if ($signal === 'BUY') {
                    if ($openPosition && $openPosition->side === 'SHORT') {
                        // Close Short Position
                        $openPosition->update([
                            'exit_price' => $execPrice,
                            'status' => 'CLOSED',
                            'closed_at' => now(),
                            'realized_pnl' => ($openPosition->entry_price - $execPrice) * ($openPosition->quantity * $contractSize),
                        ]);
                    } elseif (!$openPosition) {
                        // Open Long Position
                        Position::create([
                            'bot_instance_id' => $this->bot->id,
                            'user_id' => $this->bot->user_id,
                            'symbol' => $this->bot->symbol,
                            'side' => 'LONG',
                            'quantity' => $quantity,
                            'entry_price' => $execPrice,
                            'status' => 'OPEN',
                            'opened_at' => now(),
                        ]);
                    }
                } elseif ($signal === 'SELL') {
                    if ($openPosition && $openPosition->side === 'LONG') {
                        // Close Long Position
                        $openPosition->update([
                            'exit_price' => $execPrice,
                            'status' => 'CLOSED',
                            'closed_at' => now(),
                            'realized_pnl' => ($execPrice - $openPosition->entry_price) * ($openPosition->quantity * $contractSize),
                        ]);
                    } elseif (!$openPosition) {
                        // Open Short Position
                        Position::create([
                            'bot_instance_id' => $this->bot->id,
                            'user_id' => $this->bot->user_id,
                            'symbol' => $this->bot->symbol,
                            'side' => 'SHORT',
                            'quantity' => $quantity,
                            'entry_price' => $execPrice,
                            'status' => 'OPEN',
                            'opened_at' => now(),
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            \Log::error("Bot {$this->bot->id} failed: " . $errorMessage);
            
            // Send the new automated error email to the user
            $this->bot->user->notify(new \App\Notifications\BotErrorNotification($this->bot, $errorMessage));
        }
    }
}
