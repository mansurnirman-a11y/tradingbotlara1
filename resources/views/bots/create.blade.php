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
                    <label class="form-label">Trading Pair (Symbol)</label>
                    <input type="text" name="symbol" class="form-input" required placeholder="e.g., BTC/USDT" value="BTC/USDT">
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

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Allocated Capital (USDT)</label>
                    <input type="number" step="0.01" name="allocated_capital" class="form-input" required placeholder="e.g., 500" value="100.00">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Max Drawdown Limit (%)</label>
                    <input type="number" step="0.1" name="max_drawdown_pct" class="form-input" required placeholder="e.g., 5.0" value="5.0">
                    <small class="text-secondary" style="display: block; margin-top: 0.25rem; font-size: 0.75rem;">Bot stops if loss exceeds this %</small>
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
    const strategySelect = document.querySelector('select[name="strategy_id"]');
    const tpInput = document.querySelector('input[name="take_profit_pct"]');
    const slInput = document.querySelector('input[name="stop_loss_pct"]');
    
    const originalTp = "3.0";
    const originalSl = "1.5";

    function toggleRiskFields() {
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

    strategySelect.addEventListener('change', toggleRiskFields);
    
    if (strategySelect.value) {
        toggleRiskFields();
    }
});
</script>
@endsection
