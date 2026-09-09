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
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="text-align: right; font-family: monospace; font-size: 1.1rem;">
                                    ${{ number_format($trade->price, 2) }}
                                </div>
                                @if(in_array(Auth::user()->role ?? '', ['admin', 'superadmin']))
                                <form method="POST" action="{{ route('trades.record.force_delete', $trade->id) }}" onsubmit="return confirm('⚠️ Delete this trade execution record from database?');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Force Delete Ghost Trade Record" style="background: rgba(255,0,80,0.1); border: 1px solid rgba(255,0,80,0.3); color: #ff0050; padding: 0.35rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem;" onmouseover="this.style.background='rgba(255,0,80,0.25)'" onmouseout="this.style.background='rgba(255,0,80,0.1)'">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                @endif
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

    <!-- Active Open Positions & Ghost Trades Management (Superadmin Controls) -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-glass);">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <h3 style="margin: 0; font-size: 1.3rem;">⚡ Live Open Positions & Ghost Trades <span class="text-gradient">(Superadmin Controls)</span></h3>
            <span style="background: rgba(0, 240, 255, 0.12); color: var(--accent-neon); border: 1px solid rgba(0, 240, 255, 0.3); padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">
                {{ isset($openPositions) ? $openPositions->count() : 0 }} Active
            </span>
        </div>
        @if(isset($openPositions) && $openPositions->count() > 0)
        <form action="{{ route('trades.close_all') }}" method="POST" onsubmit="return confirm('⚠️ WARNING: FORCE CLOSE ALL active positions across all users?');" style="margin: 0;">
            @csrf
            <button type="submit" style="background: rgba(255, 60, 60, 0.15); color: var(--accent-red); border: 1px solid rgba(255, 60, 60, 0.4); padding: 0.4rem 0.9rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                🛑 Close All Live Positions
            </button>
        </form>
        @endif
    </div>

    <div class="glass-panel" style="padding: 2rem; margin-bottom: 3rem;">
        @if(isset($openPositions) && $openPositions->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.05); border-bottom: 1px solid var(--border-glass);">
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Opened At</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">User</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Bot ID</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Broker</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Pair & Type</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Entry Price</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Margin</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($openPositions as $pos)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <td style="padding: 1rem; color: var(--text-secondary); font-size: 0.85rem;">
                                {{ $pos->opened_at ? $pos->opened_at->format('M d, H:i:s') : '-' }}
                            </td>
                            <td style="padding: 1rem;">
                                <strong>{{ $pos->user->name ?? 'Unknown' }}</strong><br>
                                <span style="font-size: 0.75rem; color: var(--text-secondary);">{{ $pos->user->email ?? '' }}</span>
                            </td>
                            <td style="padding: 1rem; font-family: monospace;">
                                <span style="background: rgba(255,255,255,0.05); padding: 0.2rem 0.5rem; border-radius: 4px;">#{{ str_pad($pos->bot_instance_id, 4, '0', STR_PAD_LEFT) }}</span>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="color: var(--accent-neon); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                    {{ $pos->botInstance->brokerAccount->broker ?? 'Broker' }}
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <strong>{{ $pos->symbol }}</strong><br>
                                @if($pos->side === 'LONG')
                                    <span style="color: var(--accent-green); font-size: 0.75rem; font-weight: 700;">BUY / LONG</span>
                                @else
                                    <span style="color: var(--accent-red); font-size: 0.75rem; font-weight: 700;">SELL / SHORT</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; font-family: monospace; font-size: 0.95rem;">
                                ${{ number_format($pos->entry_price, 2) }}
                            </td>
                            <td style="padding: 1rem;">
                                <strong>${{ number_format($pos->margin_used ?? 0, 2) }}</strong>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                                    <form action="{{ route('trades.close', $pos->id) }}" method="POST" onsubmit="return confirm('Close position on exchange at market price?');" style="margin: 0;">
                                        @csrf
                                        <button type="submit" style="background: rgba(255, 60, 60, 0.1); color: var(--accent-red); border: 1px solid rgba(255, 60, 60, 0.3); padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                                            Close
                                        </button>
                                    </form>

                                    <form action="{{ route('trades.force_delete', $pos->id) }}" method="POST" onsubmit="return confirm('🚨 DANGER: Force delete this ghost position record from database?\n\nThis will remove the stuck/ghost trade immediately without calling the broker API.');" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Force Delete Ghost Position" style="background: rgba(255, 0, 80, 0.2); border: 1px solid rgba(255, 0, 80, 0.6); color: #ff0050; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;" onmouseover="this.style.background='rgba(255,0,80,0.35)'" onmouseout="this.style.background='rgba(255,0,80,0.2)'">
                                            <i class="fas fa-trash-alt"></i> Delete Ghost
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
            <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">✨</div>
                <p>No open positions or ghost trades currently active.</p>
            </div>
        @endif
    </div>

    <!-- Global Bot Instances Management -->
    <h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-glass);">All Trading Bots (Global Controls)</h3>
    <div class="glass-panel" style="padding: 2rem; margin-bottom: 3rem;">
        @if(isset($allBots) && $allBots->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.05); border-bottom: 1px solid var(--border-glass);">
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">User</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Bot ID</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Broker</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Pair & Strategy</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Capital / Leverage</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Status</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500; text-align: right;">Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allBots as $bot)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <td style="padding: 1rem;">
                                <strong>{{ $bot->user->name ?? 'Unknown' }}</strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ $bot->user->email ?? '' }}</span>
                            </td>
                            <td style="padding: 1rem; font-family: monospace;">#{{ str_pad($bot->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td style="padding: 1rem;">
                                {{ $bot->brokerAccount->account_label ?? 'N/A' }}<br>
                                <span style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">{{ $bot->brokerAccount->broker ?? '' }}</span>
                            </td>
                            <td style="padding: 1rem;">
                                <strong>{{ $bot->symbol }}</strong> ({{ $bot->timeframe }})<br>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ class_basename($bot->strategy_class) }}</span>
                            </td>
                            <td style="padding: 1rem;">
                                ${{ number_format($bot->allocated_capital, 2) }}<br>
                                <span style="font-size: 0.8rem; color: var(--accent-neon);">{{ $bot->parameters['leverage'] ?? 25 }}x</span>
                            </td>
                            <td style="padding: 1rem;">
                                @if($bot->status === 'running')
                                    <span style="color: var(--accent-neon); background: rgba(0, 240, 255, 0.1); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem; font-weight: 600;">● Running</span>
                                @else
                                    <span style="color: var(--text-secondary); background: rgba(255, 255, 255, 0.05); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">⏸ Stopped</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                                    <form method="POST" action="{{ route('bots.toggle', $bot) }}" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn {{ $bot->status === 'running' ? 'btn-secondary' : 'btn-primary' }}" style="font-size: 0.8rem; padding: 0.45rem 1rem; border-radius: 6px; font-weight: 600;">
                                            {{ $bot->status === 'running' ? '⏸ Pause' : '▶ Start' }}
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
            <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <p>No trading bots created on the platform yet.</p>
            </div>
        @endif
    </div>

    <!-- User Directory -->
    <h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-glass);">Platform Users</h3>
    <div class="glass-panel" style="padding: 2rem;">
        <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">User Management</h2>
        
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
                        <th style="padding: 1rem;">Connected Brokers / Live Balance</th>
                        <th style="padding: 1rem;">Total Bots</th>
                        <th style="padding: 1rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <td style="padding: 1rem;">
                                <strong>{{ $u->name }}</strong>
                                @if($u->role === 'superadmin')
                                    <span style="color: var(--accent-red); font-weight: bold; font-size: 0.8rem; margin-left: 0.5rem;">[SUPERADMIN]</span>
                                @endif
                            </td>
                            <td style="padding: 1rem;">{{ $u->email }}</td>
                            <td style="padding: 1rem; min-width: 260px;">
                                @if($u->brokerAccounts && $u->brokerAccounts->count() > 0)
                                    <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                                        @foreach($u->brokerAccounts as $acc)
                                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); padding: 0.35rem 0.6rem; border-radius: 6px; font-size: 0.85rem;">
                                                <div>
                                                    <span style="color: var(--accent-neon); font-weight: 600; text-transform: uppercase; font-size: 0.75rem;">{{ str_replace('_', ' ', $acc->broker) }}</span>
                                                    <span style="color: var(--text-secondary); font-size: 0.75rem;">({{ $acc->account_label }})</span>
                                                </div>
                                                <div>
                                                    @if($acc->is_active)
                                                        <span class="user-live-balance-cell" data-account-id="{{ $acc->id }}">
                                                            <span style="font-size: 0.75rem; color: var(--text-secondary);"><i class="fas fa-spinner fa-spin"></i> Fetching...</span>
                                                        </span>
                                                    @else
                                                        <span style="color: var(--accent-red); font-size: 0.75rem;">Inactive</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color: var(--text-secondary); font-size: 0.85rem; font-style: italic;">No broker connected</span>
                                @endif
                            </td>
                            <td style="padding: 1rem;">
                                <span style="background: rgba(255,255,255,0.05); padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 600;">{{ $u->bot_instances_count }}</span>
                            </td>
                            <td style="padding: 1rem;">
                                @if($u->id !== Auth::id())
                                <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                                    <form method="POST" action="{{ route('admin.users.update', $u->id) }}" style="display: flex; gap: 0.5rem; align-items: center;">
                                        @csrf
                                        <select name="is_active" class="form-input" style="width: auto; padding: 0.5rem;">
                                            <option value="1" {{ $u->is_active ? 'selected' : '' }}>Approved</option>
                                            <option value="0" {{ !$u->is_active ? 'selected' : '' }}>Suspended</option>
                                        </select>
                                        <input type="number" name="max_bots" value="{{ $u->max_bots }}" class="form-input" style="width: 70px; padding: 0.5rem;" title="Max Bots Limit">
                                        <button type="submit" class="btn btn-outline" style="padding: 0.5rem 1rem;">Save</button>
                                    </form>
                                    @if(in_array(Auth::user()->role ?? '', ['superadmin', 'admin']) && $u->role !== 'superadmin')
                                    <form method="POST" action="{{ route('admin.users.delete', $u->id) }}"
                                          onsubmit="return confirm('🚨 WARNING: Delete user \'{{ addslashes($u->name) }}\'?\n\nThis will PERMANENTLY delete:\n• All their bots\n• All positions & trade history\n• All broker accounts\n\nThis action CANNOT be undone. Are you absolutely sure?');"
                                          style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                style="background: rgba(255,0,80,0.1); border: 1px solid rgba(255,0,80,0.4); color: #ff0050; padding: 0.4rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; width: 100%; transition: all 0.2s;"
                                                onmouseover="this.style.background='rgba(255,0,80,0.25)'"
                                                onmouseout="this.style.background='rgba(255,0,80,0.1)'">
                                            <i class="fas fa-user-times" style="margin-right: 0.3rem;"></i> Delete Account
                                        </button>
                                    </form>
                                    @endif
                                </div>
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

    // Fetch User Live Balances for Superadmin Platform Users table
    function fetchUserLiveBalances() {
        const balanceCells = document.querySelectorAll('.user-live-balance-cell');
        if (balanceCells.length === 0) return;

        const accountIds = Array.from(balanceCells).map(cell => cell.dataset.accountId);
        if (accountIds.length === 0) return;

        fetch('{{ route('brokers.live-balances') }}?account_ids[]=' + accountIds.join('&account_ids[]='))
        .then(res => res.json())
        .then(data => {
            if (data.balances) {
                balanceCells.forEach(cell => {
                    const accId = cell.dataset.accountId;
                    if (data.balances[accId] !== undefined) {
                        const bal = data.balances[accId];
                        if (bal === 'Error/API limits' || bal === 'API Error/Blocked' || bal === 'Error') {
                            cell.innerHTML = `<span style="color: var(--accent-red); font-size: 0.75rem;" title="${bal}">Error</span>`;
                        } else {
                            cell.innerHTML = `<strong style="color: var(--accent-green); font-size: 0.85rem;">$${bal}</strong> <span style="font-size: 0.7rem; color: var(--text-secondary);">USDT</span>`;
                        }
                    }
                });
            }
        })
        .catch(err => console.error("Error fetching user live balances:", err));
    }

    fetchUserLiveBalances();
    setInterval(fetchUserLiveBalances, 30000);
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
