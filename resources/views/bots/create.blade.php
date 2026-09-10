@extends('layouts.app')

@section('title', 'Launch Bot')

@section('content')
<div class="container" style="padding-top: 3rem; max-width: 800px;">
    <div style="margin-bottom: 2rem;">
        <a href="{{ route('bots.index') }}" class="text-secondary" style="text-decoration: none; display: inline-block; margin-bottom: 1rem;">← Back to Bots</a>
        <h1 style="font-size: 2.5rem; margin: 0;">Launch <span class="text-gradient">Trading Bot</span></h1>
        <p class="text-secondary">Configure your algorithm parameters below</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" style="background: rgba(255, 61, 0, 0.1); color: var(--accent-red); border: 1px solid rgba(255, 61, 0, 0.2);">
            <ul style="margin-left: 1.5rem; padding: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="glass-panel" style="padding: 2.5rem;">
        <form method="POST" action="{{ route('bots.store') }}">
            @csrf

            @if(isset($isAdmin) && $isAdmin && isset($users) && count($users) > 0)
                <div style="background: linear-gradient(135deg, rgba(255, 171, 0, 0.08), rgba(255, 215, 0, 0.03)); border: 1px solid rgba(255, 171, 0, 0.3); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.75rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="background: #ffab00; color: #000; font-size: 0.75rem; font-weight: 800; padding: 0.2rem 0.6rem; border-radius: 4px; text-transform: uppercase;">👑 Superadmin Control</span>
                            <span style="font-weight: 600; color: #fff; font-size: 0.95rem;">Deploy Bot For Client / User</span>
                        </div>
                        <span style="font-size: 0.75rem; color: #ffab00;">Elevated Access</span>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-weight: 600; color: #ffd54f;">Select Target User / Client</label>
                        <select name="user_id" id="admin_target_user_select" class="form-input" style="background: rgba(0,0,0,0.6); border-color: rgba(255, 171, 0, 0.4); color: #fff;">
                            <option value="{{ Auth::id() }}">👤 Myself ({{ Auth::user()->name }} - {{ Auth::user()->email }})</option>
                            @foreach($users as $u)
                                @if($u->id !== Auth::id())
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }}) — Role: {{ strtoupper($u->role) }}</option>
                                @endif
                            @endforeach
                        </select>
                        <small class="text-secondary" style="display: block; margin-top: 0.35rem; font-size: 0.75rem;">
                            Selecting a client dynamically loads their linked broker accounts and assigns this bot to their portfolio.
                        </small>
                    </div>

                    <div id="no_broker_warning" style="display: none; margin-top: 0.75rem; padding: 0.6rem 0.9rem; background: rgba(255, 61, 0, 0.15); border: 1px solid rgba(255, 61, 0, 0.3); border-radius: 6px; color: #ff8a80; font-size: 0.8rem;">
                        ⚠️ <strong>Warning:</strong> Selected client has no active broker accounts connected. Please connect a broker account for this user first.
                    </div>
                </div>
            @endif

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Select Broker Account</label>
                    <select name="broker_account_id" class="form-input" required style="background: rgba(0,0,0,0.5);">
                        <option value="">-- Choose Connection --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->account_label }} ({{ strtoupper(str_replace('_', ' ', $acc->broker)) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Algorithm Strategy</label>
                    <select name="strategy_id" class="form-input" required style="background: rgba(0,0,0,0.5);">
                        <option value="">-- Select Strategy --</option>
                        @foreach($strategies as $strat)
                            <option value="{{ $strat->id }}" data-class-name="{{ $strat->class_name }}">
                                {{ $strat->name }} 
                                @if($strat->type === 'webhook') (TradingView Webhook) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <label class="form-label" style="margin-bottom: 0;">Trading Pair (Symbol)</label>
                        <span id="symbol_type_badge" style="font-size: 0.72rem; color: var(--accent-green); background: rgba(0,230,118,0.1); padding: 0.15rem 0.4rem; border-radius: 4px;">Crypto</span>
                    </div>
                    <select id="symbol_select" class="form-input" style="background: rgba(0,0,0,0.5);">
                        <optgroup label="🔥 Popular Crypto (Binance / Delta)">
                            <option value="BTC/USDT" selected>BTC/USDT (Bitcoin)</option>
                            <option value="ETH/USDT">ETH/USDT (Ethereum)</option>
                            <option value="SOL/USDT">SOL/USDT (Solana)</option>
                            <option value="BNB/USDT">BNB/USDT (Binance Coin)</option>
                            <option value="XRP/USDT">XRP/USDT (Ripple)</option>
                            <option value="DOGE/USDT">DOGE/USDT (Dogecoin)</option>
                            <option value="ADA/USDT">ADA/USDT (Cardano)</option>
                            <option value="AVAX/USDT">AVAX/USDT (Avalanche)</option>
                        </optgroup>
                        <optgroup label="🌍 Forex & Commodities (MT5 / Oanda)">
                            <option value="XAUUSD">XAUUSD (Gold / US Dollar)</option>
                            <option value="EURUSD">EURUSD (Euro / US Dollar)</option>
                            <option value="GBPUSD">GBPUSD (British Pound / US Dollar)</option>
                            <option value="USDJPY">USDJPY (US Dollar / Japanese Yen)</option>
                            <option value="AUDUSD">AUDUSD (Australian Dollar / USD)</option>
                            <option value="USDCAD">USDCAD (US Dollar / Canadian Dollar)</option>
                            <option value="USDCHF">USDCHF (US Dollar / Swiss Franc)</option>
                            <option value="NZDUSD">NZDUSD (New Zealand Dollar / USD)</option>
                            <option value="XAGUSD">XAGUSD (Silver / US Dollar)</option>
                            <option value="USOIL">USOIL (Crude Oil / WTI)</option>
                        </optgroup>
                        <optgroup label="✏️ Custom / Other Symbol">
                            <option value="__CUSTOM__">➕ Enter Custom Symbol Manually...</option>
                        </optgroup>
                    </select>

                    <input type="text" name="symbol" id="symbol_input" class="form-input" required placeholder="Type custom pair, e.g. LTCUSDT or GBPJPY" value="BTC/USDT" style="display: none; margin-top: 0.5rem;">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Candle Timeframe</label>
                    <select name="timeframe" class="form-input" required style="background: rgba(0,0,0,0.5);">
                        <option value="1m">1 Minute</option>
                        <option value="5m">5 Minutes</option>
                        <option value="15m" selected>15 Minutes</option>
                        <option value="1h">1 Hour</option>
                        <option value="4h">4 Hours</option>
                        <option value="1d">1 Day</option>
                    </select>
                </div>
            </div>

            <h3 style="margin: 2rem 0 1rem 0; font-size: 1.25rem; border-bottom: 1px solid var(--border-glass); padding-bottom: 0.5rem;">Risk Management</h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Allocated Margin / Capital (USDT)</label>
                    <input type="number" step="0.01" name="allocated_capital" id="allocated_capital_input" class="form-input" required placeholder="e.g., 100" value="100.00">
                    <small class="text-secondary" style="display: block; margin-top: 0.25rem; font-size: 0.75rem;">Your initial wallet margin allocated for this bot</small>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Max Drawdown Limit (%)</label>
                    <input type="number" step="0.1" name="max_drawdown_pct" class="form-input" required placeholder="e.g., 5.0" value="5.0">
                    <small class="text-secondary" style="display: block; margin-top: 0.25rem; font-size: 0.75rem;">Bot stops if loss exceeds this %</small>
                </div>
            </div>

            <!-- Leverage Adjustment Setting -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label class="form-label" style="margin-bottom: 0; font-weight: 600;">Trading Leverage (Multiplier)</label>
                    <span id="leverage_display_badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-size: 0.8rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px; border: 1px solid rgba(0, 230, 118, 0.3);">25x Selected</span>
                </div>
                
                <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                    <button type="button" class="btn-lev" data-lev="1" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">1x (Spot)</button>
                    <button type="button" class="btn-lev" data-lev="5" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">5x</button>
                    <button type="button" class="btn-lev" data-lev="10" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">10x</button>
                    <button type="button" class="btn-lev active" data-lev="25" style="background: var(--accent-green); border: 1px solid var(--accent-green); color: #000; font-weight: 700; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">25x (Default)</button>
                    <button type="button" class="btn-lev" data-lev="50" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">50x</button>
                    <button type="button" class="btn-lev" data-lev="100" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">100x</button>
                </div>
                
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="position: relative; max-width: 140px;">
                        <input type="number" step="1" min="1" max="500" name="leverage" id="leverage_input" class="form-input" required placeholder="e.g., 25" value="25" style="padding-right: 2rem;">
                        <span style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 0.85rem; font-weight: 600;">x</span>
                    </div>
                    <small class="text-secondary" style="font-size: 0.75rem;">Choose a preset above or type custom leverage (1x to 500x).</small>
                </div>
            </div>

            <!-- Dynamic Futures Position & Lot Size Preview Card -->
            <div id="position_preview_card" style="background: rgba(0, 230, 118, 0.04); border: 1px solid rgba(0, 230, 118, 0.2); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; position: relative; overflow: hidden;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span id="preview_top_badge" style="background: var(--accent-green); color: #000; font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 4px;">25x LEVERAGE</span>
                        <span style="font-size: 0.9rem; font-weight: 600; color: #fff;">Futures Position & Lot Estimation</span>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--text-secondary);">Calculated Live</span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.2rem;">Margin Used</div>
                        <div id="preview_margin" style="font-size: 1.1rem; font-weight: 700; color: #fff;">$100.00</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.2rem;">Futures Multiplier</div>
                        <div id="preview_multiplier_val" style="font-size: 1.1rem; font-weight: 700; color: var(--accent-green);">25x</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.2rem;">Total Trade Buying Power</div>
                        <div id="preview_buying_power" style="font-size: 1.1rem; font-weight: 700; color: var(--accent-green);">$2,500.00</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.2rem;">Est. Lot / Order Size</div>
                        <div id="preview_lot_size" style="font-size: 1.1rem; font-weight: 700; color: #64b5f6;">~0.0318 BTC</div>
                    </div>
                </div>
                <div style="margin-top: 0.75rem; font-size: 0.72rem; color: rgba(255,255,255,0.6); display: flex; align-items: center; gap: 0.4rem;">
                    <span>ℹ️</span> <span>Bot will automatically deploy <strong id="preview_lev_desc">25x leveraged lot size</strong> on your connected broker.</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Take Profit (%)</label>
                    <input type="number" step="0.1" name="take_profit_pct" class="form-input" required placeholder="e.g., 3.0" value="3.0">
                    <small class="text-secondary" style="display: block; margin-top: 0.25rem; font-size: 0.75rem;">Trade closes in profit at this %</small>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Stop Loss (%)</label>
                    <input type="number" step="0.1" name="stop_loss_pct" class="form-input" required placeholder="e.g., 1.5" value="1.5">
                    <small class="text-secondary" style="display: block; margin-top: 0.25rem; font-size: 0.75rem;">Trade closes in loss at this %</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.125rem; padding: 1rem;">
                🚀 Deploy Bot Instance
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userAccountsMap = @json($userAccountsMap ?? []);
    const adminUserSelect = document.getElementById('admin_target_user_select');
    const brokerSelect = document.querySelector('select[name="broker_account_id"]');
    const noBrokerWarning = document.getElementById('no_broker_warning');

    function renderBrokerOptions(accounts) {
        if (!brokerSelect) return;
        brokerSelect.innerHTML = '<option value="">-- Choose Connection --</option>';
        
        if (accounts && accounts.length > 0) {
            if (noBrokerWarning) noBrokerWarning.style.display = 'none';
            accounts.forEach(acc => {
                const opt = document.createElement('option');
                opt.value = acc.id;
                opt.textContent = acc.label;
                brokerSelect.appendChild(opt);
            });
            brokerSelect.disabled = false;
            brokerSelect.style.opacity = '1';
            
            if (accounts.length === 1) {
                brokerSelect.selectedIndex = 1;
                brokerSelect.dispatchEvent(new Event('change'));
            }
        } else {
            if (noBrokerWarning) noBrokerWarning.style.display = 'block';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '❌ No Active Broker Connected for this User';
            brokerSelect.appendChild(opt);
            brokerSelect.disabled = true;
            brokerSelect.style.opacity = '0.6';
        }
    }

    async function loadUserBrokers(userId) {
        if (!userId) return;
        
        // 1. Try instant preloaded data first
        const key = String(userId);
        let accounts = userAccountsMap && userAccountsMap[key] ? userAccountsMap[key] : null;

        if (accounts && accounts.length > 0) {
            renderBrokerOptions(accounts);
            return;
        }

        // 2. Fallback to live server AJAX fetch
        try {
            brokerSelect.innerHTML = '<option value="">⏳ Loading broker connections...</option>';
            const res = await fetch(`/admin/users/${userId}/broker-accounts`);
            if (res.ok) {
                accounts = await res.json();
                renderBrokerOptions(accounts || []);
            } else {
                renderBrokerOptions([]);
            }
        } catch (err) {
            console.error('Failed to load user broker accounts:', err);
            renderBrokerOptions([]);
        }
    }

    if (adminUserSelect && brokerSelect) {
        adminUserSelect.addEventListener('change', function() {
            loadUserBrokers(this.value);
        });

        // If a client was already selected or on initial page load
        if (adminUserSelect.value) {
            loadUserBrokers(adminUserSelect.value);
        }
    }

    const strategySelect = document.querySelector('select[name="strategy_id"]');
    const tpInput = document.querySelector('input[name="take_profit_pct"]');
    const slInput = document.querySelector('input[name="stop_loss_pct"]');
    
    const originalTp = "3.0";
    const originalSl = "1.5";

    function toggleRiskFields() {
        if (!strategySelect) return;
        const selectedOption = strategySelect.options[strategySelect.selectedIndex];
        if (!selectedOption) return;

        const className = selectedOption.getAttribute('data-class-name');

        if (className === 'App\\Strategies\\EmaCrossoverStrategy') {
            tpInput.value = '0.0';
            slInput.value = '0.0';
            tpInput.readOnly = true;
            slInput.readOnly = true;
            tpInput.style.opacity = '0.5';
            slInput.style.opacity = '0.5';
            tpInput.style.cursor = 'not-allowed';
            slInput.style.cursor = 'not-allowed';
        } else if (className === 'App\\Strategies\\SessionSweepFvgStrategy') {
            tpInput.value = '2.5';
            slInput.value = '1.0';
            tpInput.readOnly = false;
            slInput.readOnly = false;
            tpInput.style.opacity = '1';
            slInput.style.opacity = '1';
            tpInput.style.cursor = 'auto';
            slInput.style.cursor = 'auto';
            const tfSelect = document.querySelector('select[name="timeframe"]');
            if (tfSelect) tfSelect.value = '15m';
        } else {
            if (tpInput.value === '0.0' || tpInput.value === '0') {
                tpInput.value = originalTp;
            }
            if (slInput.value === '0.0' || slInput.value === '0') {
                slInput.value = originalSl;
            }
            tpInput.readOnly = false;
            slInput.readOnly = false;
            tpInput.style.opacity = '1';
            slInput.style.opacity = '1';
            tpInput.style.cursor = 'auto';
            slInput.style.cursor = 'auto';
        }
    }

    if (strategySelect) {
        strategySelect.addEventListener('change', toggleRiskFields);
        if (strategySelect.value) {
            toggleRiskFields();
        }
    }

    // Dynamic Leverage & Buying Power Calculation Logic
    const capitalInput = document.getElementById('allocated_capital_input');
    const leverageInput = document.getElementById('leverage_input');
    const leverageButtons = document.querySelectorAll('.btn-lev');
    const leverageBadge = document.getElementById('leverage_display_badge');
    const topLevBadge = document.getElementById('preview_top_badge');
    const previewMultVal = document.getElementById('preview_multiplier_val');
    const previewLevDesc = document.getElementById('preview_lev_desc');
    
    // Symbol Select & Custom Input Logic
    const symbolSelect = document.getElementById('symbol_select');
    const symbolInput = document.getElementById('symbol_input');
    const symbolTypeBadge = document.getElementById('symbol_type_badge');

    const previewMargin = document.getElementById('preview_margin');
    const previewBuyingPower = document.getElementById('preview_buying_power');
    const previewLotSize = document.getElementById('preview_lot_size');

    function updateSymbolState() {
        const val = symbolSelect.value;
        if (val === '__CUSTOM__') {
            symbolInput.style.display = 'block';
            symbolInput.focus();
            if (symbolTypeBadge) {
                symbolTypeBadge.textContent = 'Custom';
                symbolTypeBadge.style.color = '#64b5f6';
                symbolTypeBadge.style.background = 'rgba(100, 181, 246, 0.1)';
            }
        } else {
            symbolInput.style.display = 'none';
            symbolInput.value = val;
            
            const isForex = val.includes('EUR') || val.includes('GBP') || val.includes('AUD') || val.includes('JPY') || val.includes('CAD') || val.includes('CHF') || val.includes('NZD') || val.includes('XAU') || val.includes('XAG') || val.includes('OIL') || (val.includes('USD') && !val.includes('BTC') && !val.includes('ETH') && !val.includes('SOL') && !val.includes('USDT'));
            if (symbolTypeBadge) {
                symbolTypeBadge.textContent = isForex ? 'Forex / Gold' : 'Crypto';
                symbolTypeBadge.style.color = isForex ? '#ffab00' : 'var(--accent-green)';
                symbolTypeBadge.style.background = isForex ? 'rgba(255, 171, 0, 0.1)' : 'rgba(0, 230, 118, 0.1)';
            }
        }
        updateLivePreview();
    }

    if (symbolSelect) {
        symbolSelect.addEventListener('change', updateSymbolState);
    }

    if (symbolInput) {
        symbolInput.addEventListener('input', updateLivePreview);
    }

    // Auto-select smart pair when broker account changes
    if (brokerSelect) {
        brokerSelect.addEventListener('change', function() {
            const optText = this.options[this.selectedIndex] ? this.options[this.selectedIndex].text.toUpperCase() : '';
            if (optText.includes('MT5') || optText.includes('MT4') || optText.includes('OANDA')) {
                if (symbolSelect.value === 'BTC/USDT') {
                    symbolSelect.value = 'XAUUSD';
                    updateSymbolState();
                }
            } else if (optText.includes('BINANCE') || optText.includes('DELTA')) {
                if (symbolSelect.value === 'XAUUSD' || symbolSelect.value === 'EURUSD') {
                    symbolSelect.value = 'BTC/USDT';
                    updateSymbolState();
                }
            }
        });
    }

    // Preset button click handler
    leverageButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const lev = parseFloat(this.getAttribute('data-lev'));
            leverageInput.value = lev;
            updateLeverageButtons(lev);
            updateLivePreview();
        });
    });

    function updateLeverageButtons(currentLev) {
        leverageButtons.forEach(btn => {
            const btnLev = parseFloat(btn.getAttribute('data-lev'));
            if (btnLev === currentLev) {
                btn.style.background = 'var(--accent-green)';
                btn.style.color = '#000';
                btn.style.fontWeight = '700';
                btn.style.borderColor = 'var(--accent-green)';
            } else {
                btn.style.background = 'rgba(255,255,255,0.05)';
                btn.style.color = '#fff';
                btn.style.fontWeight = 'normal';
                btn.style.borderColor = 'rgba(255,255,255,0.1)';
            }
        });
    }

    function updateLivePreview() {
        const capital = parseFloat(capitalInput.value) || 0;
        let leverage = parseFloat(leverageInput.value) || 1;
        if (leverage < 1) leverage = 1;

        const buyingPower = capital * leverage;
        const rawSym = (symbolInput ? symbolInput.value : 'BTC/USDT').toUpperCase();

        if (leverageBadge) leverageBadge.textContent = leverage + 'x Selected';
        if (topLevBadge) topLevBadge.textContent = leverage + 'x LEVERAGE';
        if (previewMultVal) previewMultVal.textContent = leverage + 'x';
        if (previewLevDesc) previewLevDesc.textContent = leverage + 'x leveraged lot size';

        if (previewMargin) previewMargin.textContent = '$' + capital.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (previewBuyingPower) previewBuyingPower.textContent = '$' + buyingPower.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Dynamic price estimation based on symbol
        let approxPrice = 78500.0;
        let isForex = false;

        if (rawSym.includes('ETH')) {
            approxPrice = 2650.0;
        } else if (rawSym.includes('SOL')) {
            approxPrice = 150.0;
        } else if (rawSym.includes('EUR') || rawSym.includes('GBP') || rawSym.includes('AUD') || rawSym.includes('JPY') || rawSym.includes('XAU') || rawSym.includes('OIL')) {
            isForex = true;
        } else if (rawSym.includes('BTC')) {
            approxPrice = 78500.0;
        }

        if (previewLotSize) {
            if (isForex) {
                // 1 Standard Lot = 100,000 Units (For Gold 1 lot = 100 oz approx $265,000)
                const contractSize = rawSym.includes('XAU') ? 100 : 100000;
                let lots = (buyingPower / (rawSym.includes('XAU') ? (approxPrice * 0.01) : contractSize)).toFixed(2);
                if (rawSym.includes('XAU')) lots = (buyingPower / 265000).toFixed(2);
                previewLotSize.textContent = `~${lots} Lots`;
            } else {
                const coinQty = (buyingPower / approxPrice).toFixed(4);
                const baseCoin = rawSym.split('/')[0] || 'BTC';
                previewLotSize.textContent = `~${coinQty} ${baseCoin}`;
            }
        }
    }

    if (capitalInput) capitalInput.addEventListener('input', updateLivePreview);
    if (leverageInput) {
        leverageInput.addEventListener('input', function() {
            const lev = parseFloat(this.value) || 1;
            updateLeverageButtons(lev);
            updateLivePreview();
        });
    }

    updateSymbolState();
    updateLivePreview();
});
</script>
@endsection
