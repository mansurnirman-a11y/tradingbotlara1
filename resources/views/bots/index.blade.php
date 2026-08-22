@extends('layouts.app')

@section('title', 'My Bots')

@section('content')
<!-- TradingView Lightweight Charts -->
<script src="https://unpkg.com/lightweight-charts@4.2.0/dist/lightweight-charts.standalone.production.js"></script>

<div class="container" style="padding-top: 3rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2.5rem; margin: 0;">My <span class="text-gradient">Trading Bots</span></h1>
            <p class="text-secondary">Manage and monitor your automated algorithms</p>
        </div>
        <a href="{{ route('bots.create') }}" class="btn btn-primary">+ Launch New Bot</a>
    </div>

    @if(session('success'))
        <div class="alert" style="background: rgba(0, 230, 118, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.2); margin-bottom: 1rem;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert" style="background: rgba(255, 61, 0, 0.1); color: var(--accent-red); border: 1px solid rgba(255, 61, 0, 0.5); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(!Auth::user()->is_active)
        <div class="alert" style="background: rgba(255, 171, 0, 0.1); color: #ffab00; border: 1px solid rgba(255, 171, 0, 0.3); margin-bottom: 2rem;">
            <strong>Pending Approval:</strong> Your account is currently under review by an administrator. You can view the dashboard, but you cannot launch or start any trading bots until approved.
        </div>
    @else
        <div style="display: flex; gap: 1.5rem; margin-bottom: 2rem; flex-wrap: wrap;">
            <div class="glass-panel" style="padding: 1rem 1.5rem; flex: 1; min-width: 250px;">
                <div style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 0.5rem;">Bot Usage</div>
                <div style="font-size: 1.5rem;"><strong>{{ isset($bots) ? $bots->count() : 0 }}</strong> / <strong>{{ Auth::user()->max_bots }}</strong></div>
            </div>
            
            @if(isset($balances) && count($balances) > 0)
                @foreach($balances as $id => $bal)
                <div class="glass-panel" style="padding: 1rem 1.5rem; flex: 1; min-width: 250px;">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 0.5rem;">Wallet (ID #{{ $id }}) - Live Balance</div>
                    <div style="font-size: 1.5rem; color: var(--accent-green);" id="balance-{{ $id }}">
                        @if(is_numeric($bal))
                            ${{ number_format($bal, 2) }} <span style="font-size: 0.8rem; color: var(--text-secondary);">USDT</span>
                        @else
                            <span style="color: var(--accent-red); font-size: 1rem;">{{ $bal }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    @endif

    <div class="glass-panel" style="padding: 2rem;">
        @if(isset($bots) && $bots->count() > 0)
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Bot ID</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Account</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Pair & Strategy</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Capital</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Status</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500; text-align: center;">Chart</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bots as $bot)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                            <td style="padding: 1rem; font-family: monospace;">#{{ str_pad($bot->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td style="padding: 1rem;">{{ $bot->brokerAccount->account_label }}</td>
                            <td style="padding: 1rem;">
                                <strong>{{ $bot->symbol }}</strong> ({{ $bot->timeframe }})<br>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ class_basename($bot->strategy_class) }}</span>
                                @if(isset($botPrices) && isset($botPrices[$bot->id]))
                                    <div style="margin-top: 0.25rem; font-size: 0.85rem; color: var(--accent-green);" id="price-{{ $bot->id }}">
                                        <strong>${{ number_format($botPrices[$bot->id], 2) }}</strong> <span style="font-size: 0.75rem; color: var(--text-secondary);">LIVE</span>
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 1rem;">${{ number_format($bot->allocated_capital, 2) }}</td>
                            <td style="padding: 1rem;">
                                @if($bot->status === 'running')
                                    <span style="color: var(--accent-neon); background: rgba(0, 240, 255, 0.1); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">● Running</span>
                                @else
                                    <span style="color: var(--text-secondary); background: rgba(255, 255, 255, 0.05); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">⏸ Stopped</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="toggleChart({{ $bot->id }})" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; background: rgba(0, 240, 255, 0.1); color: var(--accent-neon); border-color: transparent;">
                                    📊 View
                                </button>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <form method="POST" action="{{ route('bots.toggle', $bot) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                                            {{ $bot->status === 'running' ? 'Pause' : 'Start' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('bots.destroy', $bot) }}" onsubmit="return confirm('Delete this bot instance permanently?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem; color: var(--accent-red); border-color: rgba(255, 61, 0, 0.3);">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align: center; padding: 4rem 1rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🤖</div>
                <h3 style="margin-bottom: 0.5rem;">No Active Bots</h3>
                <p class="text-secondary" style="margin-bottom: 2rem;">You haven't launched any automated trading algorithms yet.</p>
                <a href="{{ route('bots.create') }}" class="btn btn-primary">Launch Your First Bot</a>
            </div>
        @endif
    </div>

    @if(isset($allBots))
    <div style="margin-top: 3rem;">
        <h3 style="margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-glass);">Global Connected Bots (Superadmin Only)</h3>
        <div class="glass-panel" style="padding: 2rem;">
            @if($allBots->count() > 0)
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-glass);">
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">User</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Bot ID</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Broker</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Pair & Strategy</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Capital</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Status</th>
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allBots as $bot)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                                <td style="padding: 1rem;">
                                    <strong>{{ $bot->user->name ?? 'Unknown' }}</strong><br>
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ $bot->user->email ?? '' }}</span>
                                </td>
                                <td style="padding: 1rem; font-family: monospace;">#{{ str_pad($bot->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td style="padding: 1rem;">{{ $bot->brokerAccount->account_label ?? 'N/A' }}</td>
                                <td style="padding: 1rem;">
                                    <strong>{{ $bot->symbol }}</strong> ({{ $bot->timeframe }})<br>
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ class_basename($bot->strategy_class) }}</span>
                                </td>
                                <td style="padding: 1rem;">${{ number_format($bot->allocated_capital, 2) }}</td>
                                <td style="padding: 1rem;">
                                    @if($bot->status === 'running')
                                        <span style="color: var(--accent-neon); background: rgba(0, 240, 255, 0.1); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">● Running</span>
                                    @else
                                        <span style="color: var(--text-secondary); background: rgba(255, 255, 255, 0.05); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">⏸ Stopped</span>
                                    @endif
                                </td>
                                <td style="padding: 1rem; text-align: right;">
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                        <form method="POST" action="{{ route('bots.toggle', $bot) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                                                {{ $bot->status === 'running' ? 'Pause' : 'Start' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('bots.destroy', $bot) }}" onsubmit="return confirm('Delete this bot instance permanently?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; color: var(--accent-red); border-color: rgba(255, 61, 0, 0.3);">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 2rem;">
                    <p class="text-secondary">No users have created any bots yet.</p>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Separate Chart Section (Hidden by Default) -->
    <div id="global-chart-section" class="glass-panel" style="display: none; padding: 2rem; margin-top: 2rem; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <div>
                <h3 style="margin: 0;">Live Market Chart <span id="chart-bot-name" style="color: var(--accent-green); font-size: 1rem; margin-left: 0.5rem;"></span></h3>
                <p class="text-secondary" style="margin: 0; font-size: 0.85rem;">Interactive candlestick chart with strategy markings</p>
            </div>
            <button class="btn btn-outline" onclick="closeChartSection()" style="padding: 0.4rem 1rem;">Close Chart</button>
        </div>
        <div id="global-chart-container" style="width: 100%; height: 500px; position: relative; border-radius: 8px; overflow: hidden; background: rgba(0,0,0,0.2);">
            <div id="global-chart-loader" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: var(--text-secondary);">
                Select a bot to load Live Market Data...
            </div>
        </div>
    </div>
