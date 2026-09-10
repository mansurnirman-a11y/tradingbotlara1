<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BotInstance;
use App\Models\BrokerAccount;
use Illuminate\Support\Facades\Auth;

class BotInstanceController extends Controller
{
    public function index()
    {
        $bots = Auth::user()->botInstances()->with('brokerAccount')->latest()->get();
        
        $allBots = null;
        if (in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            $allBots = BotInstance::with(['brokerAccount', 'user'])->latest()->get();
        }
        
        // Fetch real-time balances for connected accounts
        $accounts = Auth::user()->brokerAccounts()->where('is_active', true)->get();
        $balances = [];
        
        foreach ($accounts as $account) {
            try {
                $exchange = new \App\Services\ExchangeService($account);
                $balances[$account->id] = $exchange->getAvailableBalance();
            } catch (\Exception $e) {
                $balances[$account->id] = 0;
            }
        }

        // Fetch live market prices for each bot
        $botPrices = [];
        foreach ($bots as $bot) {
            if ($bot->brokerAccount && $bot->brokerAccount->is_active) {
                try {
                    $exchange = new \App\Services\ExchangeService($bot->brokerAccount);
                    $botPrices[$bot->id] = $exchange->fetchTicker($bot->symbol);
                } catch (\Exception $e) {
                    $botPrices[$bot->id] = null;
                }
            } else {
                $botPrices[$bot->id] = null;
            }
        }

        return view('bots.index', compact('bots', 'balances', 'botPrices', 'allBots'));
    }

    public function liveData()
    {
        $bots = Auth::user()->botInstances()->with('brokerAccount')->get();
        $accounts = Auth::user()->brokerAccounts()->where('is_active', true)->get();
        
        $balances = [];
        $currencies = [];
        foreach ($accounts as $account) {
            try {
                $exchange = new \App\Services\ExchangeService($account);
                $balInfo = $exchange->fetchBalanceDetails();
                $balances[$account->id] = number_format($balInfo['free'], 2);
                $currencies[$account->id] = $balInfo['currency'];
            } catch (\Throwable $e) {
                $balances[$account->id] = 'Error';
                $currencies[$account->id] = 'USD';
            }
        }

        $botPrices = [];
        foreach ($bots as $bot) {
            if ($bot->brokerAccount && $bot->brokerAccount->is_active) {
                try {
                    $exchange = new \App\Services\ExchangeService($bot->brokerAccount);
                    $price = $exchange->fetchTicker($bot->symbol);
                    $botPrices[$bot->id] = $price ? number_format($price, 2) : '---';
                } catch (\Throwable $e) {
                    $botPrices[$bot->id] = '---';
                }
            }
        }

        return response()->json([
            'balances' => $balances,
            'currencies' => $currencies,
            'botPrices' => $botPrices
        ]);
    }

