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
                $balanceData = $exchange->fetchBalance();
                
                if (empty($balanceData)) {
                    $balances[$account->id] = 'API Error/Blocked';
                } else {
                    // Check USDT, then USD (Delta uses USD for balance)
                    $usdtFree = $balanceData['USDT']['free'] ?? ($balanceData['total']['USDT'] ?? 0);
                    $usdFree = $balanceData['USD']['free'] ?? ($balanceData['total']['USD'] ?? 0);
                    $balances[$account->id] = $usdtFree + $usdFree;
                }
            } catch (\Exception $e) {
                $balances[$account->id] = 'Error/API limits';
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
                $balanceData = $exchange->fetchBalance();
                
                if (empty($balanceData)) {
                    $balances[$account->id] = 'API Error/Blocked';
                } else {
                    $usdtFree = $balanceData['USDT']['free'] ?? ($balanceData['total']['USDT'] ?? 0);
                    $usdFree = $balanceData['USD']['free'] ?? ($balanceData['total']['USD'] ?? 0);
                    $balances[$account->id] = is_numeric($usdtFree + $usdFree) ? number_format($usdtFree + $usdFree, 2) : 'Error';
                }
            } catch (\Exception $e) {
                $balances[$account->id] = 'Error/API limits';
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
            if ($bot->strategy_class && class_exists($bot->strategy_class)) {
                $strategy = new $bot->strategy_class();
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
            'take_profit_pct' => 'required|numeric|min:0.1',
            'stop_loss_pct' => 'required|numeric|min:0.1',
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
        if ($bot->user_id !== Auth::id()) {
            abort(403);
        }

        $bot->delete();

        return redirect()->route('bots.index')->with('success', 'Bot instance deleted successfully.');
    }
}
