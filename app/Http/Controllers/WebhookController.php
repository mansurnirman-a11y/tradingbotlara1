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

                $currentPrice = $ticker;

                // Close existing position if needed
                $activePosition = Position::where('bot_instance_id', $bot->id)
                                          ->where('status', 'OPEN')
                                          ->first();

                if ($activePosition) {
                    if ($action === 'CLOSE' || 
                       ($action === 'BUY' && $activePosition->side === 'SHORT') || 
                       ($action === 'SELL' && $activePosition->side === 'LONG')) {
                           
                        $exchange->closePosition($bot->symbol);
                        $pnl = $activePosition->side === 'LONG' 
                            ? ($currentPrice - $activePosition->entry_price) / $activePosition->entry_price * 100
                            : ($activePosition->entry_price - $currentPrice) / $activePosition->entry_price * 100;
                            
                        $activePosition->update([
                            'status' => 'CLOSED',
                            'exit_price' => $currentPrice,
                            'closed_at' => now(),
                            'realized_pnl' => $pnl,
                        ]);
                        
                        $results[$bot->id][] = 'Position Closed';
                    }
                }

                // Open new position
                if ($action === 'BUY' && (!$activePosition || $activePosition->side === 'SHORT' || $action === 'CLOSE')) {
                    $exchange->createOrder($bot->symbol, 'market', 'buy', $bot->allocated_capital);
                    Position::create([
                        'bot_instance_id' => $bot->id,
                        'symbol' => $bot->symbol,
                        'side' => 'LONG',
                        'entry_price' => $currentPrice,
                        'amount' => $bot->allocated_capital,
                        'opened_at' => now(),
                        'status' => 'OPEN',
                    ]);
                    $results[$bot->id][] = 'LONG Position Opened';
                } elseif ($action === 'SELL' && (!$activePosition || $activePosition->side === 'LONG' || $action === 'CLOSE')) {
                    $exchange->createOrder($bot->symbol, 'market', 'sell', $bot->allocated_capital);
                    Position::create([
                        'bot_instance_id' => $bot->id,
                        'symbol' => $bot->symbol,
                        'side' => 'SHORT',
                        'entry_price' => $currentPrice,
                        'amount' => $bot->allocated_capital,
                        'opened_at' => now(),
                        'status' => 'OPEN',
                    ]);
                    $results[$bot->id][] = 'SHORT Position Opened';
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