    public function chartData(BotInstance $bot)
    {
        if ($bot->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $exchange = new \App\Services\ExchangeService($bot->brokerAccount);
            $ohlcv = $exchange->fetchOHLCV($bot->symbol, $bot->timeframe, 150);
            
            // Format for lightweight-charts
            $candles = [];
            foreach ($ohlcv as $candle) {
                // Ensure time is an integer in seconds
                $time = (int) floor($candle[0] / 1000);
                
                $candles[] = [
                    'time' => $time,
                    'open' => (float) $candle[1],
                    'high' => (float) $candle[2],
                    'low'  => (float) $candle[3],
                    'close'=> (float) $candle[4],
                ];
            }

            // Lightweight Charts strictly requires ascending order
            usort($candles, function($a, $b) {
                return $a['time'] <=> $b['time'];
            });

            // Remove duplicates which can also crash the chart
            $uniqueCandles = [];
            $lastTime = 0;
            foreach ($candles as $c) {
                if ($c['time'] > $lastTime) {
                    $uniqueCandles[] = $c;
                    $lastTime = $c['time'];
                }
            }
            $candles = $uniqueCandles;

            // Get active position
            $position = \App\Models\Position::where('bot_instance_id', $bot->id)
                                            ->where('status', 'OPEN')
                                            ->first();
            $positionData = null;
            if ($position) {
                $entryPrice = (float) $position->entry_price;
                $slPct = ($bot->parameters['stop_loss_pct'] ?? 1.5) / 100;
                $tpPct = ($bot->parameters['take_profit_pct'] ?? 3.0) / 100;

                $slPrice = $position->side === 'LONG' ? $entryPrice * (1 - $slPct) : $entryPrice * (1 + $slPct);
                $tpPrice = $position->side === 'LONG' ? $entryPrice * (1 + $tpPct) : $entryPrice * (1 - $tpPct);

                // Find the closest candle timestamp that is <= opened_at
                $posTime = $position->opened_at->timestamp;
                $markerTime = $candles[0]['time'] ?? $posTime;
                foreach ($candles as $c) {
                    if ($c['time'] <= $posTime) {
                        $markerTime = $c['time'];
                    }
                }

                $positionData = [
                    'entry' => $entryPrice,
                    'side' => $position->side,
                    'sl' => $slPrice,
                    'tp' => $tpPrice,
                    'time' => $markerTime
                ];
            }

            // Get Strategy Data
            $strategyData = null;
            $strategyClass = $bot->strategy_class ?: ($bot->strategy ? $bot->strategy->class_name : null);
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
            }

            if (!$strategyClass || !class_exists($strategyClass)) {
                $cleanName = class_basename($strategyClass ?? '');
                $namespaced = 'App\\Strategies\\' . $cleanName;
                if (class_exists($namespaced)) {
                    $strategyClass = $namespaced;
                }
            }

            if ($strategyClass && class_exists($strategyClass)) {
                $strategy = new $strategyClass();
                if ($strategy instanceof \App\Strategies\StrategyInterface) {
                    $strategyData = $strategy->getChartData($ohlcv, $bot->parameters ?? []);
                }
            }

            return response()->json([
                'success' => true,
                'candles' => $candles,
                'position' => $positionData,
                'strategy' => $strategyData
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function create()
    {
        $authUser = Auth::user();
        $isAdmin = in_array($authUser->role, ['admin', 'superadmin']) || (method_exists($authUser, 'isAdmin') && $authUser->isAdmin());
        
        $users = null;
        $userAccountsMap = [];
        
        if ($isAdmin) {
            $users = \App\Models\User::orderBy('name')->get();
            $allAccounts = \App\Models\BrokerAccount::get();
            foreach ($allAccounts as $acc) {
                $brokerName = $acc->broker ?: ($acc->broker_type ?: 'Broker');
                $label = $acc->account_label ?: ($acc->name ?: ($brokerName . ' #' . $acc->id));
                $userAccountsMap[(string)$acc->user_id][] = [
                    'id' => $acc->id,
                    'label' => $label . ' (' . strtoupper(str_replace('_', ' ', $brokerName)) . ')' . ($acc->is_active ? '' : ' [Inactive]'),
                    'broker' => $brokerName,
                    'is_active' => (bool)$acc->is_active,
                ];
            }
        }
        
        $accounts = $authUser->brokerAccounts()->get();
        $strategies = \App\Models\Strategy::where('is_active', true)->get();
        
        return view('bots.create', compact('accounts', 'strategies', 'users', 'userAccountsMap', 'isAdmin'));
    }

    public function getUserBrokerAccounts(\App\Models\User $user)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }
        $accounts = $user->brokerAccounts()->get()->map(function($acc) {
            $brokerName = $acc->broker ?: ($acc->broker_type ?: 'Broker');
            $label = $acc->account_label ?: ($acc->name ?: ($brokerName . ' #' . $acc->id));
            return [
                'id' => $acc->id,
                'label' => $label . ' (' . strtoupper(str_replace('_', ' ', $brokerName)) . ')' . ($acc->is_active ? '' : ' [Inactive]'),
                'broker' => $brokerName,
                'is_active' => (bool)$acc->is_active,
            ];
        });
        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        $isAdmin = in_array($authUser->role, ['admin', 'superadmin']) || (method_exists($authUser, 'isAdmin') && $authUser->isAdmin());

        $rules = [
            'broker_account_id' => 'required|exists:broker_accounts,id',
            'symbol' => 'required|string|max:20',
            'timeframe' => 'required|string|in:1m,5m,15m,1h,4h,1d',
            'strategy_id' => 'required|exists:strategies,id',
            'allocated_capital' => 'required|numeric|min:10',
            'max_drawdown_pct' => 'required|numeric|min:1|max:100',
            'take_profit_pct' => 'required|numeric|min:0',
            'stop_loss_pct' => 'required|numeric|min:0',
            'leverage' => 'nullable|numeric|min:1|max:500',
        ];

        if ($isAdmin) {
            $rules['user_id'] = 'nullable|exists:users,id';
        }

        $validated = $request->validate($rules);

        $targetUser = $authUser;
        if ($isAdmin && !empty($validated['user_id'])) {
            $targetUser = \App\Models\User::findOrFail($validated['user_id']);
        }

        // Ensure the broker account belongs to targetUser
        $account = $targetUser->brokerAccounts()->findOrFail($validated['broker_account_id']);

        if (!$targetUser->is_active) {
            return back()->withErrors('The selected user account is pending approval or inactive.');
        }

        if (!$isAdmin && $targetUser->botInstances()->count() >= $targetUser->max_bots) {
            return back()->withErrors("User has reached their maximum bot limit ({$targetUser->max_bots}). Contact an administrator to upgrade.");
        }

        $strategy = \App\Models\Strategy::findOrFail($validated['strategy_id']);

        BotInstance::create([
            'user_id' => $targetUser->id,
            'broker_account_id' => $account->id,
            'strategy_id' => $strategy->id,
            'name' => $validated['symbol'] . ' - ' . $strategy->name,
            'symbol' => strtoupper($validated['symbol']),
            'timeframe' => $validated['timeframe'],
            'strategy_class' => $strategy->class_name ?? 'Webhook', // fallback since DB column is not nullable
            'allocated_capital' => $validated['allocated_capital'],
            'max_drawdown_pct' => $validated['max_drawdown_pct'],
            'parameters' => [
                'take_profit_pct' => $validated['take_profit_pct'],
                'stop_loss_pct' => $validated['stop_loss_pct'],
                'leverage' => floatval($validated['leverage'] ?? 25),
            ],
            'status' => 'stopped',
        ]);

        $msg = $targetUser->id === $authUser->id 
            ? 'Trading bot launched successfully.'
            : "Trading bot successfully created for client {$targetUser->name} ({$targetUser->email}).";

        return redirect()->route('bots.index')->with('success', $msg);
    }

    public function toggleStatus(BotInstance $bot)
    {
        // Ensure user owns this bot or is an admin/superadmin
        if ($bot->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $user = Auth::user();
        if (!$user->is_active && !Auth::user()->isAdmin()) {
            return back()->withErrors('Your account is pending approval by an administrator. You cannot start bots.');
        }

        $bot->status = $bot->status === 'running' ? 'stopped' : 'running';
        $bot->save();

        $statusMsg = $bot->status === 'running' ? 'started' : 'stopped';
        return back()->with('success', "Bot #{$bot->id} ({$bot->symbol}) has been {$statusMsg}.");
    }

    public function destroy(BotInstance $bot)
    {
        if ($bot->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        // Clean up linked positions and trades
        $bot->positions()->delete();
        $bot->trades()->delete();
        $bot->delete();

        return back()->with('success', "Bot #{$bot->id} ({$bot->symbol}) deleted successfully.");
    }

    public function importOanda()
    {
        $accounts = Auth::user()->brokerAccounts()
            ->where('broker', 'oanda')
            ->where('is_active', true)
            ->get();

        $importedCount = 0;

        foreach ($accounts as $account) {
            try {
                $bridge = new \App\Services\CustomApiBridgeService($account);
                $positions = $bridge->fetchPositions();

                foreach ($positions as $p) {
                    $symbol = $p['symbol'];
                    $amount = (float)$p['amount'];
                    $entryPrice = (float)$p['averageEntryPrice'];
                    $createdAt = isset($p['createdAt']) ? \Carbon\Carbon::parse($p['createdAt']) : now();

                    if ($amount == 0) continue;

                    // Try to find a bot instance for this symbol
                    $bot = Auth::user()->botInstances()
                        ->where('symbol', $symbol)
                        ->first();

                    if (!$bot) {
                        // Find or default strategy
                        $strategy = \App\Models\Strategy::where('class_name', 'like', '%Ema%')->first() ?? \App\Models\Strategy::first();
                        
                        // Create a default bot instance to host this imported trade
                        $bot = \App\Models\BotInstance::create([
                            'user_id' => Auth::id(),
                            'broker_account_id' => $account->id,
                            'name' => "Imported " . $symbol . " Bot",
                            'symbol' => $symbol,
                            'strategy_class' => $strategy->class_name ?? 'App\\Strategies\\EmaCrossoverStrategy',
                            'timeframe' => '1h',
                            'allocated_capital' => abs($amount) * $entryPrice,
                            'max_drawdown_pct' => 5.00,
                            'parameters' => [
                                'take_profit_pct' => 3.0,
                                'stop_loss_pct' => 1.5,
                            ],
                            'status' => 'paused',
                        ]);
                    }

                    // Check if this bot already has an open position
                    $hasOpen = $bot->positions()->where('status', 'OPEN')->exists();
                    if ($hasOpen) continue;

                    // Create the Position record
                    \App\Models\Position::create([
                        'bot_instance_id' => $bot->id,
                        'user_id' => Auth::id(),
                        'symbol' => $symbol,
                        'side' => $amount > 0 ? 'LONG' : 'SHORT',
                        'quantity' => abs($amount),
                        'entry_price' => $entryPrice,
                        'status' => 'OPEN',
                        'opened_at' => $createdAt,
                    ]);

                    // Create matching trade log in ledger
                    \App\Models\Trade::create([
                        'bot_instance_id' => $bot->id,
                        'user_id' => Auth::id(),
                        'order_id' => 'IMPORTED-' . strtoupper(uniqid()),
                        'symbol' => $symbol,
                        'side' => $amount > 0 ? 'BUY' : 'SELL',
                        'type' => 'MARKET',
                        'price' => $entryPrice,
                        'quantity' => abs($amount),
                        'status' => 'FILLED',
                        'executed_at' => $createdAt,
                    ]);

                    $importedCount++;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to import Oanda trades: " . $e->getMessage());
            }
        }

        if ($importedCount > 0) {
            return back()->with('success', "Successfully imported {$importedCount} active trade(s) from Oanda into your bots dashboard!");
        }

        return back()->with('success', "No new open trades found on Oanda to import.");
    }
}
