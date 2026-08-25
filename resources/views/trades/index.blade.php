@extends('layouts.app')

@section('title', 'Positions Ledger')

@section('content')
<style>
@keyframes pulse-light {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 240, 255, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(0, 240, 255, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 240, 255, 0); }
}
</style>

<div class="container" style="padding-top: 3rem;">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2.5rem; margin: 0;">Positions <span class="text-gradient">Ledger</span></h1>
        <p class="text-secondary">Track your open and closed market positions</p>
    </div>

    <div class="glass-panel" style="padding: 2rem;">
        @if(isset($positions) && $positions->count() > 0)
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Opened At</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Bot ID</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Pair</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Type</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Entry Price</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="height: 8px; width: 8px; background-color: var(--accent-neon); border-radius: 50%; display: inline-block; box-shadow: 0 0 8px var(--accent-neon); animation: pulse-light 2s infinite;"></span>
                                    On Trade
                                </div>
                            </th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Exit Price</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">PnL</th>
                            <th style="padding: 1rem; color: var(--text-secondary); font-weight: 500;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($positions as $position)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                            <td style="padding: 1rem; color: var(--text-secondary); font-size: 0.875rem;">{{ $position->opened_at->format('M d, H:i') }}</td>
                            <td style="padding: 1rem; font-family: monospace;">
                                <span title="{{ $position->botInstance->brokerAccount->account_label ?? 'Deleted Bot' }}">
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
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">{{ rtrim(rtrim(number_format($position->base_size ?? 0, 4), '0'), '.') }} {{ explode('/', $position->symbol)[0] ?? '' }}</div>
                            </td>
                            <td style="padding: 1rem;">${{ number_format($position->entry_price, 2) }}</td>
                            <td style="padding: 1rem;">
                                <strong>${{ number_format($position->margin_used ?? 0, 2) }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">Value: ${{ number_format($position->trade_value ?? 0, 2) }}</div>
                            </td>
                            <td style="padding: 1rem;">
                                {{ $position->exit_price ? '$'.number_format($position->exit_price, 2) : '-' }}
                            </td>
                            <td style="padding: 1rem; font-weight: bold;">
                                @if($position->status === 'OPEN')
                                    <span class="live-pnl-cell" data-position-id="{{ $position->id }}">
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fas fa-spinner fa-spin"></i> Fetching...</div>
                                    </span>
                                @else
                                    @if($position->realized_pnl > 0)
                                        <span style="color: var(--accent-green);">+${{ number_format($position->realized_pnl, 2) }}</span>
                                    @elseif($position->realized_pnl < 0)
                                        <span style="color: var(--accent-red);">-${{ number_format(abs($position->realized_pnl), 2) }}</span>
                                    @else
                                        <span style="color: var(--text-secondary);">-</span>
                                    @endif
                                @endif
                            </td>
                            <td style="padding: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                @if($position->status === 'OPEN')
                                    <span style="color: var(--accent-neon); background: rgba(0, 240, 255, 0.1); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">Open</span>
                                    <form action="{{ route('trades.close', $position->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to close this position at Market Price?');" style="margin:0;">
                                        @csrf
                                        <button type="submit" style="background: rgba(255, 60, 60, 0.1); color: var(--accent-red); border: 1px solid rgba(255, 60, 60, 0.2); padding: 0.25rem 0.5rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='rgba(255, 60, 60, 0.2)'" onmouseout="this.style.background='rgba(255, 60, 60, 0.1)'" title="Close Position">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @else
                                    <span style="color: var(--text-secondary); background: rgba(255, 255, 255, 0.05); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">Closed</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 2rem;">
                {{ $positions->links('pagination::bootstrap-4') }}
            </div>
        @else
            <div style="text-align: center; padding: 4rem 1rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                <h3 style="margin-bottom: 0.5rem;">No Positions Yet</h3>
                <p class="text-secondary">Your bots haven't entered any positions. Ensure they are running and market conditions meet your strategy.</p>
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
                        html = `<div style="color: var(--accent-green); font-weight: bold;">+$${pnl.toFixed(2)}</div>`;
                        html += `<div style="font-size: 0.75rem; color: var(--accent-green); margin-top: 2px;">+${roe.toFixed(2)}%</div>`;
                    } else if (pnl < 0) {
                        html = `<div style="color: var(--accent-red); font-weight: bold;">-$${Math.abs(pnl).toFixed(2)}</div>`;
                        html += `<div style="font-size: 0.75rem; color: var(--accent-red); margin-top: 2px;">${roe.toFixed(2)}%</div>`;
                    } else {
                        html = `<div style="color: var(--text-secondary);">$0.00</div>`;
                        html += `<div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">0.00%</div>`;
                    }

                    // Add a tiny current price indicator below
                    html += `<div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">@ $${parseFloat(price).toFixed(2)}</div>`;
                    
                    cell.innerHTML = html;
                }
            });
        })
        .catch(err => console.error("Error fetching live PNL:", err));
    }

    // Fetch immediately, then every 10 seconds
    fetchLivePnl();
    setInterval(fetchLivePnl, 10000);
});
</script>
@endsection
