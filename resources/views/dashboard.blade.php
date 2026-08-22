@extends('layouts.app')

@section('title', 'Portfolio Dashboard')

@section('content')
<div class="container" style="padding-top: 3rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <div>
            <h1 style="font-size: 2.5rem; margin: 0;">Portfolio <span class="text-gradient">Overview</span></h1>
            <p class="text-secondary">Welcome back, {{ Auth::user()->name }}. Here is your algorithmic performance.</p>
        </div>
        <a href="{{ route('bots.create') }}" class="btn btn-primary">+ Launch Bot</a>
    </div>

    <!-- Top Metrics Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div class="glass-panel" style="padding: 1.5rem; border-left: 4px solid var(--accent-neon);">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Allocated Capital</h4>
            <div style="font-size: 2rem; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${{ number_format($totalCapital, 2) }}">${{ number_format($totalCapital, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Across {{ $activeBotsCount }} running bots</div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem; border-left: 4px solid var(--accent-green);">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Realized PnL</h4>
            <div style="font-size: 2rem; font-weight: 700; color: {{ $runningPnl >= 0 ? 'var(--accent-green)' : 'var(--accent-red)' }}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $runningPnl >= 0 ? '+' : '' }}${{ number_format($runningPnl, 2) }}">
                {{ $runningPnl >= 0 ? '+' : '' }}${{ number_format($runningPnl, 2) }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Based on closed trades</div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem; border-left: 4px solid var(--accent-orange);">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Unrealized PnL</h4>
            <div id="dashboard-upnl" style="font-size: 2rem; font-weight: 700; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fas fa-circle-notch fa-spin" style="font-size: 1.5rem;"></i>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Live from open positions</div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem; border-left: 4px solid #b388ff;">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Win Rate</h4>
            <div style="font-size: 2rem; font-weight: 700; color: #b388ff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ number_format($winRate, 1) }}%">{{ number_format($winRate, 1) }}%</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Based on {{ $closedTradesCount }} closed trades</div>
        </div>
    </div>

    <div class="dashboard-main-grid">
        <!-- Chart Section -->
        <div class="glass-panel" style="padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">Growth Trajectory</h3>
                <span class="text-secondary" style="font-size: 0.875rem;">Last 30 Days</span>
            </div>
            <!-- Canvas for Chart.js -->
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="portfolioChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="glass-panel" style="padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem; margin-top: 0;">Recent Executions</h3>
            
            @if(isset($recentTrades) && $recentTrades->count() > 0)
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @foreach($recentTrades as $trade)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: var(--radius-md); border: 1px solid var(--border-glass);">
                            <div>
                                <div style="font-weight: 600; font-size: 1.1rem;">
                                    <span style="color: {{ $trade->side === 'BUY' ? 'var(--accent-green)' : 'var(--accent-red)' }};">
                                        {{ $trade->side }}
                                    </span> 
                                    {{ $trade->symbol }}
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    Bot #{{ str_pad($trade->bot_instance_id, 4, '0', STR_PAD_LEFT) }} • {{ $trade->executed_at ? $trade->executed_at->diffForHumans() : $trade->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div style="text-align: right; font-family: monospace; font-size: 1.1rem;">
                                ${{ number_format($trade->price, 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top: 1.5rem; text-align: center;">
                    <a href="{{ route('trades.index') }}" style="color: var(--accent-neon); text-decoration: none; font-size: 0.9rem;">View All Trades →</a>
                </div>
            @else
                <div style="text-align: center; padding: 2rem 0; color: var(--text-secondary);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">💤</div>
                    <p>No recent trades</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('portfolioChart').getContext('2d');
    
    // Create a beautiful neon gradient for the area chart fill
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0, 240, 255, 0.5)'); // Neon blue start
    gradient.addColorStop(1, 'rgba(0, 240, 255, 0.0)'); // Transparent end

    // Dynamic data from Controller
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
                pointBackgroundColor: '#121212',
                pointBorderColor: '#00f0ff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4 // Smooth curves
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#00f0ff',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(0, 240, 255, 0.3)',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.5)'
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.5)',
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });

    // Fetch Live Unrealized PNL
    function fetchDashboardUpnl() {
        fetch('{{ route('trades.live_pnl') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                all_open: true
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
            document.getElementById('dashboard-upnl').innerHTML = '<span style="color: var(--text-secondary); font-size: 1rem;">Error</span>';
        });
    }

    // Fetch immediately, then every 10 seconds
    fetchDashboardUpnl();
    setInterval(fetchDashboardUpnl, 10000);
});
</script>
@endsection
