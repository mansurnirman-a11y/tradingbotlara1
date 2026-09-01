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
        foreach ($accounts as $account) {
            try {
                $exchange = new \App\Services\ExchangeService($account);
                $bal = $exchange->getAvailableBalance();
                $balances[$account->id] = number_format($bal, 2);
            } catch (\Exception $e) {
                $balances[$account->id] = 'Error';
            }
        }

        $botPrices = [];
        foreach ($bots as $bot) {
            if ($bot->brokerAccount && $bot->brokerAccount->is_active) {
                try {
                    $exchange = new \App\Services\ExchangeService($bot->brokerAccount);
                    $price = $exchange->fetchTicker($bot->symbol);
                    $botPrices[$bot->id] = $price ? number_format($price, 2) : '---';
                } catch (\Exception $e) {
                    $botPrices[$bot->id] = '---';
                }
            }
        }

        return response()->json([
            'balances' => $balances,
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
            if ($normalized === 'inbuildsupertrend' || $normalized === 'supertrend' || $normalized === 'supertrendstrategy') {
                $strategyClass = \App\Strategies\SupertrendStrategy::class;
            } elseif ($normalized === 'emacrossover' || $normalized === 'emacrossoverstrategy') {
                $strategyClass = \App\Strategies\EmaCrossoverStrategy::class;
            } elseif ($normalized === 'rsireversal' || $normalized === 'rsistrategy') {
                $strategyClass = \App\Strategies\RsiStrategy::class;
            } elseif ($normalized === 'macdmomentum' || $normalized === 'macdstrategy') {
                $strategyClass = \App\Strategies\MacdStrategy::class;
            } elseif ($normalized === 'smatrend' || $normalized === 'smacrossoverstrategy') {
                $strategyClass = \App\Strategies\SmaCrossoverStrategy::class;
            } elseif ($normalized === 'bollingerscalper' || $normalized === 'bollingerscalpingstrategy') {
                $strategyClass = \App\Strategies\BollingerScalpingStrategy::class;
            } elseif ($strategyClass && !class_exists($strategyClass)) {
                $namespaced = 'App\\Strategies\\' . ltrim($strategyClass, '\\');
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
        $accounts = Auth::user()->brokerAccounts()->where('is_active', true)->get();
        $strategies = \App\Models\Strategy::where('is_active', true)->get();
        return view('bots.create', compact('accounts', 'strategies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'broker_account_id' => 'required|exists:broker_accounts,id',
            'symbol' => 'required|string|max:20',
            'timeframe' => 'required|string|in:1m,5m,15m,1h,4h,1d',
            'strategy_id' => 'required|exists:strategies,id',
            'allocated_capital' => 'required|numeric|min:10',
            'max_drawdown_pct' => 'required|numeric|min:1|max:100',
            'take_profit_pct' => 'required|numeric|min:0',
            'stop_loss_pct' => 'required|numeric|min:0',
        ]);

        // Ensure the broker account actually belongs to this user
        $account = Auth::user()->brokerAccounts()->findOrFail($validated['broker_account_id']);

        $user = Auth::user();

        if (!$user->is_active) {
            return back()->withErrors('Your account is pending approval. You cannot create bots yet.');
        }

        if ($user->botInstances()->count() >= $user->max_bots) {
            return back()->withErrors("You have reached your maximum bot limit ({$user->max_bots}). Contact an administrator to upgrade.");
        }

        $strategy = \App\Models\Strategy::findOrFail($validated['strategy_id']);

        BotInstance::create([
            'user_id' => $user->id,
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
            ],
            'status' => 'stopped',
        ]);

        return redirect()->route('bots.index')->with('success', 'Trading bot launched successfully.');
    }

    public function toggleStatus(BotInstance $bot)
    {
        // Ensure user owns this bot
        if ($bot->user_id !== Auth::id()) {
            abort(403);
        }

        $user = Auth::user();
        if (!$user->is_active) {
            return back()->withErrors('Your account is pending approval by an administrator. You cannot start bots.');
        }

        $bot->status = $bot->status === 'running' ? 'stopped' : 'running';
        $bot->save();

        $statusMsg = $bot->status === 'running' ? 'started' : 'stopped';
        return back()->with('success', "Bot has been {$statusMsg}.");
    }

    public function destroy(BotInstance $bot)
    {
        if ($bot->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $bot->delete();

        return redirect()->route('bots.index')->with('success', 'Bot instance deleted successfully.');
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
