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
            'account_label' => 'required|string|max:100',
            'api_key' => 'nullable|string|required_unless:broker,oanda,custom_api',
            'api_secret' => 'nullable|string|required_unless:broker,oanda,custom_api',
            'bridge_url' => 'nullable|url|required_if:broker,oanda,custom_api,mt4,mt5',
        ]);

        // In a real scenario, we would inject CCXT here and ping the API to verify the keys are valid before saving.
        // E.g., CCXT\binance(['apiKey' => $validated['api_key'], 'secret' => $validated['api_secret']])->fetchBalance();

        BrokerAccount::create([
            'user_id' => Auth::id(),
            'broker' => $validated['broker'],
            'account_label' => $validated['account_label'],
            'api_key' => $validated['api_key'] ?? null, // Automatically encrypted by Model cast
            'api_secret' => $validated['api_secret'] ?? null, // Automatically encrypted by Model cast
            'bridge_url' => $validated['bridge_url'] ?? null,
            'is_active' => true,
        ]);

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
                    $usdtFree = $balanceData['USDT']['free'] ?? ($balanceData['total']['USDT'] ?? 0);
                    $usdFree = $balanceData['USD']['free'] ?? ($balanceData['total']['USD'] ?? 0);
                    $balances[$account->id] = is_numeric($usdtFree + $usdFree) ? number_format($usdtFree + $usdFree, 2) : 'Error';
                }
            } catch (\Exception $e) {
                $balances[$account->id] = 'Error/API limits';
            }
        }

        return response()->json(['balances' => $balances]);
    }
}
