<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Strategy;
use App\Models\BotInstance;
use App\Services\ExchangeService;
use App\Models\Position;

class WebhookController extends Controller
{
    public function handleTradingView(Request $request, $key)
    {
        $strategy = Strategy::where('webhook_key', $key)->where('is_active', true)->first();

        if (!$strategy) {
            return response()->json(['error' => 'Invalid or inactive webhook key'], 404);
        }

        // Expected payload from TradingView:
        // { "action": "BUY" | "SELL" | "CLOSE" }
        $action = $request->input('action');
        
        // Fallback for raw text/plain JSON payload from TradingView
        if (!$action && $request->getContent()) {
            $payload = json_decode($request->getContent(), true);
            $action = $payload['action'] ?? null;
        }

        $action = strtoupper($action ?? '');
        
        if (!in_array($action, ['BUY', 'SELL', 'CLOSE'])) {
            return response()->json(['error' => 'Invalid action in payload'], 400);
        }

        $bots = BotInstance::where('strategy_id', $strategy->id)
                           ->where('status', 'running')
                           ->with('brokerAccount')
                           ->get();
        
        $results = [];

        foreach ($bots as $bot) {
            try {
                if (!$bot->brokerAccount || !$bot->brokerAccount->is_active) {
                    continue;
                }

                $exchange = new ExchangeService($bot->brokerAccount);
                $ticker = $exchange->fetchTicker($bot->symbol);
                if (!$ticker) continue;

                $currentPrice = floatval($ticker);
                $contractSize = $exchange->getContractSize($bot->symbol) ?: 1.0;

                // Close existing position if needed
                $activePosition = Position::where('bot_instance_id', $bot->id)
                                          ->where('status', 'OPEN')
                                          ->first();

                if ($activePosition) {
                    if ($action === 'CLOSE' || 
                       ($action === 'BUY' && $activePosition->side === 'SHORT') || 
                       ($action === 'SELL' && $activePosition->side === 'LONG')) {
                           
                        $exchange->closePosition($bot->symbol, $activePosition->quantity, $activePosition->side);
                        $realizedPnl = $activePosition->side === 'LONG' 
                            ? ($currentPrice - $activePosition->entry_price) * ($activePosition->quantity * $contractSize)
                            : ($activePosition->entry_price - $currentPrice) * ($activePosition->quantity * $contractSize);

                        $activePosition->update([
                            'status' => 'CLOSED',
                            'exit_price' => $currentPrice,
                            'closed_at' => now(),
                            'realized_pnl' => $realizedPnl,
                        ]);

                        // Dynamic Capital Update (Compounding)
                        $newCapital = max(0, round(floatval($bot->allocated_capital) + $realizedPnl, 4));
                        $bot->update(['allocated_capital' => $newCapital]);
                        $bot->allocated_capital = $newCapital;
                        
                        $results[$bot->id][] = 'Position Closed. Updated Capital: $' . number_format($newCapital, 2);
                        $activePosition = null; // Position is now closed
                    }
                }

                // Open new position
                if (($action === 'BUY' || $action === 'SELL') && !$activePosition) {
                    $customLeverage = isset($bot->parameters['leverage']) ? floatval($bot->parameters['leverage']) : null;
                    $leverage = $exchange->getLeverage($bot->symbol, $customLeverage);
                    $positionValue = $bot->allocated_capital * $leverage;

                    $rawQuantity = ($positionValue / $currentPrice) / $contractSize;
                    $quantity = $exchange->formatAmount($bot->symbol, $rawQuantity);

                    if (floatval($quantity) <= 0) {
                        $market = $exchange->getMarketInfo($bot->symbol);
                        if ($market && isset($market['precision']['amount']) && $market['precision']['amount'] == 1) {
                            $quantity = $exchange->formatAmount($bot->symbol, $positionValue);
                        } else {
                            throw new Exception("Allocated capital ({$bot->allocated_capital}) is too small. Calculated quantity rounded to 0.");
                        }
                    }

                    $order = $exchange->createMarketOrder($bot->symbol, strtolower($action), $quantity);

                    $execPrice = floatval($order['average'] ?? $order['price'] ?? $order['averagePrice'] ?? 0);
                    if ($execPrice <= 0 || (abs($execPrice - $currentPrice) / $currentPrice > 0.2)) {
                        $execPrice = $currentPrice;
                    }

                    $actualFilled = floatval($order['filled'] ?? $order['amount'] ?? $order['contracts'] ?? $order['quantity'] ?? $quantity);
                    if ($actualFilled <= 0) {
                        $actualFilled = $quantity;
                    }

                    $volumeUsd = $execPrice * ($actualFilled * $contractSize);

                    $side = $action === 'BUY' ? 'LONG' : 'SHORT';
                    Position::create([
                        'bot_instance_id' => $bot->id,
                        'user_id' => $bot->user_id,
                        'symbol' => $bot->symbol,
                        'side' => $side,
                        'entry_price' => $execPrice,
                        'quantity' => $actualFilled,
                        'opened_at' => now(),
                        'status' => 'OPEN',
                    ]);

                    $orderId = $order['id'] ?? $order['order_id'] ?? ('ORD-' . uniqid());
                    \App\Models\Trade::create([
                        'bot_instance_id' => $bot->id,
                        'user_id' => $bot->user_id,
                        'order_id' => $orderId,
                        'symbol' => $bot->symbol,
                        'side' => $action,
                        'type' => 'MARKET',
                        'price' => $execPrice,
                        'quantity' => $actualFilled,
                        'volume_usd' => $volumeUsd,
                        'status' => 'FILLED',
                        'executed_at' => now(),
                    ]);

                    $results[$bot->id][] = "{$side} Position Opened (Qty: {$actualFilled})";
                }

            } catch (\Exception $e) {
                \Log::error("Webhook error for bot {$bot->id}: " . $e->getMessage());
                $results[$bot->id][] = 'Error: ' . $e->getMessage();
                
                // Notify user
                if ($bot->user) {
                    $bot->user->notify(new \App\Notifications\BotErrorNotification($bot, $e->getMessage()));
                }
            }
        }

        return response()->json(['success' => true, 'results' => $results]);
    }
}
