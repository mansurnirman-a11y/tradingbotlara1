<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trade;
use Illuminate\Support\Facades\Auth;

use App\Models\Position;

class TradeController extends Controller
{
    public function index()
    {
        $query = Position::with(['botInstance.brokerAccount', 'user'])->orderBy('created_at', 'desc');

        if (!in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            $query->where('user_id', Auth::id());
        }

        $positions = $query->paginate(20);

        $exchangeServices = [];
        foreach ($positions as $position) {
            $bot = $position->botInstance;
            if ($bot && $bot->brokerAccount) {
                $brokerId = $bot->brokerAccount->id;
                if (!isset($exchangeServices[$brokerId])) {
                    try {
                        $exchangeServices[$brokerId] = new \App\Services\ExchangeService($bot->brokerAccount);
                    } catch (\Exception $e) {
                        $exchangeServices[$brokerId] = null;
                    }
                }
                
                $service = $exchangeServices[$brokerId];
                if ($service) {
                    $contractSize = $service->getContractSize($position->symbol);
                    $position->trade_value = $position->quantity * $contractSize * $position->entry_price;
                    $position->base_size = $position->quantity * $contractSize;
                } else {
                    $position->trade_value = $position->quantity * $position->entry_price;
                    $position->base_size = $position->quantity;
                }
                $position->margin_used = $bot->allocated_capital;
            } else {
                $position->trade_value = $position->quantity * $position->entry_price;
                $position->base_size = $position->quantity;
                $position->margin_used = 0;
            }
        }

        return view('trades.index', compact('positions'));
    }

    public function getLivePnl(Request $request)
    {
        $positionIds = $request->input('position_ids', []);
        $fetchAll = $request->input('all_open', false);
        
        if (empty($positionIds) && !$fetchAll) {
            return response()->json([]);
        }

        $query = Position::where('status', 'OPEN');
        
        // If user is not admin, restrict to their own positions
        if (!in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            $query->where('user_id', Auth::id());
        }
        
        if (!$fetchAll) {
            $query->whereIn('id', $positionIds);
        }

        $positions = $query->with('botInstance.brokerAccount')->get();

        $results = [];
        $exchangeServices = [];

        foreach ($positions as $position) {
            $bot = $position->botInstance;
            if (!$bot || !$bot->brokerAccount) continue;

            $brokerId = $bot->brokerAccount->id;
            
            // Cache exchange service per broker to avoid re-initializing
            if (!isset($exchangeServices[$brokerId])) {
                try {
                    $exchangeServices[$brokerId] = new \App\Services\ExchangeService($bot->brokerAccount);
                } catch (\Exception $e) {
                    continue;
                }
            }

            $service = $exchangeServices[$brokerId];

            try {
                // Use fetch_positions to get the exact Unrealized PNL and Mark Price from the exchange
                // This handles inverse/linear math and funding fees natively
                $exchangePositions = $service->getClient()->fetch_positions();
                
                $foundExchangePos = false;
                foreach ($exchangePositions as $ep) {
                    $epSymbol = $ep['symbol'] ?? $ep['product_symbol'] ?? $ep['info']['product_symbol'] ?? '';
                    $contracts = floatval($ep['contracts'] ?? $ep['size'] ?? 0);
                    $exchangeSide = $contracts > 0 ? 'LONG' : ($contracts < 0 ? 'SHORT' : '');
                    
                    if (str_replace(['/', '-', ':'], '', $epSymbol) === str_replace(['/', '-', ':'], '', $position->symbol) && abs($contracts) > 0 && $exchangeSide === $position->side) {
                        
                        $actualEntryPrice = $ep['entryPrice'] ?? $ep['entry_price'] ?? $ep['info']['entry_price'] ?? null;
                        if ($actualEntryPrice && $actualEntryPrice != $position->entry_price) {
                            $position->update(['entry_price' => $actualEntryPrice]);
                        }
                        
                        $currentPrice = $ep['markPrice'] ?? $ep['mark_price'] ?? $ep['info']['mark_price'] ?? null;
                        $pnl = $ep['unrealizedPnl'] ?? $ep['unrealized_pnl'] ?? $ep['info']['unrealized_pnl'] ?? 0;
                        
                        if (!$currentPrice) {
                            $ticker = $service->getClient()->fetchTicker($position->symbol);
                            $currentPrice = $ticker['last'] ?? $position->entry_price;
                        }

                        $results[$position->id] = [
                            'current_price' => $currentPrice,
                            'pnl' => floatval($pnl),
                            'margin_used' => $bot->allocated_capital
                        ];
                        
                        $foundExchangePos = true;
                        break;
                    }
                }
                
                // Fallback for MetaApi or exchanges that don't support fetch_positions well
                if (!$foundExchangePos) {
                    $contractSize = $service->getContractSize($position->symbol);
                    $ticker = $service->getClient()->fetchTicker($position->symbol);
                    $currentPrice = $ticker['last'] ?? null;

                    if ($currentPrice) {
                        $pnl = 0;
                        if ($position->side === 'LONG') {
                            $pnl = ($currentPrice - $position->entry_price) * ($position->quantity * $contractSize);
                        } else {
                            $pnl = ($position->entry_price - $currentPrice) * ($position->quantity * $contractSize);
                        }

                        $results[$position->id] = [
                            'current_price' => $currentPrice,
                            'pnl' => $pnl,
                            'margin_used' => $bot->allocated_capital
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Skip if error fetching positions
                continue;
            }
        }

        return response()->json($results);
    }

    public function closePosition(Request $request, Position $position)
    {
        if ($position->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        if ($position->status !== 'OPEN') {
            return redirect()->back()->with('error', 'Position is already closed.');
        }

        $bot = $position->botInstance;
        if (!$bot || !$bot->brokerAccount) {
            return redirect()->back()->with('error', 'Broker account not found.');
        }

        try {
            $exchangeService = new \App\Services\ExchangeService($bot->brokerAccount);
            $side = $position->side === 'LONG' ? 'sell' : 'buy';
            
            $order = $exchangeService->createMarketOrder(
                $position->symbol,
                $side,
                $position->quantity
            );

            // Fetch execution price
            $execPrice = $order['average'] ?? $order['price'] ?? null;
            if (!$execPrice || floatval($execPrice) == 0) {
                try {
                    sleep(1);
                    $fetchedOrder = $exchangeService->getClient()->fetchOrder($order['id'], $position->symbol);
                    $execPrice = $fetchedOrder['average'] ?? $fetchedOrder['price'] ?? 0;
                } catch (\Exception $e) {
                    $execPrice = 0; // Fallback
                }
            }

            // Fallback to ticker if still zero
            if (!$execPrice || floatval($execPrice) == 0) {
                $ticker = $exchangeService->getClient()->fetchTicker($position->symbol);
                $execPrice = $ticker['last'] ?? $position->entry_price;
            }

            // Calculate Realized PNL
            $contractSize = $exchangeService->getContractSize($position->symbol);
            $pnl = 0;
            if ($position->side === 'LONG') {
                $pnl = ($execPrice - $position->entry_price) * ($position->quantity * $contractSize);
            } else {
                $pnl = ($position->entry_price - $execPrice) * ($position->quantity * $contractSize);
            }

            $position->update([
                'exit_price' => $execPrice,
                'status' => 'CLOSED',
                'closed_at' => now(),
                'realized_pnl' => $pnl,
            ]);

            return redirect()->back()->with('success', 'Position closed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to close position: ' . $e->getMessage());
        }
    }
}
