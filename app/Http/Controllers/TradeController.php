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
        $baseQuery = Position::with(['botInstance.brokerAccount', 'user']);

        $user = Auth::user();
        if ($user && !in_array($user->role, ['admin', 'superadmin'])) {
            $baseQuery->where('user_id', $user->id);
        }

        // 1. Open Positions (all active live positions)
        $openPositions = (clone $baseQuery)
            ->where('status', 'OPEN')
            ->orderBy('opened_at', 'desc')
            ->get();

        // 2. Closed Positions (historical ledger with pagination)
        $closedPositions = (clone $baseQuery)
            ->where('status', 'CLOSED')
            ->orderBy('closed_at', 'desc')
            ->paginate(20);

        $exchangeServices = [];
        $enrichPosition = function ($position) use (&$exchangeServices) {
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
        };

        foreach ($openPositions as $position) {
            $enrichPosition($position);
            
            // Pre-calculate instant live PnL for initial page load from broker if available
            $bot = $position->botInstance;
            if ($bot && $bot->brokerAccount) {
                $service = $exchangeServices[$bot->brokerAccount->id] ?? null;
                if ($service) {
                    try {
                        $brokerFound = false;
                        $client = $service->getClient();
                        if (is_callable([$client, 'fetch_positions']) || is_callable([$client, 'fetchPositions'])) {
                            $eps = is_callable([$client, 'fetch_positions']) ? $client->fetch_positions() : $client->fetchPositions();
                            if (is_array($eps)) {
                                foreach ($eps as $ep) {
                                    $epSymbol = $ep['symbol'] ?? $ep['product_symbol'] ?? '';
                                    if (str_replace(['/', '-', ':'], '', $epSymbol) === str_replace(['/', '-', ':'], '', $position->symbol)) {
                                        $contracts = floatval($ep['contracts'] ?? $ep['size'] ?? $ep['amount'] ?? 0);
                                        $exchangeSide = $contracts > 0 ? 'LONG' : ($contracts < 0 ? 'SHORT' : '');
                                        if (abs($contracts) > 0 && ($exchangeSide === $position->side || empty($exchangeSide))) {
                                            $brokerEntry = $ep['entryPrice'] ?? $ep['entry_price'] ?? $ep['averageEntryPrice'] ?? $ep['info']['entry_price'] ?? null;
                                            if ($brokerEntry && (float)$brokerEntry != (float)$position->entry_price) {
                                                $position->entry_price = (float)$brokerEntry;
                                                $position->update(['entry_price' => (float)$brokerEntry]);
                                            }
                                            if (abs($contracts) != (float)$position->quantity) {
                                                $position->quantity = abs($contracts);
                                                $position->update(['quantity' => abs($contracts)]);
                                                $enrichPosition($position);
                                            }
                                            $position->current_price = (float)($ep['markPrice'] ?? $ep['mark_price'] ?? $ep['currentPrice'] ?? $position->entry_price);
                                            $position->unrealized_pnl = (float)($ep['unrealizedPnl'] ?? $ep['unrealized_pnl'] ?? 0);
                                            $brokerFound = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        if (!$brokerFound) {
                            $tickerPrice = $service->fetchTicker($position->symbol);
                            if ($tickerPrice && is_numeric($tickerPrice)) {
                                $position->current_price = (float)$tickerPrice;
                                $contractSize = $service->getContractSize($position->symbol);
                                if ($position->side === 'LONG') {
                                    $position->unrealized_pnl = ($position->current_price - $position->entry_price) * ($position->quantity * $contractSize);
                                } else {
                                    $position->unrealized_pnl = ($position->entry_price - $position->current_price) * ($position->quantity * $contractSize);
                                }
                            }
                        }
                    } catch (\Throwable $e) {}
                }
            }
        }

        foreach ($closedPositions as $position) {
            $enrichPosition($position);
        }

        // Backward compatibility
        $positions = $closedPositions;

        return view('trades.index', compact('openPositions', 'closedPositions', 'positions'));
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
        $user = Auth::user();
        if ($user && !in_array($user->role, ['admin', 'superadmin'])) {
            $query->where('user_id', $user->id);
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
                } catch (\Throwable $e) {
                    continue;
                }
            }

            $service = $exchangeServices[$brokerId];
            if (!$service) continue;

            try {
                $foundExchangePos = false;
                $client = $service->getClient();

                // Fetch real live position PnL directly from broker/exchange
                if (is_callable([$client, 'fetch_positions']) || is_callable([$client, 'fetchPositions'])) {
                    try {
                        $exchangePositions = is_callable([$client, 'fetch_positions']) 
                            ? $client->fetch_positions() 
                            : $client->fetchPositions();

                        if (is_array($exchangePositions)) {
                            foreach ($exchangePositions as $ep) {
                                $epSymbol = $ep['symbol'] ?? $ep['product_symbol'] ?? $ep['info']['product_symbol'] ?? '';
                                if (str_replace(['/', '-', ':'], '', $epSymbol) === str_replace(['/', '-', ':'], '', $position->symbol)) {
                                    $contracts = floatval($ep['contracts'] ?? $ep['size'] ?? $ep['amount'] ?? 0);
                                    $exchangeSide = $contracts > 0 ? 'LONG' : ($contracts < 0 ? 'SHORT' : '');
                                    
                                    if (abs($contracts) > 0 && ($exchangeSide === $position->side || empty($exchangeSide))) {
                                        $currentPrice = $ep['markPrice'] ?? $ep['mark_price'] ?? $ep['currentPrice'] ?? $ep['info']['mark_price'] ?? null;
                                        $pnl = $ep['unrealizedPnl'] ?? $ep['unrealized_pnl'] ?? $ep['info']['unrealized_pnl'] ?? null;
                                        $brokerEntry = $ep['entryPrice'] ?? $ep['entry_price'] ?? $ep['averageEntryPrice'] ?? $ep['info']['entry_price'] ?? null;
                                        
                                        // Auto-sync position quantity and entry price if broker changed (e.g. partial closes)
                                        $posUpdates = [];
                                        if (abs($contracts) != (float)$position->quantity) {
                                            $posUpdates['quantity'] = abs($contracts);
                                            $position->quantity = abs($contracts);
                                        }
                                        if ($brokerEntry && (float)$brokerEntry != (float)$position->entry_price) {
                                            $posUpdates['entry_price'] = (float)$brokerEntry;
                                            $position->entry_price = (float)$brokerEntry;
                                        }
                                        if (!empty($posUpdates)) {
                                            $position->update($posUpdates);
                                        }

                                        if (!$currentPrice) {
                                            $currentPrice = $service->fetchTicker($position->symbol) ?? $position->entry_price;
                                        }

                                        if ($pnl === null) {
                                            $contractSize = $service->getContractSize($position->symbol);
                                            $pnl = $position->side === 'LONG'
                                                ? ($currentPrice - $position->entry_price) * ($position->quantity * $contractSize)
                                                : ($position->entry_price - $currentPrice) * ($position->quantity * $contractSize);
                                        }

                                        $results[$position->id] = [
                                            'current_price' => floatval($currentPrice),
                                            'pnl' => floatval($pnl),
                                            'margin_used' => $bot->allocated_capital,
                                            'quantity' => floatval($position->quantity)
                                        ];
                                        
                                        $foundExchangePos = true;
                                        break;
                                    }
                                }
                            }
                        }
                    } catch (\Throwable $epErr) {}
                }
                
                // Standard Direct Ticker calculation fallback if broker position is not directly queried
                if (!$foundExchangePos) {
                    $contractSize = $service->getContractSize($position->symbol);
                    $currentPrice = $service->fetchTicker($position->symbol);
                    
                    if ($currentPrice && is_numeric($currentPrice)) {
                        $currentPrice = (float)$currentPrice;
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
            } catch (\Throwable $e) {
                \Log::warning("Error calculating live PnL for pos {$position->id}: " . $e->getMessage());
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
