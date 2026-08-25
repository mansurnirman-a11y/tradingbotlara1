@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container" style="padding-top: 3rem;">
    
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2.5rem; margin: 0;">Portfolio <span class="text-gradient">Overview</span></h1>
            <p class="text-secondary">Welcome back, {{ Auth::user()->name }}. Here is your algorithmic performance.</p>
        </div>
        <!-- ACTIONS -->
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="{{ route('admin.strategies') }}" class="btn btn-outline" style="padding: 1rem 2rem; font-size: 1.1rem; border-color: #b388ff; color: #b388ff;">
                ⚙️ STRATEGY MANAGEMENT
            </a>
            
            <a href="{{ route('admin.import') }}" class="btn btn-outline" style="padding: 1rem 2rem; font-size: 1.1rem; border-color: #00f0ff; color: #00f0ff;">
                📥 IMPORT TV HISTORY
            </a>
            
            <form method="POST" action="{{ route('admin.killswitch') }}" onsubmit="return confirm('⚠️ DANGER: This will instantly pause ALL active trading bots across the entire platform. Are you absolutely sure?');" style="margin: 0;">
                @csrf
                <button type="submit" class="btn" style="background: rgba(255, 61, 0, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); font-size: 1.1rem; padding: 1rem 2rem; box-shadow: 0 0 20px rgba(255, 61, 0, 0.4);">
                    🛑 GLOBAL KILL-SWITCH
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert" style="background: rgba(255, 61, 0, 0.1); color: var(--accent-red); border: 1px solid rgba(255, 61, 0, 0.5); font-weight: bold; text-align: center; padding: 1.5rem; font-size: 1.2rem;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert" style="background: rgba(255, 61, 0, 0.1); color: var(--accent-red); border: 1px solid rgba(255, 61, 0, 0.5); font-weight: bold; text-align: center; padding: 1.5rem; font-size: 1.2rem; margin-top: 1rem;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <!-- Platform Stats Grid -->
    <h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-glass);">Platform Overview</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="glass-panel" style="padding: 1.5rem; text-align: center; border-color: rgba(0, 240, 255, 0.3);">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Registered Users</h4>
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--accent-neon);">{{ number_format($metrics['totalUsers']) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">{{ $metrics['activeUsers'] }} Active Accounts</div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem; text-align: center; border-color: rgba(255, 61, 0, 0.3);">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Total Admins</h4>
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--accent-red);">{{ number_format($metrics['totalAdmins']) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Managing the platform</div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem; text-align: center; border-color: rgba(0, 230, 118, 0.3);">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Total Created Bots</h4>
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--accent-green);">{{ number_format($metrics['totalBots']) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">{{ $metrics['activeBotsCount'] }} Currently Running</div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem; text-align: center;">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Total Executed Trades</h4>
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--text-primary);">{{ number_format($metrics['closedTradesCount']) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Across all time</div>
        </div>
    </div>

    <!-- Global Financial Metrics Grid -->
    <h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-glass);">Financial Overview</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
        <div class="glass-panel" style="padding: 1.5rem; border-left: 4px solid var(--accent-neon);">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Allocated Capital</h4>
            <div style="font-size: 2rem; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${{ number_format($metrics['totalCapital'], 2) }}">${{ number_format($metrics['totalCapital'], 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Across {{ $metrics['activeBotsCount'] }} running bots globally</div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem; border-left: 4px solid var(--accent-green);">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Realized PnL</h4>
            <div style="font-size: 2rem; font-weight: 700; color: {{ $metrics['runningPnl'] >= 0 ? 'var(--accent-green)' : 'var(--accent-red)' }}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $metrics['runningPnl'] >= 0 ? '+' : '' }}${{ number_format($metrics['runningPnl'], 2) }}">
                {{ $metrics['runningPnl'] >= 0 ? '+' : '' }}${{ number_format($metrics['runningPnl'], 2) }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Global from closed trades</div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem; border-left: 4px solid var(--accent-orange);">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Unrealized PnL</h4>
            <div id="dashboard-upnl" style="font-size: 2rem; font-weight: 700; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fas fa-circle-notch fa-spin" style="font-size: 1.5rem;"></i>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Live from ALL open positions</div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem; border-left: 4px solid #b388ff;">
            <h4 class="text-secondary" style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Win Rate</h4>
            <div style="font-size: 2rem; font-weight: 700; color: #b388ff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ number_format($metrics['winRate'], 1) }}%">{{ number_format($metrics['winRate'], 1) }}%</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Based on {{ $metrics['closedTradesCount'] }} global trades</div>
        </div>
    </div>

    <!-- Main Dashboard Grid (Chart + Recent Activity) -->
    <div class="dashboard-main-grid" style="margin-bottom: 3rem;">
        <!-- Chart Section -->
        <div class="glass-panel" style="padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">Global Growth Trajectory</h3>
                <span class="text-secondary" style="font-size: 0.875rem;">Last 30 Days</span>
            </div>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="portfolioChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="glass-panel" style="padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem; margin-top: 0;">Recent Global Executions</h3>
            
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
                                    <strong>{{ $trade->user->name ?? 'Unknown' }}</strong> • Bot #{{ str_pad($trade->bot_instance_id, 4, '0', STR_PAD_LEFT) }} • {{ $trade->executed_at ? $trade->executed_at->diffForHumans() : $trade->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div style="text-align: right; font-family: monospace; font-size: 1.1rem;">
                                ${{ number_format($trade->price, 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 2rem 0; color: var(--text-secondary);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">💤</div>
                    <p>No recent trades on the platform</p>
                </div>
            @endif
        </div>
    </div>

    <!-- User Directory -->
    <h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-glass);">Platform Users</h3>
    <div class="glass-panel" style="padding: 2rem;">
        <h2 style="font-size: 1.5rem; margin-top: 2rem; margin-bottom: 1rem;">User Management</h2>
        
        @if(session('success'))
            <div class="alert" style="background: rgba(0, 230, 118, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.2); margin-bottom: 1rem;">
                {{ session('success') }}
            </div>
        @endif

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; background: rgba(255, 255, 255, 0.02); border-radius: 8px; overflow: hidden;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.05); border-bottom: 1px solid var(--border-glass);">
                        <th style="padding: 1rem;">Name</th>
                        <th style="padding: 1rem;">Email</th>
                        <th style="padding: 1rem;">Total Bots</th>
                        <th style="padding: 1rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <td style="padding: 1rem;">
                                {{ $u->name }}
                                @if($u->role === 'superadmin')
                                    <span style="color: var(--accent-red); font-weight: bold; font-size: 0.8rem; margin-left: 0.5rem;">[SUPERADMIN]</span>
                                @endif
                            </td>
                            <td style="padding: 1rem;">{{ $u->email }}</td>
                            <td style="padding: 1rem;">{{ $u->bot_instances_count }}</td>
                            <td style="padding: 1rem;">
                                @if($u->id !== Auth::id())
                                <form method="POST" action="{{ route('admin.users.update', $u->id) }}" style="display: flex; gap: 0.5rem; align-items: center;">
                                    @csrf
                                    <select name="is_active" class="form-input" style="width: auto; padding: 0.5rem;">
                                        <option value="1" {{ $u->is_active ? 'selected' : '' }}>Approved</option>
                                        <option value="0" {{ !$u->is_active ? 'selected' : '' }}>Suspended</option>
                                    </select>
                                    <input type="number" name="max_bots" value="{{ $u->max_bots }}" class="form-input" style="width: 70px; padding: 0.5rem;" title="Max Bots Limit">
                                    <button type="submit" class="btn btn-outline" style="padding: 0.5rem 1rem;">Save</button>
                                </form>
                                @else
                                    <span class="text-secondary">Cannot edit yourself</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 2rem;">
            {{ $users->links('pagination::bootstrap-4') }}
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function fetchDashboardUpnl() {
        fetch('{{ route('trades.live_pnl') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                all_open: true // In TradeController, this fetches all open trades globally for admin!
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

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('portfolioChart');
    if (!canvas) return; // Only run if chart exists
    
    const ctx = canvas.getContext('2d');
    
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
                label: 'Global Portfolio Value (USD)',
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
});
</script>
@endsection
