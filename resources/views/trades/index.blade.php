@extends('layouts.app')

@section('title', 'Positions Ledger')

@section('content')
<style>
@keyframes pulse-neon {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 240, 255, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(0, 240, 255, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 240, 255, 0); }
}

@keyframes pulse-green {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(0, 230, 118, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0); }
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
}

.badge-open {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: rgba(0, 240, 255, 0.12);
    color: var(--accent-neon);
    border: 1px solid rgba(0, 240, 255, 0.3);
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-open .dot {
    width: 7px;
    height: 7px;
    background-color: var(--accent-neon);
    border-radius: 50%;
    box-shadow: 0 0 8px var(--accent-neon);
    animation: pulse-neon 2s infinite;
}

.badge-closed {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-secondary);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.8rem;
}

.open-position-card {
    background: linear-gradient(135deg, rgba(0, 240, 255, 0.03) 0%, rgba(255, 255, 255, 0.02) 100%);
    border: 1px solid rgba(0, 240, 255, 0.2);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2.5rem;
}

.btn-close-pos {
    background: rgba(255, 60, 60, 0.1);
    color: var(--accent-red);
    border: 1px solid rgba(255, 60, 60, 0.25);
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.2s ease;
}

.btn-close-pos:hover {
    background: rgba(255, 60, 60, 0.25);
    border-color: var(--accent-red);
    transform: translateY(-1px);
}
</style>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Page Header -->
    <div style="margin-bottom: 2.5rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem;">
        <div>
            <h1 style="font-size: 2.5rem; margin: 0; font-weight: 800; letter-spacing: -0.5px;">Positions <span class="text-gradient">Ledger</span></h1>
            <p class="text-secondary" style="margin-top: 0.5rem; font-size: 1rem;">Live monitoring of open positions and historical trade audit</p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <div class="glass-panel" style="padding: 0.75rem 1.25rem; border-radius: 12px; display: flex; align-items: center; gap: 0.75rem;">
                <span style="height: 10px; width: 10px; background-color: var(--accent-green); border-radius: 50%; display: inline-block; box-shadow: 0 0 10px var(--accent-green); animation: pulse-green 2s infinite;"></span>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Open Positions</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: #fff;">{{ $openPositions->count() }}</div>
                </div>
            </div>
            <div class="glass-panel" style="padding: 0.75rem 1.25rem; border-radius: 12px; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-history" style="color: var(--text-secondary); font-size: 1.2rem;"></i>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Total Closed</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: #fff;">{{ $closedPositions->total() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 1. OPEN POSITIONS SECTION / CARD -->
    <!-- ========================================== -->
    <div class="open-position-card">
        <div class="section-header">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span class="badge-open">
                    <span class="dot"></span>
                    ACTIVE POSITIONS
                </span>
                <span style="font-size: 0.9rem; color: var(--text-secondary);">({{ $openPositions->count() }} In Trade)</span>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                @if($openPositions->count() > 0 && in_array(Auth::user()->role ?? '', ['admin', 'superadmin']))
                    <form action="{{ route('trades.close_all') }}" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to FORCE CLOSE ALL active positions across all users?');" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn-close-pos" style="background: rgba(255, 60, 60, 0.2); border-color: rgba(255, 60, 60, 0.5);">
                            <i class="fas fa-radiation"></i> Close All Positions
                        </button>
                    </form>
                @endif
                <span style="font-size: 0.8rem; color: var(--text-secondary);">Live PnL updates every 10s</span>
            </div>
        </div>

        @if($openPositions->count() > 0)
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(0, 240, 255, 0.2);">
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Opened At</th>
                            @if(in_array(Auth::user()->role ?? '', ['admin', 'superadmin']))
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">User</th>
                            @endif
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Bot ID</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Pair</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Type</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Entry Price</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Allocated Margin</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Live PnL (ROE)</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($openPositions as $position)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.15);">
                            <td style="padding: 1rem; color: var(--text-secondary); font-size: 0.875rem;">
                                {{ $position->opened_at->format('M d, H:i:s') }}
                            </td>
                            @if(in_array(Auth::user()->role ?? '', ['admin', 'superadmin']))
                            <td style="padding: 1rem; font-size: 0.85rem;">
                                <span style="background: rgba(0, 240, 255, 0.1); color: var(--accent-cyan); padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;">
                                    {{ $position->user->name ?? 'User #' . $position->user_id }}
                                </span>
                            </td>
                            @endif
                            <td style="padding: 1rem; font-family: monospace;">
                                <span style="background: rgba(255,255,255,0.08); padding: 0.2rem 0.5rem; border-radius: 6px;" title="{{ $position->botInstance->name ?? 'Bot #' . $position->bot_instance_id }}">
                                    #{{ str_pad($position->bot_instance_id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <strong style="font-size: 1rem; color: #fff;">{{ $position->symbol }}</strong>
                            </td>
                            <td style="padding: 1rem;">
                                @if($position->side === 'LONG')
                                    <span style="color: var(--accent-green); background: rgba(0, 230, 118, 0.12); border: 1px solid rgba(0, 230, 118, 0.3); padding: 0.25rem 0.6rem; border-radius: 6px; font-weight: 700; font-size: 0.8rem; letter-spacing: 0.5px;">BUY / LONG</span>
                                @else
                                    <span style="color: var(--accent-red); background: rgba(255, 60, 60, 0.12); border: 1px solid rgba(255, 60, 60, 0.3); padding: 0.25rem 0.6rem; border-radius: 6px; font-weight: 700; font-size: 0.8rem; letter-spacing: 0.5px;">SELL / SHORT</span>
                                @endif
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px;">
                                    Size: {{ rtrim(rtrim(number_format($position->base_size ?? 0, 4), '0'), '.') }} {{ explode('/', $position->symbol)[0] ?? '' }}
                                </div>
                            </td>
                            <td style="padding: 1rem; font-weight: 600; font-size: 0.95rem;">
                                ${{ number_format($position->entry_price, 2) }}
                            </td>
                            <td style="padding: 1rem;">
                                <strong style="color: #fff;">${{ number_format($position->margin_used ?? 0, 2) }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">Value: ${{ number_format($position->trade_value ?? 0, 2) }}</div>
                            </td>
                            <td style="padding: 1rem; font-weight: bold;">
                                <span class="live-pnl-cell" data-position-id="{{ $position->id }}">
                                    @if(isset($position->unrealized_pnl))
                                        @php
                                            $pnl = $position->unrealized_pnl;
                                            $margin = ($position->margin_used && $position->margin_used > 0) ? $position->margin_used : 1;
                                            $roe = ($pnl / $margin) * 100;
                                        @endphp
                                        @if($pnl > 0)
                                            <div style="color: var(--accent-green); font-weight: bold; font-size: 1rem;">+${{ number_format($pnl, 2) }}</div>
                                            <div style="font-size: 0.75rem; color: var(--accent-green); margin-top: 2px;">+{{ number_format($roe, 2) }}% ROE</div>
                                        @elseif($pnl < 0)
                                            <div style="color: var(--accent-red); font-weight: bold; font-size: 1rem;">-${{ number_format(abs($pnl), 2) }}</div>
                                            <div style="font-size: 0.75rem; color: var(--accent-red); margin-top: 2px;">{{ number_format($roe, 2) }}% ROE</div>
                                        @else
                                            <div style="color: var(--text-secondary); font-size: 1rem;">$0.00</div>
                                            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">0.00% ROE</div>
                                        @endif
                                        @if(isset($position->current_price))
                                            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">Mark: ${{ number_format($position->current_price, 2) }}</div>
                                        @endif
                                    @else
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fas fa-spinner fa-spin"></i> Calculating...</div>
                                    @endif
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <form action="{{ route('trades.close', $position->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to close this position at current Market Price?');" style="margin:0; display: inline-block;">
                                    @csrf
                                    <button type="submit" class="btn-close-pos" title="Close Position at Market Price">
                                        <i class="fas fa-times-circle"></i> Close Position
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align: center; padding: 2.5rem 1rem; background: rgba(0,0,0,0.1); border-radius: 12px; border: 1px dashed rgba(255,255,255,0.08);">
                <div style="font-size: 2.2rem; margin-bottom: 0.75rem; opacity: 0.8;">⚡</div>
                <h4 style="margin: 0 0 0.4rem 0; font-weight: 600; color: #fff;">No Active Open Positions</h4>
                <p class="text-secondary" style="margin: 0; font-size: 0.9rem; max-width: 500px; margin: 0 auto;">
                    Your bots are actively monitoring market feeds. When a strategy setup triggers, the live trade will appear here in real-time.
                </p>
            </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- 2. CLOSED POSITIONS HISTORY TABLE -->
    <!-- ========================================== -->
    <div class="glass-panel" style="padding: 2rem; border-radius: 16px;">
        <div class="section-header">
            <div>
                <h3 style="margin: 0; font-size: 1.3rem; font-weight: 700;">📜 Closed Position <span class="text-gradient">History</span></h3>
                <p class="text-secondary" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Historical trade settlements and realized profit/loss</p>
            </div>
            <span class="badge-closed">
                {{ $closedPositions->total() }} Total Records
            </span>
        </div>

        @if($closedPositions->count() > 0)
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Opened At</th>
                            @if(in_array(Auth::user()->role ?? '', ['admin', 'superadmin']))
                                <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">User</th>
                            @endif
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Bot ID</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Pair</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Type</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Entry Price</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Exit Price</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">On Trade (Margin)</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Realized PnL</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($closedPositions as $position)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.02); transition: background 0.2s ease;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem; color: var(--text-secondary); font-size: 0.875rem;">
                                {{ $position->opened_at ? $position->opened_at->format('M d, H:i') : '-' }}
                            </td>
                            @if(in_array(Auth::user()->role ?? '', ['admin', 'superadmin']))
                            <td style="padding: 1rem; font-size: 0.85rem;">
                                <span style="background: rgba(0, 240, 255, 0.1); color: var(--accent-cyan); padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;">
                                    {{ $position->user->name ?? 'User #' . $position->user_id }}
                                </span>
                            </td>
                            @endif
                            <td style="padding: 1rem; font-family: monospace;">
                                <span style="background: rgba(255,255,255,0.05); padding: 0.2rem 0.5rem; border-radius: 6px;" title="{{ $position->botInstance->name ?? 'Bot #' . $position->bot_instance_id }}">
                                    #{{ str_pad($position->bot_instance_id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td style="padding: 1rem;"><strong>{{ $position->symbol }}</strong></td>
                            <td style="padding: 1rem;">
                                @if($position->side === 'LONG')
                                    <span style="color: var(--accent-green); font-weight: 600;">LONG</span>
                                @else
                                    <span style="color: var(--accent-red); font-weight: 600;">SHORT</span>
                                @endif
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">
                                    {{ rtrim(rtrim(number_format($position->base_size ?? 0, 4), '0'), '.') }} {{ explode('/', $position->symbol)[0] ?? '' }}
                                </div>
                            </td>
                            <td style="padding: 1rem;">${{ number_format($position->entry_price, 2) }}</td>
                            <td style="padding: 1rem;">
                                {{ $position->exit_price ? '$'.number_format($position->exit_price, 2) : '-' }}
                            </td>
                            <td style="padding: 1rem;">
                                <strong>${{ number_format($position->margin_used ?? 0, 2) }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">Value: ${{ number_format($position->trade_value ?? 0, 2) }}</div>
                            </td>
                            <td style="padding: 1rem; font-weight: bold;">
                                @if($position->realized_pnl > 0)
                                    <span style="color: var(--accent-green); font-size: 0.95rem;">+${{ number_format($position->realized_pnl, 2) }}</span>
                                @elseif($position->realized_pnl < 0)
                                    <span style="color: var(--accent-red); font-size: 0.95rem;">-${{ number_format(abs($position->realized_pnl), 2) }}</span>
                                @else
                                    <span style="color: var(--text-secondary);">-</span>
                                @endif
                            </td>
                            <td style="padding: 1rem;">
                                <span class="badge-closed">Closed</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 2rem;">
                {{ $closedPositions->links('pagination::bootstrap-4') }}
            </div>
        @else
            <div style="text-align: center; padding: 3rem 1rem;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.6;">📊</div>
                <h4 style="margin-bottom: 0.4rem;">No Closed Positions Yet</h4>
                <p class="text-secondary" style="font-size: 0.9rem;">Historical trade records will be catalogued here once open positions conclude.</p>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pnlCells = document.querySelectorAll('.live-pnl-cell');
    if (pnlCells.length === 0) return;

    const positionIds = Array.from(pnlCells).map(cell => cell.dataset.positionId);

    function fetchLivePnl() {
        fetch('{{ route('trades.live_pnl') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ position_ids: positionIds })
        })
        .then(res => res.json())
        .then(data => {
            pnlCells.forEach(cell => {
                const posId = cell.dataset.positionId;
                if (data[posId]) {
                    const pnl = data[posId].pnl;
                    const price = data[posId].current_price;
                    const margin = parseFloat(data[posId].margin_used) || 1;
                    
                    const roe = (pnl / margin) * 100;
                    
                    let html = '';
                    
                    if (pnl > 0) {
                        html = `<div style="color: var(--accent-green); font-weight: bold; font-size: 1rem;">+$${pnl.toFixed(2)}</div>`;
                        html += `<div style="font-size: 0.75rem; color: var(--accent-green); margin-top: 2px;">+${roe.toFixed(2)}% ROE</div>`;
                    } else if (pnl < 0) {
                        html = `<div style="color: var(--accent-red); font-weight: bold; font-size: 1rem;">-$${Math.abs(pnl).toFixed(2)}</div>`;
                        html += `<div style="font-size: 0.75rem; color: var(--accent-red); margin-top: 2px;">${roe.toFixed(2)}% ROE</div>`;
                    } else {
                        html = `<div style="color: var(--text-secondary); font-size: 1rem;">$0.00</div>`;
                        html += `<div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">0.00% ROE</div>`;
                    }

                    // Live current price indicator below
                    html += `<div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">Mark: $${parseFloat(price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>`;
                    
                    cell.innerHTML = html;
                }
            });
        })
        .catch(err => console.error("Error fetching live PNL:", err));
    }

    // Fetch immediately, then every 5 seconds
    fetchLivePnl();
    setInterval(fetchLivePnl, 5000);
});
</script>
@endsection
