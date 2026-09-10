<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trade;
use Illuminate\Support\Facades\Auth;

use App\Models\Position;

class TradeController extends Controller
{
    public function index(Request $request)
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

        // 2. Closed Positions (historical ledger with pagination & PnL filter)
        $closedQuery = (clone $baseQuery)->where('status', 'CLOSED');

        $pnlFilter = $request->input('pnl_filter', 'all');
        if ($pnlFilter === 'profitable') {
            $closedQuery->where('realized_pnl', '>', 0);
        } elseif ($pnlFilter === 'loss') {
            $closedQuery->where('realized_pnl', '<', 0);
        } elseif ($pnlFilter === 'breakeven') {
            $closedQuery->where('realized_pnl', '=', 0);
        }

        $closedPositions = $closedQuery
            ->orderBy('closed_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

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
                        $brokerPositions = $service->getOpenPositions();
                        foreach ($brokerPositions as $ep) {
                            if (\App\Services\ExchangeService::symbolsMatch($ep['symbol'], $position->symbol)) {
                                $epSide = strtoupper($ep['side'] ?? '');
                                $contracts = floatval($ep['contracts'] ?? $ep['quantity'] ?? 0);
                                if ($contracts > 0 && ($epSide === $position->side || empty($epSide))) {
                                    $brokerEntry = floatval($ep['entry_price'] ?? 0);
                                    if ($brokerEntry > 0 && abs($brokerEntry - floatval($position->entry_price)) > 0.0001) {
                                        $position->entry_price = $brokerEntry;
                                        $position->update(['entry_price' => $brokerEntry]);
                                    }
                                    if (abs($contracts - floatval($position->quantity)) > 0.000001) {
                                        $position->quantity = $contracts;
                                        $position->update(['quantity' => $contracts]);
                                        $enrichPosition($position);
                                    }
                                    $position->current_price = floatval($ep['current_price'] ?? $position->entry_price);
                                    $position->unrealized_pnl = floatval($ep['unrealized_pnl'] ?? 0);
                                    $brokerFound = true;
                                    break;
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
                $brokerPositions = $service->getOpenPositions();
                foreach ($brokerPositions as $ep) {
                    if (\App\Services\ExchangeService::symbolsMatch($ep['symbol'], $position->symbol)) {
                        $epSide = strtoupper($ep['side'] ?? '');
                        $contracts = floatval($ep['contracts'] ?? $ep['quantity'] ?? 0);

                        if ($contracts > 0 && ($epSide === $position->side || empty($epSide))) {
                            $currentPrice = floatval($ep['current_price'] ?? 0);
                            $pnl = floatval($ep['unrealized_pnl'] ?? 0);
                            $brokerEntry = floatval($ep['entry_price'] ?? 0);

                            // Auto-sync position quantity and entry price if broker changed (e.g. partial closes)
                            $posUpdates = [];
                            if (abs($contracts - floatval($position->quantity)) > 0.000001) {
                                $posUpdates['quantity'] = $contracts;
                                $position->quantity = $contracts;
                            }
                            if ($brokerEntry > 0 && abs($brokerEntry - floatval($position->entry_price)) > 0.0001) {
                                $posUpdates['entry_price'] = $brokerEntry;
                                $position->entry_price = $brokerEntry;
                            }
                            if (!empty($posUpdates)) {
                                $position->update($posUpdates);
                            }

                            if ($currentPrice <= 0) {
                                $currentPrice = floatval($service->fetchTicker($position->symbol) ?? $position->entry_price);
                            }

                            $results[$position->id] = [
                                'current_price' => $currentPrice,
                                'pnl' => $pnl,
                                'margin_used' => $bot->allocated_capital,
                                'quantity' => floatval($position->quantity)
                            ];

                            $foundExchangePos = true;
                            break;
                        }
                    }
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
        $user = Auth::user();
        if ($position->user_id !== $user->id && !in_array($user->role, ['admin', 'superadmin'])) {
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
            
            $order = $exchangeService->closePosition(
                $position->symbol,
                $position->quantity,
                $position->side
            );

            // Fetch execution price
            $execPrice = floatval($order['average'] ?? $order['price'] ?? $order['averagePrice'] ?? 0);
            if ($execPrice <= 0) {
                try {
                    $client = $exchangeService->getClient();
                    if (is_callable([$client, 'fetchOrder']) || is_callable([$client, 'fetch_order'])) {
                        $fetchedOrder = is_callable([$client, 'fetch_order'])
                            ? $client->fetch_order($order['id'] ?? '', $position->symbol)
                            : $client->fetchOrder($order['id'] ?? '', $position->symbol);
                        $execPrice = floatval($fetchedOrder['average'] ?? $fetchedOrder['price'] ?? 0);
                    }
                } catch (\Throwable $e) {
                    $execPrice = 0;
                }
            }

            // Fallback to ticker if still zero
            if ($execPrice <= 0) {
                $tickerPrice = $exchangeService->fetchTicker($position->symbol);
                $execPrice = $tickerPrice ? floatval($tickerPrice) : floatval($position->entry_price);
            }

            // Calculate Realized PNL
            $contractSize = $exchangeService->getContractSize($position->symbol) ?: 1.0;
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

            // Update bot capital and pause bot to prevent auto re-entry
            $newCapital = max(0, round(floatval($bot->allocated_capital) + $pnl, 4));
            $bot->update([
                'allocated_capital' => $newCapital,
                'status' => 'stopped'
            ]);

            // Record Trade entry
            $orderId = $order['id'] ?? $order['order_id'] ?? ('MANUAL-CLOSE-' . uniqid());
            \App\Models\Trade::create([
                'bot_instance_id' => $bot->id,
                'user_id' => $position->user_id,
                'order_id' => $orderId,
                'symbol' => $position->symbol,
                'side' => strtoupper($side),
                'type' => 'MARKET',
                'price' => $execPrice,
                'quantity' => $position->quantity,
                'volume_usd' => $execPrice * ($position->quantity * $contractSize),
                'status' => 'FILLED',
                'realized_pnl' => $pnl,
                'executed_at' => now(),
            ]);

            return redirect()->back()->with('success', "Position closed successfully and Bot #{$bot->id} paused.");
        } catch (\Throwable $e) {
            // If broker API fails (e.g. ghost position not on exchange), gracefully close stuck record in DB
            $position->update([
                'exit_price' => $position->entry_price,
                'status' => 'CLOSED',
                'closed_at' => now(),
                'realized_pnl' => 0,
            ]);

            if ($bot) {
                $bot->update(['status' => 'stopped']);
            }

            return redirect()->back()->with('success', "⚠️ Ghost position was cleared and closed in database. Bot #{$bot->id} paused.");
        }
    }

    public function closeAll(Request $request)
    {
        $user = Auth::user();
        $query = Position::where('status', 'OPEN');
        if ($user && !in_array($user->role, ['admin', 'superadmin'])) {
            $query->where('user_id', $user->id);
        }

        $positions = $query->with('botInstance.brokerAccount')->get();
        $closedCount = 0;

        foreach ($positions as $position) {
            $bot = $position->botInstance;

            try {
                if ($bot && $bot->brokerAccount) {
                    $exchangeService = new \App\Services\ExchangeService($bot->brokerAccount);
                    $side = $position->side === 'LONG' ? 'sell' : 'buy';
                    
                    $order = $exchangeService->closePosition(
                        $position->symbol,
                        $position->quantity,
                        $position->side
                    );

                    $execPrice = floatval($order['average'] ?? $order['price'] ?? $order['averagePrice'] ?? 0);
                    if ($execPrice <= 0) {
                        $tickerPrice = $exchangeService->fetchTicker($position->symbol);
                        $execPrice = $tickerPrice ? floatval($tickerPrice) : floatval($position->entry_price);
                    }

                    $contractSize = $exchangeService->getContractSize($position->symbol) ?: 1.0;
                    $pnl = $position->side === 'LONG'
                        ? ($execPrice - $position->entry_price) * ($position->quantity * $contractSize)
                        : ($position->entry_price - $execPrice) * ($position->quantity * $contractSize);

                    $position->update([
                        'exit_price' => $execPrice,
                        'status' => 'CLOSED',
                        'closed_at' => now(),
                        'realized_pnl' => $pnl,
                    ]);

                    $newCapital = max(0, round(floatval($bot->allocated_capital) + $pnl, 4));
                    $bot->update([
                        'allocated_capital' => $newCapital,
                        'status' => 'stopped'
                    ]);

                    $orderId = $order['id'] ?? $order['order_id'] ?? ('BULK-CLOSE-' . uniqid());
                    \App\Models\Trade::create([
                        'bot_instance_id' => $bot->id,
                        'user_id' => $position->user_id,
                        'order_id' => $orderId,
                        'symbol' => $position->symbol,
                        'side' => strtoupper($side),
                        'type' => 'MARKET',
                        'price' => $execPrice,
                        'quantity' => $position->quantity,
                        'volume_usd' => $execPrice * ($position->quantity * $contractSize),
                        'status' => 'FILLED',
                        'realized_pnl' => $pnl,
                        'executed_at' => now(),
                    ]);
                } else {
                    $position->update([
                        'exit_price' => $position->entry_price,
                        'status' => 'CLOSED',
                        'closed_at' => now(),
                        'realized_pnl' => 0,
                    ]);
                }

                $closedCount++;
            } catch (\Throwable $e) {
                // If broker API fails for ghost trade, close in DB cleanly
                $position->update([
                    'exit_price' => $position->entry_price,
                    'status' => 'CLOSED',
                    'closed_at' => now(),
                    'realized_pnl' => 0,
                ]);
                if ($bot) {
                    $bot->update(['status' => 'stopped']);
                }
                $closedCount++;
            }
        }

        return redirect()->back()->with('success', "Successfully closed and resolved all {$closedCount} positions.");
    }

    public function exportAuditReport(Request $request)
    {
        $user = Auth::user();
        $query = Position::with(['botInstance.brokerAccount', 'user'])->orderBy('closed_at', 'desc')->orderBy('opened_at', 'desc');

        if ($user && !in_array($user->role, ['admin', 'superadmin'])) {
            $query->where('user_id', $user->id);
        }

        $filterStatus = $request->input('status', 'all');
        if ($filterStatus === 'closed') {
            $query->where('status', 'CLOSED');
        } elseif ($filterStatus === 'open') {
            $query->where('status', 'OPEN');
        }

        $pnlFilter = $request->input('pnl_filter', 'all');
        if ($pnlFilter === 'profitable') {
            $query->where('realized_pnl', '>', 0);
        } elseif ($pnlFilter === 'loss') {
            $query->where('realized_pnl', '<', 0);
        } elseif ($pnlFilter === 'breakeven') {
            $query->where('realized_pnl', '=', 0);
        }

        $positions = $query->get();
        $filename = 'trading_audit_report_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($positions) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for proper Excel rendering
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Columns Header
            fputcsv($file, [
                'Position ID',
                'User Name',
                'User Email',
                'Bot ID',
                'Bot Name',
                'Broker',
                'Symbol',
                'Type / Side',
                'Lot Size (Qty)',
                'Allocated Margin (USD)',
                'Entry Price (USD)',
                'Exit Price (USD)',
                'Realized PnL (USD)',
                'ROE (%)',
                'Status',
                'Opened At (UTC)',
                'Closed At (UTC)',
                'Duration (Minutes)',
            ]);

            foreach ($positions as $pos) {
                $margin = floatval($pos->botInstance->allocated_capital ?? 0);
                $pnl = floatval($pos->realized_pnl ?? 0);
                $roe = ($margin > 0 && $pos->status === 'CLOSED') ? round(($pnl / $margin) * 100, 2) : 0;
                
                $duration = '-';
                if ($pos->opened_at && $pos->closed_at) {
                    $duration = round($pos->opened_at->diffInMinutes($pos->closed_at), 1);
                }

                fputcsv($file, [
                    $pos->id,
                    $pos->user->name ?? 'N/A',
                    $pos->user->email ?? 'N/A',
                    $pos->bot_instance_id,
                    $pos->botInstance->name ?? ('Bot #' . $pos->bot_instance_id),
                    strtoupper($pos->botInstance->brokerAccount->broker ?? 'N/A'),
                    $pos->symbol,
                    $pos->side,
                    $pos->quantity,
                    number_format($margin, 2, '.', ''),
                    number_format((float)$pos->entry_price, 4, '.', ''),
                    $pos->exit_price ? number_format((float)$pos->exit_price, 4, '.', '') : 'OPEN',
                    $pos->realized_pnl !== null ? number_format((float)$pos->realized_pnl, 4, '.', '') : '0.00',
                    $roe . '%',
                    $pos->status,
                    $pos->opened_at ? $pos->opened_at->format('Y-m-d H:i:s') : '',
                    $pos->closed_at ? $pos->closed_at->format('Y-m-d H:i:s') : '',
                    $duration,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Force-delete a ghost/stale position record from the database.
     * This does NOT call the broker API — it is for removing bad records only.
     * Superadmin/Admin only.
     */
    public function forceDeletePosition(Request $request, Position $position)
    {
        $user = Auth::user();

        // Strictly admin/superadmin only
        if (!in_array($user->role ?? '', ['admin', 'superadmin'])) {
            return back()->with('error', 'Access Denied: Superadmin privileges required.');
        }

        $symbol = $position->symbol;
        $owner  = $position->user->name ?? 'Unknown';

        // Delete linked trade records for this position's bot on the same symbol
        Trade::where('bot_instance_id', $position->bot_instance_id)
             ->where('symbol', $position->symbol)
             ->delete();

        // Delete the position record itself
        $position->delete();

        return back()->with('success', "🗑️ Ghost position [{$symbol}] for user [{$owner}] has been force-deleted from the database.");
    }

    /**
     * Force-delete a ghost/stale trade execution record from the database.
     * Superadmin/Admin only.
     */
    public function forceDeleteTrade(Request $request, Trade $trade)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return back()->with('error', 'Access Denied: Admin privileges required.');
        }

        $symbol = $trade->symbol;
        $orderId = $trade->order_id ?? ('#' . $trade->id);
        $owner = $trade->user->name ?? 'Unknown';

        $trade->delete();

        return back()->with('success', "🗑️ Ghost trade record [{$symbol} ({$orderId})] for user [{$owner}] has been permanently deleted.");
    }
}

