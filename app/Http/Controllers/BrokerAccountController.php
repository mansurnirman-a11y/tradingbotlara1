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
            'server_name' => 'nullable|string|max:100',
            'account_label' => 'required|string|max:100',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'bridge_url' => 'nullable|string',
        ]);

        $metaAccountId = null;

        // Auto-provision Cloud MT4/MT5 account only if broker is MetaTrader and NO local/VPS bridge_url was given
        if (in_array($validated['broker'], ['mt4', 'mt5']) && empty($validated['bridge_url'])) {
            try {
                $provisionResult = \App\Services\MetaApiBridgeService::provisionAccount(
                    $validated['account_label'],
                    $validated['api_key'] ?? '',
                    $validated['api_secret'] ?? '',
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
        
        $query = BrokerAccount::where('is_active', true);
        if (!empty($accountIds)) {
            $query->whereIn('id', $accountIds);
        }
        
        // If not admin, restrict to own accounts
        if (!in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            $query->where('user_id', Auth::id());
        }
        
        $accounts = $query->get();
        $balances = [];
        $currencies = [];
        $details = [];
        
        foreach ($accounts as $account) {
            try {
                $exchange = new \App\Services\ExchangeService($account);
                $balInfo = $exchange->fetchBalanceDetails();
                $balances[$account->id] = number_format($balInfo['free'], 2);
                $currencies[$account->id] = $balInfo['currency'];
                $details[$account->id] = [
                    'balance' => $balInfo['free'],
                    'total' => $balInfo['total'],
                    'currency' => $balInfo['currency'],
                    'formatted' => $balInfo['formatted'],
                ];
            } catch (\Throwable $e) {
                $balances[$account->id] = 'Error';
                $currencies[$account->id] = 'USD';
                $details[$account->id] = [
                    'balance' => 0,
                    'total' => 0,
                    'currency' => 'USD',
                    'formatted' => 'Error/API limits',
                ];
            }
        }

        return response()->json([
            'balances' => $balances,
            'currencies' => $currencies,
            'details' => $details,
        ]);
    }
}
