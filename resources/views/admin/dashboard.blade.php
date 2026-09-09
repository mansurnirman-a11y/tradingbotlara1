@extends('layouts.app')

@section('title', 'Superadmin Dashboard - Capital First')

@section('content')
<style>
/* ==========================================================================
   SUPERADMIN DASHBOARD MODERN STYLING
   ========================================================================== */
.admin-header-badge {
    background: linear-gradient(135deg, rgba(255, 61, 0, 0.2), rgba(255, 138, 101, 0.1));
    border: 1px solid rgba(255, 61, 0, 0.4);
    color: #ff5722;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.admin-header-badge::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #ff5722;
    box-shadow: 0 0 8px #ff5722;
}

.kpi-card {
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

.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
    border-color: rgba(0, 240, 255, 0.25);
}

.kpi-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, transparent, rgba(0, 240, 255, 0.4), transparent);
}

.kpi-card.kpi-neon::after { background: linear-gradient(90deg, transparent, #00f0ff, transparent); }
.kpi-card.kpi-green::after { background: linear-gradient(90deg, transparent, #00e676, transparent); }
.kpi-card.kpi-red::after { background: linear-gradient(90deg, transparent, #ff3d00, transparent); }
.kpi-card.kpi-purple::after { background: linear-gradient(90deg, transparent, #b388ff, transparent); }
.kpi-card.kpi-orange::after { background: linear-gradient(90deg, transparent, #ff9100, transparent); }

.kpi-title {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.kpi-value {
    font-size: 2.1rem;
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.5px;
    margin-bottom: 0.4rem;
}

.kpi-subtitle {
    font-size: 0.75rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.admin-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2.5rem;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.admin-section-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.glass-table-card {
    background: rgba(18, 26, 38, 0.6);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.admin-table thead tr {
    background: rgba(255, 255, 255, 0.04);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.admin-table th {
    padding: 0.9rem 1.2rem;
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.admin-table tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    transition: background 0.15s ease;
}

.admin-table tbody tr:hover {
    background: rgba(0, 240, 255, 0.03);
}

.admin-table td {
    padding: 1rem 1.2rem;
    font-size: 0.9rem;
    vertical-align: middle;
}

.btn-ctrl-pause {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 0.4rem 0.85rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-ctrl-pause:hover {
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.3);
}

.btn-ctrl-del {
    background: rgba(255, 60, 60, 0.12);
    color: #ff5252;
    border: 1px solid rgba(255, 60, 60, 0.35);
    padding: 0.4rem 0.85rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.btn-ctrl-del:hover {
    background: rgba(255, 60, 60, 0.28);
    border-color: #ff5252;
}

.user-badge-chip {
    background: rgba(0, 240, 255, 0.08);
    border: 1px solid rgba(0, 240, 255, 0.25);
    color: var(--accent-cyan);
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.82rem;
}

.pulse-dot-green {
    width: 8px;
    height: 8px;
    background: var(--accent-green);
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 10px var(--accent-green);
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(0, 230, 118, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0); }
}

.gov-form-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(0, 0, 0, 0.45);
    padding: 0.35rem 0.5rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.gov-select {
    background: #0f172a;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 8px;
    padding: 0.4rem 0.7rem;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    outline: none;
    transition: all 0.2s;
}

.gov-select:focus {
    border-color: var(--accent-neon);
    box-shadow: 0 0 10px rgba(0, 240, 255, 0.3);
}

.gov-limit-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 0.3rem 0.6rem;
    font-size: 0.78rem;
    color: var(--text-secondary);
}

.gov-limit-input {
    width: 42px;
    background: transparent;
    border: none;
    color: var(--accent-neon);
    font-weight: 700;
    font-size: 0.88rem;
    text-align: center;
    outline: none;
}

.gov-save-btn {
    background: linear-gradient(135deg, rgba(0, 240, 255, 0.18), rgba(0, 240, 255, 0.08));
    color: var(--accent-cyan);
    border: 1px solid rgba(0, 240, 255, 0.4);
    padding: 0.4rem 0.85rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.gov-save-btn:hover {
    background: rgba(0, 240, 255, 0.3);
    box-shadow: 0 0 12px rgba(0, 240, 255, 0.3);
    transform: translateY(-1px);
}
</style>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem; max-width: 1400px;">
    
    <!-- Header & Action Ribbon -->
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1.5rem; margin-bottom: 2rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span class="admin-header-badge">SUPERADMIN CONTROL CENTER</span>
            </div>
            <h1 style="font-size: 2.3rem; margin: 0; font-weight: 800; letter-spacing: -0.5px;">
                Platform <span class="text-gradient">Command & Telemetry</span>
            </h1>
            <p class="text-secondary" style="margin-top: 0.35rem; font-size: 0.95rem; margin-bottom: 0;">
                Live ecosystem monitoring, algorithmic risk enforcement & multi-user governance
            </p>
        </div>

        <!-- Top Action Buttons -->
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
            <a href="{{ route('admin.strategies') }}" class="glass-panel" style="padding: 0.65rem 1.15rem; font-size: 0.85rem; font-weight: 700; border-radius: 10px; border-color: rgba(179, 136, 255, 0.4); color: #d1b3ff; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='rgba(179, 136, 255, 0.15)'" onmouseout="this.style.background='transparent'">
                <i class="fas fa-brain" style="color: #b388ff;"></i> Strategies
            </a>
            
            <a href="{{ route('admin.import') }}" class="glass-panel" style="padding: 0.65rem 1.15rem; font-size: 0.85rem; font-weight: 700; border-radius: 10px; border-color: rgba(0, 240, 255, 0.4); color: #00f0ff; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='rgba(0, 240, 255, 0.15)'" onmouseout="this.style.background='transparent'">
                <i class="fas fa-file-import" style="color: #00f0ff;"></i> Import History
            </a>
            
            <a href="{{ route('trades.index') }}" class="glass-panel" style="padding: 0.65rem 1.15rem; font-size: 0.85rem; font-weight: 700; border-radius: 10px; border-color: rgba(0, 230, 118, 0.4); color: #00e676; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='rgba(0, 230, 118, 0.15)'" onmouseout="this.style.background='transparent'">
                <i class="fas fa-stream" style="color: #00e676;"></i> Positions Ledger
            </a>

            <form method="POST" action="{{ route('admin.killswitch') }}" onsubmit="return confirm('⚠️ DANGER: This will instantly pause ALL active trading bots across the entire platform. Are you absolutely sure?');" style="margin: 0;">
                @csrf
                <button type="submit" style="background: rgba(255, 61, 0, 0.15); color: #ff5252; border: 1px solid rgba(255, 61, 0, 0.5); font-size: 0.85rem; font-weight: 700; padding: 0.65rem 1.25rem; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 0 15px rgba(255, 61, 0, 0.2); transition: all 0.2s;" onmouseover="this.style.background='rgba(255, 61, 0, 0.3)'" onmouseout="this.style.background='rgba(255, 61, 0, 0.15)'">
                    <i class="fas fa-radiation"></i> Global Kill-Switch
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Banners -->
    @if(session('success'))
        <div class="alert" style="background: rgba(0, 230, 118, 0.12); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.4); font-weight: 600; border-radius: 10px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert" style="background: rgba(255, 61, 0, 0.12); color: var(--accent-red); border: 1px solid rgba(255, 61, 0, 0.4); font-weight: 600; border-radius: 10px; padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
            @foreach($errors->all() as $error)
                <div style="display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <!-- ==================================================================== -->
    <!-- 1. FINANCIAL & LIQUIDITY TELEMETRY KPI GRID                          -->
    <!-- ==================================================================== -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        
        <div class="kpi-card kpi-neon">
            <div class="kpi-title">
                <span>Allocated Capital</span>
                <i class="fas fa-coins" style="color: var(--accent-neon); font-size: 1rem;"></i>
            </div>
            <div class="kpi-value" style="color: #fff;" title="${{ number_format($metrics['totalCapital'], 2) }}">
                ${{ number_format($metrics['totalCapital'], 2) }}
            </div>
            <div class="kpi-subtitle">
                <span class="pulse-dot-green"></span> Across {{ $metrics['activeBotsCount'] }} running bots
            </div>
        </div>

        <div class="kpi-card kpi-green">
            <div class="kpi-title">
                <span>Realized PnL</span>
                <i class="fas fa-chart-line" style="color: var(--accent-green); font-size: 1rem;"></i>
            </div>
            <div class="kpi-value" style="color: {{ $metrics['runningPnl'] >= 0 ? 'var(--accent-green)' : 'var(--accent-red)' }};">
                {{ $metrics['runningPnl'] >= 0 ? '+' : '' }}${{ number_format($metrics['runningPnl'], 2) }}
            </div>
            <div class="kpi-subtitle">
                <i class="fas fa-check-double" style="color: var(--accent-green);"></i> Closed trade settlements
            </div>
        </div>

        <div class="kpi-card kpi-orange">
            <div class="kpi-title">
                <span>Unrealized PnL (Live)</span>
                <i class="fas fa-bolt" style="color: var(--accent-orange); font-size: 1rem;"></i>
            </div>
            <div class="kpi-value" id="dashboard-upnl" style="color: var(--text-secondary);">
                <i class="fas fa-circle-notch fa-spin" style="font-size: 1.3rem;"></i>
            </div>
            <div class="kpi-subtitle">
                <i class="fas fa-sync-alt fa-spin" style="font-size: 0.7rem;"></i> Live feed from open positions
            </div>
        </div>

        <div class="kpi-card kpi-purple">
            <div class="kpi-title">
                <span>Platform Win Rate</span>
                <i class="fas fa-trophy" style="color: #b388ff; font-size: 1rem;"></i>
            </div>
            <div class="kpi-value" style="color: #b388ff;">
                {{ number_format($metrics['winRate'], 1) }}%
            </div>
            <div class="kpi-subtitle">
                <i class="fas fa-history"></i> {{ $metrics['closedTradesCount'] }} historical executions
            </div>
        </div>

    </div>

    <!-- ==================================================================== -->
    <!-- 2. PLATFORM INFRASTRUCTURE STATS                                    -->
    <!-- ==================================================================== -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2.5rem;">
        
        <div class="kpi-card" style="padding: 1.1rem 1.4rem;">
            <div class="kpi-title">
                <span>Registered Users</span>
                <i class="fas fa-users" style="color: var(--accent-neon);"></i>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #fff;">
                {{ number_format($metrics['totalUsers']) }}
            </div>
            <div class="kpi-subtitle">
                <span style="color: var(--accent-green); font-weight: 700;">{{ $metrics['activeUsers'] }} Active</span> &bull; {{ $metrics['totalUsers'] - $metrics['activeUsers'] }} Pending
            </div>
        </div>

        <div class="kpi-card" style="padding: 1.1rem 1.4rem;">
            <div class="kpi-title">
                <span>Platform Admins</span>
                <i class="fas fa-user-shield" style="color: var(--accent-red);"></i>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #ff5252;">
                {{ number_format($metrics['totalAdmins']) }}
            </div>
            <div class="kpi-subtitle">
                <i class="fas fa-lock"></i> Governance authorized
            </div>
        </div>

        <div class="kpi-card" style="padding: 1.1rem 1.4rem;">
            <div class="kpi-title">
                <span>Trading Bots</span>
                <i class="fas fa-robot" style="color: var(--accent-green);"></i>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent-green);">
                {{ number_format($metrics['totalBots']) }}
            </div>
            <div class="kpi-subtitle">
                <span style="color: var(--accent-green); font-weight: 700;">{{ $metrics['activeBotsCount'] }} Running</span> &bull; {{ $metrics['totalBots'] - $metrics['activeBotsCount'] }} Stopped
            </div>
        </div>

        <div class="kpi-card" style="padding: 1.1rem 1.4rem;">
            <div class="kpi-title">
                <span>Total Executions</span>
                <i class="fas fa-exchange-alt" style="color: var(--text-secondary);"></i>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #fff;">
                {{ number_format($metrics['closedTradesCount']) }}
            </div>
            <div class="kpi-subtitle">
                <i class="fas fa-check-circle" style="color: var(--accent-green);"></i> All-time fill rate
            </div>
        </div>

    </div>

    <!-- ==================================================================== -->
    <!-- 3. CHART & RECENT ACTIVITY SECTION                                   -->
    <!-- ==================================================================== -->
    <div class="dashboard-main-grid" style="margin-bottom: 2.5rem;">
        
        <!-- Growth Chart -->
        <div class="glass-panel" style="padding: 1.75rem; border-radius: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-chart-area" style="color: var(--accent-neon);"></i> Global Growth Trajectory
                    </h3>
                    <p class="text-secondary" style="font-size: 0.8rem; margin: 0.2rem 0 0 0;">Historical capital valuation curves</p>
                </div>
                <span class="badge" style="background: rgba(0, 240, 255, 0.1); color: var(--accent-neon); border: 1px solid rgba(0, 240, 255, 0.3); padding: 0.3rem 0.7rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                    30-Day Window
                </span>
            </div>
            <div style="position: relative; height: 280px; width: 100%;">
                <canvas id="portfolioChart"></canvas>
            </div>
        </div>

        <!-- Recent Executions Stream -->
        <div class="glass-panel" style="padding: 1.75rem; border-radius: 14px; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-bolt" style="color: #ff9100;"></i> Recent Global Executions
                </h3>
                <span class="text-secondary" style="font-size: 0.75rem;">Real-time feed</span>
            </div>
            
            @if(isset($recentTrades) && $recentTrades->count() > 0)
                <div style="display: flex; flex-direction: column; gap: 0.75rem; overflow-y: auto; max-height: 280px; padding-right: 0.25rem;">
                    @foreach($recentTrades as $trade)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px solid rgba(255,255,255,0.06); transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='rgba(255,255,255,0.02)'">
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.4rem;">
                                    <span style="background: {{ $trade->side === 'BUY' ? 'rgba(0, 230, 118, 0.15)' : 'rgba(255, 60, 60, 0.15)' }}; color: {{ $trade->side === 'BUY' ? 'var(--accent-green)' : 'var(--accent-red)' }}; border: 1px solid {{ $trade->side === 'BUY' ? 'rgba(0, 230, 118, 0.3)' : 'rgba(255, 60, 60, 0.3)' }}; padding: 0.15rem 0.45rem; border-radius: 4px; font-size: 0.72rem; font-weight: 800;">
                                        {{ $trade->side }}
                                    </span> 
                                    <span style="color: #fff;">{{ $trade->symbol }}</span>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    <span style="color: var(--accent-cyan);">{{ $trade->user->name ?? 'Unknown' }}</span> &bull; Bot #{{ str_pad($trade->bot_instance_id, 4, '0', STR_PAD_LEFT) }} &bull; {{ $trade->executed_at ? $trade->executed_at->diffForHumans() : $trade->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="text-align: right; font-family: monospace; font-size: 0.95rem; font-weight: 700; color: #fff;">
                                    ${{ number_format($trade->price, 2) }}
                                </div>
                                @if(in_array(Auth::user()->role ?? '', ['admin', 'superadmin']))
                                <form method="POST" action="{{ route('trades.record.force_delete', $trade->id) }}" onsubmit="return confirm('⚠️ Delete this trade execution record from database?');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Force Delete Ghost Trade Record" style="background: rgba(255,0,80,0.1); border: 1px solid rgba(255,0,80,0.3); color: #ff0050; padding: 0.3rem 0.55rem; border-radius: 5px; cursor: pointer; font-size: 0.75rem; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,0,80,0.3)'" onmouseout="this.style.background='rgba(255,0,80,0.1)'">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 2.5rem 0; color: var(--text-secondary); margin: auto;">
                    <div style="font-size: 1.8rem; margin-bottom: 0.4rem;">⚡</div>
                    <p style="margin: 0; font-size: 0.9rem;">No recent executions logged</p>
                </div>
            @endif
        </div>

    </div>

    <!-- ==================================================================== -->
    <!-- 4. LIVE OPEN POSITIONS & GHOST TRADES SECTION                        -->
    <!-- ==================================================================== -->
    <div class="admin-section-header">
        <h3 class="admin-section-title">
            <i class="fas fa-satellite-dish" style="color: var(--accent-neon);"></i>
            Live Open Positions & Ghost Trades
            <span style="background: rgba(0, 240, 255, 0.12); color: var(--accent-neon); border: 1px solid rgba(0, 240, 255, 0.3); padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">
                {{ isset($openPositions) ? $openPositions->count() : 0 }} Active
            </span>
        </h3>
        
        @if(isset($openPositions) && $openPositions->count() > 0)
        <form action="{{ route('trades.close_all') }}" method="POST" onsubmit="return confirm('⚠️ WARNING: FORCE CLOSE ALL active positions across all users?');" style="margin: 0;">
            @csrf
            <button type="submit" style="background: rgba(255, 60, 60, 0.15); color: #ff5252; border: 1px solid rgba(255, 60, 60, 0.4); padding: 0.45rem 1rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.2s;" onmouseover="this.style.background='rgba(255, 60, 60, 0.3)'" onmouseout="this.style.background='rgba(255, 60, 60, 0.15)'">
                <i class="fas fa-radiation"></i> Close All Live Positions
            </button>
        </form>
        @endif
    </div>

    <div class="glass-table-card" style="margin-bottom: 3rem;">
        @if(isset($openPositions) && $openPositions->count() > 0)
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Opened At</th>
                            <th>User</th>
                            <th>Bot ID</th>
                            <th>Broker</th>
                            <th>Pair & Type</th>
                            <th>Entry Price</th>
                            <th>Margin / Value</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($openPositions as $pos)
                        <tr>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                {{ $pos->opened_at ? $pos->opened_at->format('M d, H:i:s') : '-' }}
                            </td>
                            <td>
                                <span class="user-badge-chip">
                                    {{ $pos->user->name ?? 'User #' . $pos->user_id }}
                                </span>
                            </td>
                            <td style="font-family: monospace;">
                                <span style="background: rgba(255,255,255,0.06); padding: 0.2rem 0.5rem; border-radius: 5px; color: #fff;">
                                    #{{ str_pad($pos->bot_instance_id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td>
                                <span style="color: var(--accent-neon); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; background: rgba(0, 240, 255, 0.08); padding: 0.2rem 0.5rem; border-radius: 4px; border: 1px solid rgba(0, 240, 255, 0.2);">
                                    {{ $pos->botInstance->brokerAccount->broker ?? 'Broker' }}
                                </span>
                            </td>
                            <td>
                                <strong style="color: #fff; font-size: 0.95rem;">{{ $pos->symbol }}</strong><br>
                                @if($pos->side === 'LONG')
                                    <span style="color: var(--accent-green); font-size: 0.75rem; font-weight: 800;">BUY / LONG</span>
                                @else
                                    <span style="color: var(--accent-red); font-size: 0.75rem; font-weight: 800;">SELL / SHORT</span>
                                @endif
                            </td>
                            <td style="font-family: monospace; font-size: 0.95rem; font-weight: 600; color: #fff;">
                                ${{ number_format($pos->entry_price, 2) }}
                            </td>
                            <td>
                                <strong style="color: #fff;">${{ number_format($pos->margin_used ?? 0, 2) }}</strong>
                                <div style="font-size: 0.72rem; color: var(--text-secondary);">Value: ${{ number_format($pos->trade_value ?? 0, 2) }}</div>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                                    <form action="{{ route('trades.close', $pos->id) }}" method="POST" onsubmit="return confirm('Close position on exchange at market price?');" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-ctrl-pause" style="color: #ff5252; border-color: rgba(255, 60, 60, 0.3); background: rgba(255, 60, 60, 0.08);">
                                            <i class="fas fa-times-circle"></i> Close
                                        </button>
                                    </form>

                                    <form action="{{ route('trades.force_delete', $pos->id) }}" method="POST" onsubmit="return confirm('🚨 DANGER: Force delete this ghost position record from database?\n\nThis will remove the stuck/ghost trade immediately without calling the broker API.');" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ctrl-del" title="Force Delete Ghost Position">
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
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                <div style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.8;">✨</div>
                <h4 style="margin: 0 0 0.3rem 0; color: #fff; font-weight: 600;">No Open Positions or Ghost Trades</h4>
                <p style="margin: 0; font-size: 0.85rem;">All platforms & user accounts are currently clean with zero exposure.</p>
            </div>
        @endif
    </div>

    <!-- ==================================================================== -->
    <!-- 5. ALL TRADING BOTS (GLOBAL CONTROLS) TABLE                          -->
    <!-- ==================================================================== -->
    <div class="admin-section-header">
        <h3 class="admin-section-title">
            <i class="fas fa-cubes" style="color: #b388ff;"></i>
            All Trading Bots (Global Controls)
            <span style="background: rgba(179, 136, 255, 0.12); color: #b388ff; border: 1px solid rgba(179, 136, 255, 0.3); padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">
                {{ isset($allBots) ? $allBots->count() : 0 }} Total
            </span>
        </h3>
    </div>

    <div class="glass-table-card" style="margin-bottom: 3rem;">
        @if(isset($allBots) && $allBots->count() > 0)
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Bot ID</th>
                            <th>Broker Account</th>
                            <th>Pair & Strategy</th>
                            <th>Capital / Leverage</th>
                            <th>Status</th>
                            <th style="text-align: right;">Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allBots as $bot)
                        <tr>
                            <td>
                                <strong style="color: #fff;">{{ $bot->user->name ?? 'Unknown' }}</strong><br>
                                <span style="font-size: 0.75rem; color: var(--text-secondary);">{{ $bot->user->email ?? '' }}</span>
                            </td>
                            <td style="font-family: monospace;">
                                <span style="background: rgba(255,255,255,0.06); padding: 0.2rem 0.5rem; border-radius: 5px; color: #fff;">
                                    #{{ str_pad($bot->id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td>
                                <strong style="color: #fff; font-size: 0.9rem;">{{ $bot->brokerAccount->account_label ?? 'N/A' }}</strong><br>
                                <span style="font-size: 0.72rem; color: var(--accent-neon); text-transform: uppercase; font-weight: 700;">
                                    {{ $bot->brokerAccount->broker ?? '' }}
                                </span>
                            </td>
                            <td>
                                <strong style="color: #fff; font-size: 0.95rem;">{{ $bot->symbol }}</strong>
                                <span style="font-size: 0.75rem; color: var(--text-secondary);">({{ $bot->timeframe }})</span><br>
                                <span style="font-size: 0.75rem; color: #b388ff; font-weight: 600;">{{ class_basename($bot->strategy_class) }}</span>
                            </td>
                            <td>
                                <strong style="color: #fff;">${{ number_format($bot->allocated_capital, 2) }}</strong><br>
                                <span style="font-size: 0.75rem; color: var(--accent-neon); font-weight: 700;">{{ $bot->parameters['leverage'] ?? 25 }}x Lev</span>
                            </td>
                            <td>
                                @if($bot->status === 'running')
                                    <span style="color: var(--accent-neon); background: rgba(0, 240, 255, 0.1); border: 1px solid rgba(0, 240, 255, 0.3); padding: 0.25rem 0.65rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;">
                                        <span class="pulse-dot-green"></span> Running
                                    </span>
                                @else
                                    <span style="color: var(--text-secondary); background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 0.25rem 0.65rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                                        ⏸ Stopped
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                                    <form method="POST" action="{{ route('bots.toggle', $bot) }}" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-ctrl-pause" style="{{ $bot->status === 'running' ? '' : 'background: rgba(0, 230, 118, 0.15); color: var(--accent-green); border-color: rgba(0, 230, 118, 0.4);' }}">
                                            {{ $bot->status === 'running' ? '⏸ Pause' : '▶ Start' }}
                                        </button>
                                    </form>
                                    
                                    <form method="POST" action="{{ route('bots.destroy', $bot) }}" onsubmit="return confirm('⚠️ Are you sure you want to PERMANENTLY delete Bot #{{ $bot->id }} ({{ $bot->symbol }}) for user {{ $bot->user->name ?? 'User' }}?');" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ctrl-del" title="Delete Bot Permanently">
                                            <i class="fas fa-trash-alt"></i> Delete
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
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                <p style="margin: 0;">No trading bots created on the platform yet.</p>
            </div>
        @endif
    </div>

    <!-- ==================================================================== -->
    <!-- 6. PLATFORM USERS & GOVERNANCE DIRECTORY                             -->
    <!-- ==================================================================== -->
    <div class="admin-section-header">
        <h3 class="admin-section-title">
            <i class="fas fa-users-cog" style="color: var(--accent-cyan);"></i>
            Platform Users & Governance
            <span style="background: rgba(0, 240, 255, 0.12); color: var(--accent-cyan); border: 1px solid rgba(0, 240, 255, 0.3); padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">
                {{ $users->total() }} Total
            </span>
        </h3>
    </div>

    <div class="glass-table-card">
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User Profile</th>
                        <th>Email Address</th>
                        <th>Connected Brokers & Live Balance</th>
                        <th>Bots Count</th>
                        <th style="text-align: right;">Governance / Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, rgba(0, 240, 255, 0.2), rgba(179, 136, 255, 0.2)); border: 1px solid rgba(0, 240, 255, 0.3); display: flex; align-items: center; justify-content: center; font-weight: 800; color: #fff; font-size: 0.95rem;">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <strong style="color: #fff; font-size: 0.95rem;">{{ $u->name }}</strong>
                                        @if($u->role === 'superadmin')
                                            <span style="background: rgba(255, 61, 0, 0.15); color: #ff5252; border: 1px solid rgba(255, 61, 0, 0.4); padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.68rem; font-weight: 800; margin-left: 0.3rem;">SUPERADMIN</span>
                                        @elseif($u->role === 'admin')
                                            <span style="background: rgba(179, 136, 255, 0.15); color: #b388ff; border: 1px solid rgba(179, 136, 255, 0.4); padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.68rem; font-weight: 800; margin-left: 0.3rem;">ADMIN</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="color: var(--text-secondary); font-family: monospace; font-size: 0.85rem;">
                                {{ $u->email }}
                            </td>
                            <td style="min-width: 280px;">
                                @if($u->brokerAccounts && $u->brokerAccounts->count() > 0)
                                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                        @foreach($u->brokerAccounts as $acc)
                                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); padding: 0.35rem 0.6rem; border-radius: 6px; font-size: 0.8rem;">
                                                <div>
                                                    <span style="color: var(--accent-neon); font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">{{ str_replace('_', ' ', $acc->broker) }}</span>
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
                                    <span style="color: var(--text-secondary); font-size: 0.8rem; font-style: italic;">No broker linked</span>
                                @endif
                            </td>
                            <td>
                                <span style="background: rgba(255,255,255,0.06); padding: 0.25rem 0.6rem; border-radius: 6px; font-weight: 700; color: #fff;">
                                    {{ $u->bot_instances_count }} Bots
                                </span>
                            </td>
                            <td style="text-align: right;">
                                @if($u->id !== Auth::id())
                                <form method="POST" action="{{ route('admin.users.update', $u->id) }}" onsubmit="if(this.is_active.value === 'delete') { return confirm('🚨 DANGER: Are you sure you want to PERMANENTLY delete user \'{{ addslashes($u->name) }}\'?\n\nThis will remove all bots, broker accounts, open positions and trade records.\n\nThis action CANNOT be undone.'); }" style="margin: 0; display: inline-flex; justify-content: flex-end;">
                                    @csrf
                                    <div class="gov-form-wrapper">
                                        <select name="is_active" class="gov-select">
                                            <option value="1" {{ $u->is_active ? 'selected' : '' }}>● Approved</option>
                                            <option value="0" {{ !$u->is_active ? 'selected' : '' }}>⏸ Suspended</option>
                                            @if(in_array(Auth::user()->role ?? '', ['superadmin', 'admin']) && $u->role !== 'superadmin')
                                                <option value="delete" style="color: #ff5252; background: #1e1014; font-weight: bold;">🗑️ Delete User</option>
                                            @endif
                                        </select>
                                        
                                        <div class="gov-limit-badge" title="Max Trading Bots Allowed">
                                            <span>Limit:</span>
                                            <input type="number" name="max_bots" value="{{ $u->max_bots }}" class="gov-limit-input" min="0" max="100">
                                        </div>

                                        <button type="submit" class="gov-save-btn">
                                            <i class="fas fa-check"></i> Save
                                        </button>
                                    </div>
                                </form>
                                @else
                                    <span style="color: var(--text-secondary); font-size: 0.8rem; font-style: italic;">Active Session (You)</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.06);">
            {{ $users->links('pagination::bootstrap-4') }}
        </div>
    </div>

</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Fetch Real-time Dashboard UPNL
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

    // 2. Fetch User Live Balances for Platform Users table
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('portfolioChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    let gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(0, 240, 255, 0.4)');
    gradient.addColorStop(1, 'rgba(0, 240, 255, 0.0)');

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
});
</script>
@endsection