</div>

<script>
    // Real-Time WebSockets for Live Market Data & Balances
    const userId = {{ Auth::id() }};
    
    document.addEventListener('DOMContentLoaded', () => {
        if (window.Echo) {
            window.Echo.private('live-data.' + userId)
                .listen('LiveDataUpdated', (e) => {
                    console.log('Real-Time Data Received:', e);
                    
                    // Update Wallet Balances
                    if (e.balances) {
                        for (const [id, bal] of Object.entries(e.balances)) {
                            const el = document.getElementById('balance-' + id);
                            if (el) {
                                if (!isNaN(bal) && bal !== 'Error' && bal !== 'API Error' && bal !== 'Error/API limits') {
                                    el.innerHTML = '<strong>$' + bal + '</strong> <span style="font-size: 0.8rem; color: var(--text-secondary);">USDT</span>';
                                } else {
                                    el.innerHTML = '<span style="color: var(--accent-red); font-size: 1rem;">' + bal + '</span>';
                                }
                            }
                        }
                    }
                    
                    // Update Bot Market Prices
                    if (e.botPrices) {
                        for (const [botId, price] of Object.entries(e.botPrices)) {
                            const el = document.getElementById('price-' + botId);
                            if (el && price !== '---') {
                                el.innerHTML = '<strong>$' + price + '</strong> <span style="font-size: 0.75rem; color: var(--text-secondary);">LIVE</span>';
                            }
                        }
                    }
                });
        } else {
            console.error('Laravel Echo is not initialized.');
        }
    });

    // TradingView Lightweight Charts Logic
    let activeChart = null; // Single chart instance
    
    function toggleChart(botId) {
        const section = document.getElementById('global-chart-section');
        section.style.display = 'block';
        
        // Scroll smoothly to the chart section
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        loadChart(botId);
    }

    function closeChartSection() {
        document.getElementById('global-chart-section').style.display = 'none';
        if (activeChart) {
            activeChart.remove();
            activeChart = null;
        }
        document.getElementById('chart-bot-name').innerText = '';
        document.getElementById('global-chart-loader').style.display = 'block';
        document.getElementById('global-chart-loader').innerText = 'Select a bot to load Live Market Data...';
    }

    async function loadChart(botId) {
        const container = document.getElementById('global-chart-container');
        const loader = document.getElementById('global-chart-loader');
        
        // Cleanup previous chart if exists
        if (activeChart) {
            activeChart.remove();
            activeChart = null;
        }

        loader.style.display = 'block';
        loader.innerText = 'Loading Live Market Data...';

        try {
            const response = await fetch(`/bots/${botId}/chart-data`);
            const data = await response.json();
            
            if (!data.success) {
                loader.innerText = 'Failed to load chart data: ' + data.message;
                return;
            }

            loader.style.display = 'none';
            document.getElementById('chart-bot-name').innerText = '(Bot #' + String(botId).padStart(4, '0') + ')';

            // Initialize Lightweight Chart
            activeChart = LightweightCharts.createChart(container, {
                width: container.clientWidth || 800,
                height: 500,
                layout: {
                    background: { type: 'solid', color: 'transparent' },
                    textColor: '#d1d4dc',
                },
                grid: {
                    vertLines: { color: 'rgba(42, 46, 57, 0.5)' },
                    horzLines: { color: 'rgba(42, 46, 57, 0.5)' },
                },
                timeScale: {
                    timeVisible: true,
                    secondsVisible: false,
                },
            });

            const candlestickSeries = activeChart.addCandlestickSeries({
                upColor: '#00e676',
                downColor: '#ff3d00',
                borderDownColor: '#ff3d00',
                borderUpColor: '#00e676',
                wickDownColor: '#ff3d00',
                wickUpColor: '#00e676',
            });

            // Convert UTC timestamp to Local pseudo-UTC timestamp for TradingView
            function timeToLocal(originalTime) {
                const d = new Date(originalTime * 1000);
                return Date.UTC(d.getFullYear(), d.getMonth(), d.getDate(), d.getHours(), d.getMinutes(), d.getSeconds()) / 1000;
            }

            // Map all timestamps to local time
            data.candles.forEach(c => c.time = timeToLocal(c.time));

            if (data.position) {
                data.position.time = timeToLocal(data.position.time);
            }

            if (data.strategy && data.strategy.data) {
                if (Array.isArray(data.strategy.data)) {
                    data.strategy.data.forEach(d => d.time = timeToLocal(d.time));
                } else {
                    if (data.strategy.data.macd) data.strategy.data.macd.forEach(d => d.time = timeToLocal(d.time));
                    if (data.strategy.data.signal) data.strategy.data.signal.forEach(d => d.time = timeToLocal(d.time));
                    if (data.strategy.data.histogram) data.strategy.data.histogram.forEach(d => d.time = timeToLocal(d.time));
                    if (data.strategy.data.fast) data.strategy.data.fast.forEach(d => d.time = timeToLocal(d.time));
                    if (data.strategy.data.slow) data.strategy.data.slow.forEach(d => d.time = timeToLocal(d.time));
                }
            }

            // Set Data
            candlestickSeries.setData(data.candles);

            // Add Position Lines if active
            let allMarkers = [];

            if (data.position) {
                candlestickSeries.createPriceLine({
                    price: data.position.entry,
                    color: '#00f0ff',
                    lineWidth: 2,
                    lineStyle: LightweightCharts.LineStyle.Solid,
                    axisLabelVisible: true,
                    title: 'ENTRY',
                });

                candlestickSeries.createPriceLine({
                    price: data.position.tp,
                    color: '#00e676',
                    lineWidth: 2,
                    lineStyle: LightweightCharts.LineStyle.Dashed,
                    axisLabelVisible: true,
                    title: 'TARGET',
                });

                candlestickSeries.createPriceLine({
                    price: data.position.sl,
                    color: '#ff3d00',
                    lineWidth: 2,
                    lineStyle: LightweightCharts.LineStyle.Dashed,
                    axisLabelVisible: true,
                    title: 'LOSS',
                });

                allMarkers.push({
                    time: data.position.time,
                    position: data.position.side === 'LONG' ? 'belowBar' : 'aboveBar',
                    color: data.position.side === 'LONG' ? '#00e676' : '#ff3d00',
                    shape: data.position.side === 'LONG' ? 'arrowUp' : 'arrowDown',
                    text: data.position.side + ' Executed'
                });
            }

            // Render Strategy Graph
            let strategyMarkers = [];
            
            if (data.strategy && data.strategy.data) {
                const sType = data.strategy.type;
                
                // Collect signals if any
                if (data.strategy.signals) {
                    strategyMarkers = data.strategy.signals;
                    strategyMarkers.forEach(m => m.time = timeToLocal(m.time));
                }
                
                if (sType === 'RSI') {
                    const rsiSeries = activeChart.addLineSeries({
                        color: '#00f0ff',
                        lineWidth: 2,
                        priceScaleId: 'rsi',
                        title: 'RSI (14)',
                    });
                    activeChart.priceScale('rsi').applyOptions({
                        scaleMargins: { top: 0.75, bottom: 0 },
                    });
                    // Adjust main price scale so it doesn't overlap
                    activeChart.priceScale('right').applyOptions({
                        scaleMargins: { top: 0.1, bottom: 0.3 },
                    });
                    rsiSeries.setData(data.strategy.data);
                    
                    rsiSeries.createPriceLine({ price: 70, color: '#ff3d00', lineWidth: 1, lineStyle: 2 });
                    rsiSeries.createPriceLine({ price: 30, color: '#00e676', lineWidth: 1, lineStyle: 2 });

                } else if (sType === 'MACD') {
                    const histSeries = activeChart.addHistogramSeries({
                        priceScaleId: 'macd',
                        title: 'MACD Hist',
                    });
                    const macdSeries = activeChart.addLineSeries({
                        color: '#00f0ff', lineWidth: 2, priceScaleId: 'macd', title: 'MACD Line',
                    });
                    const signalSeries = activeChart.addLineSeries({
                        color: '#ff3d00', lineWidth: 2, priceScaleId: 'macd', title: 'Signal Line',
                    });
                    activeChart.priceScale('macd').applyOptions({
                        scaleMargins: { top: 0.75, bottom: 0 },
                    });
                    activeChart.priceScale('right').applyOptions({
                        scaleMargins: { top: 0.1, bottom: 0.3 },
                    });

                    histSeries.setData(data.strategy.data.histogram);
                    macdSeries.setData(data.strategy.data.macd);
                    signalSeries.setData(data.strategy.data.signal);

                } else if (sType === 'SMA') {
                    const fastSeries = activeChart.addLineSeries({ color: '#00f0ff', lineWidth: 2, title: 'Fast SMA' });
                    const slowSeries = activeChart.addLineSeries({ color: '#ff3d00', lineWidth: 2, title: 'Slow SMA' });
                    fastSeries.setData(data.strategy.data.fast);
                    slowSeries.setData(data.strategy.data.slow);

                } else if (sType === 'BOLLINGER') {
                    const upperSeries = activeChart.addLineSeries({ color: 'rgba(255, 61, 0, 0.7)', lineWidth: 1, title: 'BB Upper' });
                    const middleSeries = activeChart.addLineSeries({ color: 'rgba(209, 212, 220, 0.5)', lineWidth: 1, title: 'BB Mid' });
                    const lowerSeries = activeChart.addLineSeries({ color: 'rgba(0, 230, 118, 0.7)', lineWidth: 1, title: 'BB Lower' });
                    upperSeries.setData(data.strategy.data.upper);
                    middleSeries.setData(data.strategy.data.middle);
                    lowerSeries.setData(data.strategy.data.lower);
                }
            }

            // Apply all markers
            allMarkers = allMarkers.concat(strategyMarkers);
            // Markers must be sorted by time
            allMarkers.sort((a, b) => a.time - b.time);
            if (allMarkers.length > 0) {
                candlestickSeries.setMarkers(allMarkers);
            }

            activeChart.timeScale().fitContent();

            // Handle Resize
            const resizeObserver = new ResizeObserver(entries => {
                if (entries.length === 0 || entries[0].target !== container || !activeChart) return;
                const newRect = entries[0].contentRect;
                activeChart.applyOptions({ width: newRect.width, height: newRect.height });
            });
            resizeObserver.observe(container);

        } catch (err) {
            console.error(err);
            loader.style.display = 'block';
            loader.innerText = 'Error: ' + (err.message || err);
        }
    }
</script>
@endsection
