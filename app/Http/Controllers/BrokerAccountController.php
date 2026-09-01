<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BrokerAccount;
use Illuminate\Support\Facades\Auth;

class BrokerAccountController extends Controller
{
    public function index()
    {
        $accounts = Auth::user()->brokerAccounts()->orderBy('created_at', 'desc')->get();
        
        $allBrokerAccounts = null;
        if (in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            $allBrokerAccounts = BrokerAccount::with('user')->orderBy('created_at', 'desc')->get();
        }

        return view('brokers.index', compact('accounts', 'allBrokerAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'broker' => 'required|in:binance,delta_india,mt4,mt5,oanda,custom_api',
            'server_name' => 'nullable|string|max:100|required_if:broker,mt4,mt5',
            'account_label' => 'required|string|max:100',
            'api_key' => 'nullable|string|required_unless:broker,oanda,custom_api',
            'api_secret' => 'nullable|string|required_unless:broker,oanda,custom_api',
            'bridge_url' => 'nullable|url|required_if:broker,oanda,custom_api',
        ]);

        $metaAccountId = null;

        // Auto-provision Cloud MT4/MT5 account if broker is MetaTrader
        if (in_array($validated['broker'], ['mt4', 'mt5'])) {
            try {
                $provisionResult = \App\Services\MetaApiBridgeService::provisionAccount(
                    $validated['account_label'],
                    $validated['api_key'],
                    $validated['api_secret'],
                    $validated['server_name'] ?? 'KasperCapitalMarkets-Server',
                    $validated['broker']
                );
                $metaAccountId = $provisionResult['id'] ?? null;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("MetaApi auto-provision notice: " . $e->getMessage());
                // Non-blocking fallback so user can still connect successfully
            }
        }

        $accountData = [
            'user_id' => Auth::id(),
            'broker' => $validated['broker'],
            'server_name' => $validated['server_name'] ?? null,
            'account_label' => $validated['account_label'],
            'api_key' => $validated['api_key'] ?? null, // Automatically encrypted by Model cast
            'api_secret' => $validated['api_secret'] ?? null, // Automatically encrypted by Model cast
            'bridge_url' => $validated['bridge_url'] ?? null,
            'meta_account_id' => $metaAccountId,
            'is_active' => true,
        ];

        BrokerAccount::create($accountData);

        return back()->with('success', 'Broker account securely connected.');
    }

    public function destroy($id)
    {
        \Illuminate\Support\Facades\Log::debug("BrokerAccountController::destroy called for ID: " . $id . " by User: " . Auth::id());
        $account = Auth::user()->brokerAccounts()->findOrFail($id);
        
        // Ensure no active bots are using this account before deleting
        if ($account->botInstances()->where('status', 'running')->exists()) {
            return back()->with('error', 'Cannot delete broker because there are active bots using it. Please stop the bots first.');
        }

        $account->delete();

        return back()->with('success', 'Broker account deleted successfully.');
    }

    public function deleteGet($id)
    {
        \Illuminate\Support\Facades\Log::debug("BrokerAccountController::deleteGet called for ID: " . $id . " by User: " . Auth::id());
        $account = Auth::user()->brokerAccounts()->findOrFail($id);
        
        // Ensure no active bots are using this account before deleting
        if ($account->botInstances()->where('status', 'running')->exists()) {
            return back()->with('error', 'Cannot delete broker because there are active bots using it. Please stop the bots first.');
        }

        $account->delete();

        return back()->with('success', 'Broker account deleted successfully.');
    }

    public function liveBalances(Request $request)
    {
        $accountIds = $request->input('account_ids', []);
        
        $query = BrokerAccount::whereIn('id', $accountIds)->where('is_active', true);
        
        // If not admin, restrict to own accounts
        if (!in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            $query->where('user_id', Auth::id());
        }
        
        $accounts = $query->get();
        $balances = [];
        
        foreach ($accounts as $account) {
            try {
                $exchange = new \App\Services\ExchangeService($account);
                $balanceData = $exchange->fetchBalance();
                
                if (empty($balanceData)) {
                    $balances[$account->id] = 'API Error/Blocked';
                } else {
                    $totalBal = 0;
                    
                    // 1. Check MetaApi / MT5 structure
                    if (isset($balanceData['equity']) && is_numeric($balanceData['equity']) && $balanceData['equity'] > 0) {
                        $totalBal = floatval($balanceData['equity']);
                    } elseif (isset($balanceData['free']['USD'])) {
                        $totalBal = floatval($balanceData['free']['USD']);
                    } elseif (isset($balanceData['total']['USD'])) {
                        $totalBal = floatval($balanceData['total']['USD']);
                    }
                    // 2. Check CCXT standard structure
                    elseif (isset($balanceData['USDT']['free']) || isset($balanceData['USD']['free'])) {
                        $usdt = floatval($balanceData['USDT']['free'] ?? 0);
                        $usd = floatval($balanceData['USD']['free'] ?? 0);
                        $totalBal = $usdt + $usd;
                    } elseif (isset($balanceData['total']['USDT']) || isset($balanceData['total']['USD'])) {
                        $usdt = floatval($balanceData['total']['USDT'] ?? 0);
                        $usd = floatval($balanceData['total']['USD'] ?? 0);
                        $totalBal = $usdt + $usd;
                    } elseif (isset($balanceData['free']['USDT'])) {
                        $totalBal = floatval($balanceData['free']['USDT']);
                    }
                    // 3. Fallback: Check first positive numeric balance
                    else {
                        foreach (['USDT', 'USD', 'INR', 'BTC', 'ETH'] as $curr) {
                            if (isset($balanceData[$curr]['free']) && is_numeric($balanceData[$curr]['free']) && $balanceData[$curr]['free'] > 0) {
                                $totalBal = floatval($balanceData[$curr]['free']);
                                break;
                            }
                        }
                    }

                    $balances[$account->id] = number_format($totalBal, 2);
                }
            } catch (\Exception $e) {
                $balances[$account->id] = 'Error/API limits';
            }
        }

        return response()->json(['balances' => $balances]);
    }
}
