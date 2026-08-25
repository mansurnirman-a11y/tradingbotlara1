@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container" style="padding-top: 3rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="font-size: 2.5rem; margin: 0;">Trading <span class="text-gradient">Dashboard</span></h1>
        <p class="text-secondary">Welcome back, {{ Auth::user()->name }}</p>
    </div>

    @if(session('success'))
        <div class="alert" style="background: rgba(0, 230, 118, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.2);">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        <!-- Add Broker Account Form -->
        <div class="glass-panel" style="padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Connect Broker</h3>
            
            <form method="POST" action="{{ route('brokers.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Broker</label>
                    <select name="broker" class="form-input" required style="background: rgba(0,0,0,0.5);">
                        <option value="binance">Binance (Spot/Futures)</option>
                        <option value="delta_india">Delta Exchange India</option>
                        <option value="mt4">MetaTrader 4</option>
                        <option value="mt5">MetaTrader 5</option>
                        <option value="oanda">Oanda</option>
                        <option value="custom_api">Custom Broker API</option>
                    </select>
                </div>

                <div class="form-group" id="bridge-url-group" style="display: none;">
                    <label class="form-label">API/Bridge URL <span style="color: var(--accent-red);">*</span></label>
                    <input type="url" name="bridge_url" id="bridge_url" class="form-input" placeholder="e.g. http://localhost:8000 or https://api.custombroker.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Account Label</label>
                    <input type="text" name="account_label" class="form-input" required placeholder="e.g. My Binance Main">
                </div>

                <div class="form-group">
                    <label class="form-label">API Key <span class="api-key-required" style="color: var(--accent-red);">*</span></label>
                    <input type="password" name="api_key" id="api_key" class="form-input" placeholder="Paste your API Key">
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label class="form-label">API Secret <span class="api-secret-required" style="color: var(--accent-red);">*</span></label>
                    <input type="password" name="api_secret" id="api_secret" class="form-input" placeholder="Paste your API Secret">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Connect Account</button>
            </form>
        </div>

        <!-- Connected Accounts List -->
        <div class="glass-panel" style="padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Connected Accounts</h3>
            
            @if(isset($accounts) && $accounts->count() > 0)
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-glass);">
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Broker</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Label</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Status</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Added</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $acc)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                                <td style="padding: 1rem; text-transform: capitalize;">{{ str_replace('_', ' ', $acc->broker) }}</td>
                                <td style="padding: 1rem;">{{ $acc->account_label }}</td>
                                <td style="padding: 1rem;">
                                    @if($acc->is_active)
                                        <span style="color: var(--accent-green); background: rgba(0,230,118,0.1); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">Active</span>
                                    @else
                                        <span style="color: var(--accent-red); background: rgba(255,61,0,0.1); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">Inactive</span>
                                    @endif
                                </td>
                                <td style="padding: 1rem; color: var(--text-secondary);">{{ $acc->created_at->format('M d, Y') }}</td>
                                <td style="padding: 1rem;">
                                    <form action="{{ route('brokers.destroy', $acc->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this broker account? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: none; border: none; color: var(--accent-red); cursor: pointer; display: flex; align-items: center; gap: 0.5rem;" title="Delete Broker">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 3rem 1rem; border: 1px dashed var(--border-glass); border-radius: var(--radius-md);">
                    <p class="text-secondary">No broker accounts connected yet.</p>
                    <p class="text-secondary" style="font-size: 0.875rem; margin-top: 0.5rem;">Use the form to securely link your exchange.</p>
                </div>
            @endif
        </div>
    </div>

    @if(isset($allBrokerAccounts))
    <div style="margin-top: 3rem;">
        <h3 style="margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-glass);">Global Connected Brokers (Superadmin Only)</h3>
        
        <div class="glass-panel" style="padding: 2rem;">
            @if($allBrokerAccounts->count() > 0)
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-glass);">
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">User</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Broker</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Label</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Balance</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Status</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allBrokerAccounts as $acc)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                                <td style="padding: 1rem;">
                                    <strong>{{ $acc->user->name }}</strong><br>
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ $acc->user->email }}</span>
                                </td>
                                <td style="padding: 1rem; text-transform: capitalize;">{{ str_replace('_', ' ', $acc->broker) }}</td>
                                <td style="padding: 1rem;">{{ $acc->account_label }}</td>
                                <td style="padding: 1rem; font-weight: bold;">
                                    @if($acc->is_active)
                                        <span class="live-balance-cell" data-account-id="{{ $acc->id }}">
                                            <div style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fas fa-spinner fa-spin"></i> Fetching...</div>
                                        </span>
                                    @else
                                        <span style="color: var(--text-secondary);">-</span>
                                    @endif
                                </td>
                                <td style="padding: 1rem;">
                                    @if($acc->is_active)
                                        <span style="color: var(--accent-green); background: rgba(0,230,118,0.1); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">Active</span>
                                    @else
                                        <span style="color: var(--accent-red); background: rgba(255,61,0,0.1); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">Inactive</span>
                                    @endif
                                </td>
                                <td style="padding: 1rem; color: var(--text-secondary);">{{ $acc->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 2rem;">
                    <p class="text-secondary">No users have connected any broker accounts yet.</p>
                </div>
            @endif
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const balanceCells = document.querySelectorAll('.live-balance-cell');
        if (balanceCells.length === 0) return;

        const accountIds = Array.from(balanceCells).map(cell => cell.dataset.accountId);

        function fetchLiveBalances() {
            fetch('{{ route('brokers.live-balances') }}?account_ids[]=' + accountIds.join('&account_ids[]='))
            .then(res => res.json())
            .then(data => {
                if(data.balances) {
                    balanceCells.forEach(cell => {
                        const accId = cell.dataset.accountId;
                        if (data.balances[accId]) {
                            const bal = data.balances[accId];
                            if (bal === 'Error/API limits' || bal === 'API Error/Blocked' || bal === 'Error') {
                                cell.innerHTML = `<span style="color: var(--accent-red); font-size: 0.9rem;">${bal}</span>`;
                            } else {
                                cell.innerHTML = `<span style="color: var(--accent-green);">$${bal}</span> <span style="font-size: 0.75rem; color: var(--text-secondary);">USDT</span>`;
                            }
                        }
                    });
                }
            })
            .catch(err => console.error("Error fetching live balances:", err));
        }

        // Fetch immediately, then every 30 seconds to avoid hitting API rate limits
        fetchLiveBalances();
        setInterval(fetchLiveBalances, 30000);
    });
    </script>
    @endif

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const brokerSelect = document.querySelector('select[name="broker"]');
        const bridgeUrlGroup = document.getElementById('bridge-url-group');
        const bridgeUrlInput = document.getElementById('bridge_url');
        const apiKeyInput = document.getElementById('api_key');
        const apiSecretInput = document.getElementById('api_secret');
        const apiKeyRequiredStar = document.querySelector('.api-key-required');
        const apiSecretRequiredStar = document.querySelector('.api-secret-required');

        function toggleBrokerFields() {
            const val = brokerSelect.value;
            if (['mt4', 'mt5', 'oanda', 'custom_api'].includes(val)) {
                bridgeUrlGroup.style.display = 'block';
                bridgeUrlInput.setAttribute('required', 'required');
            } else {
                bridgeUrlGroup.style.display = 'none';
                bridgeUrlInput.removeAttribute('required');
                bridgeUrlInput.value = '';
            }

            if (['oanda', 'custom_api'].includes(val)) {
                // Key/Secret are optional for local/custom connections
                apiKeyInput.removeAttribute('required');
                apiSecretInput.removeAttribute('required');
                if (apiKeyRequiredStar) apiKeyRequiredStar.style.display = 'none';
                if (apiSecretRequiredStar) apiSecretRequiredStar.style.display = 'none';
            } else {
                // Required for binance, delta_india, mt4, mt5
                apiKeyInput.setAttribute('required', 'required');
                apiSecretInput.setAttribute('required', 'required');
                if (apiKeyRequiredStar) apiKeyRequiredStar.style.display = 'inline';
                if (apiSecretRequiredStar) apiSecretRequiredStar.style.display = 'inline';
            }
        }

        if (brokerSelect) {
            brokerSelect.addEventListener('change', toggleBrokerFields);
            toggleBrokerFields(); // Run on load
        }
    });
    </script>
</div>
@endsection
