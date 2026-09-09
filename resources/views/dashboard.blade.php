@extends('layouts.app')

@section('title', 'Portfolio Dashboard - Capital First')

@section('content')
<style>
/* ==========================================================================
   USER DASHBOARD MODERN STYLING
   ========================================================================== */
.user-kpi-card {
    background: rgba(18, 26, 38, 0.65);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 1.4rem;
    position: relative;
    overflow: hidden;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.user-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
    border-color: rgba(0, 240, 255, 0.25);
}

.user-kpi-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, transparent, rgba(0, 240, 255, 0.4), transparent);
}

.user-kpi-card.kpi-neon::after { background: linear-gradient(90deg, transparent, #00f0ff, transparent); }
.user-kpi-card.kpi-green::after { background: linear-gradient(90deg, transparent, #00e676, transparent); }
.user-kpi-card.kpi-orange::after { background: linear-gradient(90deg, transparent, #ff9100, transparent); }
.user-kpi-card.kpi-purple::after { background: linear-gradient(90deg, transparent, #b388ff, transparent); }

.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.kpi-label {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--text-secondary);
}

.kpi-number {
    font-size: 2.1rem;
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.5px;
    margin-bottom: 0.4rem;
}

.kpi-meta {
    font-size: 0.75rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.user-trade-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 1rem;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    transition: all 0.2s ease;
}

.user-trade-item:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(0, 240, 255, 0.2);
    transform: translateX(2px);
}

.pulse-dot-cyan {
    width: 8px;
    height: 8px;
    background: var(--accent-neon);
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 10px var(--accent-neon);
    animation: pulse-dot-c 2s infinite;
}

@keyframes pulse-dot-c {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 240, 255, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(0, 240, 255, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 240, 255, 0); }
}
</style>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem; max-width: 1400px;">
    
    <!-- Page Header & Action -->
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1.5rem; margin-bottom: 2rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                <span class="pulse-dot-cyan"></span>
                <span style="font-size: 0.75rem; color: var(--accent-neon); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">
                    LIVE PORTFOLIO TELEMETRY
                </span>
            </div>
            <h1 style="font-size: 2.3rem; margin: 0; font-weight: 800; letter-spacing: -0.5px; color: #fff;">
                Portfolio <span class="text-gradient">Overview</span>
            </h1>
            <p class="text-secondary" style="margin-top: 0.35rem; font-size: 0.95rem; margin-bottom: 0;">
                Welcome back, <strong style="color: #fff;">{{ Auth::user()->name }}</strong>. Here is your real-time algorithmic trading performance.
            </p>
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="{{ route('trades.index') }}" class="glass-panel" style="padding: 0.65rem 1.15rem; font-size: 0.85rem; font-weight: 700; border-radius: 10px; border-color: rgba(0, 240, 255, 0.4); color: #00f0ff; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='rgba(0, 240, 255, 0.15)'" onmouseout="this.style.background='transparent'">
                <i class="fas fa-stream" style="color: #00f0ff;"></i> Positions Ledger
            </a>
            
            <a href="{{ route('bots.create') }}" class="btn btn-primary" style="padding: 0.65rem 1.25rem; font-size: 0.85rem; font-weight: 700; border-radius: 10px; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 0 20px rgba(0, 240, 255, 0.3);">
                <i class="fas fa-plus-circle"></i> Launch New Bot
            </a>
        </div>
    </div>

    <!-- Top Metrics Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
        
        <div class="user-kpi-card kpi-neon">
            <div class="kpi-header">
                <span class="kpi-label">Allocated Capital</span>
                <i class="fas fa-coins" style="color: var(--accent-neon); font-size: 1rem;"></i>
            </div>
            <div class="kpi-number" style="color: #fff;" title="${{ number_format($totalCapital, 2) }}">
                ${{ number_format($totalCapital, 2) }}
            </div>
            <div class="kpi-meta">
                <span style="color: var(--accent-neon); font-weight: 700;">{{ $activeBotsCount }} running</span> bot(s) active
            </div>
        </div>

        <div class="user-kpi-card kpi-green">
            <div class="kpi-header">
                <span class="kpi-label">Realized PnL</span>
                <i class="fas fa-chart-line" style="color: var(--accent-green); font-size: 1rem;"></i>
            </div>
            <div class="kpi-number" style="color: {{ $runningPnl >= 0 ? 'var(--accent-green)' : 'var(--accent-red)' }};">
                {{ $runningPnl >= 0 ? '+' : '' }}${{ number_format($runningPnl, 2) }}
            </div>
            <div class="kpi-meta">
                <i class="fas fa-check-double" style="color: var(--accent-green);"></i> Settled closed trades
            </div>
        </div>

        <div class="user-kpi-card kpi-orange">
            <div class="kpi-header">
                <span class="kpi-label">Unrealized PnL</span>
                <i class="fas fa-bolt" style="color: var(--accent-orange); font-size: 1rem;"></i>
            </div>
            <div class="kpi-number" id="dashboard-upnl" style="color: var(--text-secondary);">
                <i class="fas fa-circle-notch fa-spin" style="font-size: 1.3rem;"></i>
            </div>
            <div class="kpi-meta">
                <i class="fas fa-sync-alt fa-spin" style="font-size: 0.7rem;"></i> Live feed from open positions
            </div>
        </div>

        <div class="user-kpi-card kpi-purple">
            <div class="kpi-header">
                <span class="kpi-label">Win Rate</span>
                <i class="fas fa-trophy" style="color: #b388ff; font-size: 1rem;"></i>
            </div>
            <div class="kpi-number" style="color: #b388ff;">
                {{ number_format($winRate, 1) }}%
            </div>
            <div class="kpi-meta">
                <i class="fas fa-history"></i> Based on {{ $closedTradesCount }} closed trades
            </div>
        </div>

    </div>

    <!-- Main Grid: Growth Chart + Recent Executions -->
    <div class="dashboard-main-grid">
        
        <!-- Growth Chart Section -->
        <div class="glass-panel" style="padding: 1.75rem; border-radius: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-chart-area" style="color: var(--accent-neon);"></i> Growth Trajectory
                    </h3>
                    <p class="text-secondary" style="font-size: 0.8rem; margin: 0.2rem 0 0 0;">Historical capital progression</p>
                </div>
                <span class="badge" style="background: rgba(0, 240, 255, 0.1); color: var(--accent-neon); border: 1px solid rgba(0, 240, 255, 0.3); padding: 0.3rem 0.7rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                    Last 30 Days
                </span>
            </div>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="portfolioChart"></canvas>
            </div>
        </div>

        <!-- Recent Executions Section -->
        <div class="glass-panel" style="padding: 1.75rem; border-radius: 14px; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-bolt" style="color: #ff9100;"></i> Recent Executions
                </h3>
                <span class="text-secondary" style="font-size: 0.75rem;">Real-time feed</span>
            </div>
            
            @if(isset($recentTrades) && $recentTrades->count() > 0)
                <div style="display: flex; flex-direction: column; gap: 0.75rem; overflow-y: auto; max-height: 290px; padding-right: 0.25rem;">
                    @foreach($recentTrades as $trade)
                        <div class="user-trade-item">
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.4rem;">
                                    <span style="background: {{ $trade->side === 'BUY' ? 'rgba(0, 230, 118, 0.15)' : 'rgba(255, 60, 60, 0.15)' }}; color: {{ $trade->side === 'BUY' ? 'var(--accent-green)' : 'var(--accent-red)' }}; border: 1px solid {{ $trade->side === 'BUY' ? 'rgba(0, 230, 118, 0.3)' : 'rgba(255, 60, 60, 0.3)' }}; padding: 0.15rem 0.45rem; border-radius: 4px; font-size: 0.72rem; font-weight: 800;">
                                        {{ $trade->side }}
                                    </span> 
                                    <span style="color: #fff;">{{ $trade->symbol }}</span>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    Bot #{{ str_pad($trade->bot_instance_id, 4, '0', STR_PAD_LEFT) }} &bull; {{ $trade->executed_at ? $trade->executed_at->diffForHumans() : $trade->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div style="text-align: right; font-family: monospace; font-size: 1rem; font-weight: 700; color: #fff;">
                                ${{ number_format($trade->price, 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top: 1.25rem; text-align: center;">
                    <a href="{{ route('trades.index') }}" style="color: var(--accent-neon); text-decoration: none; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                        View All Positions & Trades →
                    </a>
                </div>
            @else
                <div style="text-align: center; padding: 3rem 0; color: var(--text-secondary); margin: auto;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.8;">⚡</div>
                    <p style="margin: 0; font-size: 0.9rem; color: #fff; font-weight: 600;">No recent executions</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem;">Your bots will log trades here in real-time.</p>
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('portfolioChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        
        let gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(0, 240, 255, 0.4)');
        gradient.addColorStop(1, 'rgba(0, 240, 255, 0.0)');

        const labels = {!! json_encode($chartLabels ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) !!};
        const dataPoints = {!! json_encode($chartData ?? [1000, 1050, 1020, 1100, 1150, 1140, 1250]) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Portfolio Value (USD)',
                    data: dataPoints,
                    borderColor: '#00f0ff',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: '#00f0ff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#00f0ff',
                        bodyColor: '#ffffff',
                        borderColor: 'rgba(0, 240, 255, 0.3)',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return '$' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { color: 'rgba(255, 255, 255, 0.4)', font: { size: 11 } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.04)', drawBorder: false },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.4)',
                            font: { size: 11 },
                            callback: function(value) { return '$' + value; }
                        }
                    }
                }
            }
        });
    }

    // Fetch Live Unrealized PNL
    function fetchDashboardUpnl() {
        fetch('{{ route('trades.live_pnl') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                all_open: false
            })
        })
        .then(response => response.json())
        .then(data => {
            let totalUpnl = 0;
            let hasOpenPositions = false;
            
            for (const posId in data) {
                hasOpenPositions = true;
                totalUpnl += parseFloat(data[posId].pnl || 0);
            }

            const upnlContainer = document.getElementById('dashboard-upnl');
            if (!upnlContainer) return;
            
            if (!hasOpenPositions) {
                upnlContainer.innerHTML = '<span style="color: var(--text-secondary);">$0.00</span>';
                return;
            }

            if (totalUpnl > 0) {
                upnlContainer.innerHTML = `<span style="color: var(--accent-green);">+$${totalUpnl.toFixed(2)}</span>`;
            } else if (totalUpnl < 0) {
                upnlContainer.innerHTML = `<span style="color: var(--accent-red);">-$${Math.abs(totalUpnl).toFixed(2)}</span>`;
            } else {
                upnlContainer.innerHTML = `<span style="color: var(--text-secondary);">$0.00</span>`;
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard UPNL:', error);
            const upnlContainer = document.getElementById('dashboard-upnl');
            if (upnlContainer) {
                upnlContainer.innerHTML = '<span style="color: var(--text-secondary); font-size: 1.2rem;">$0.00</span>';
            }
        });
    }

    fetchDashboardUpnl();
    setInterval(fetchDashboardUpnl, 10000);
});
</script>
@endsection
