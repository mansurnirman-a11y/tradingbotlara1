@extends('layouts.app')

@section('title', 'Strategy Management')

@section('content')
<div class="container" style="padding-top: 3rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-secondary" style="text-decoration: none; display: inline-block; margin-bottom: 1rem;">← Back to Dashboard</a>
            <h1 style="font-size: 2.5rem; margin: 0;">Strategy <span class="text-gradient">Management</span></h1>
            <p class="text-secondary">Add and configure algorithms & webhooks for your users</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert" style="background: rgba(0, 230, 118, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.2); margin-bottom: 1.5rem; padding: 1rem;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert" style="background: rgba(255, 61, 0, 0.1); color: var(--accent-red); border: 1px solid rgba(255, 61, 0, 0.2); margin-bottom: 1.5rem; padding: 1rem;">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert" style="background: rgba(255, 61, 0, 0.1); color: var(--accent-red); border: 1px solid rgba(255, 61, 0, 0.2); margin-bottom: 1.5rem; padding: 1rem;">
            <ul style="margin: 0; padding-left: 1.5rem;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="glass-panel" style="padding: 2rem; margin-bottom: 3rem;">
        <h3 style="margin-top: 0; margin-bottom: 1.5rem;">Add New Strategy</h3>
        <form action="{{ route('admin.strategies.store') }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label class="form-label">Strategy Name</label>
                    <input type="text" name="name" class="form-input" required placeholder="e.g. BTC Scalper V2">
                </div>
                <div>
                    <label class="form-label">Strategy Type</label>
                    <select name="type" class="form-input" id="strategy_type_select" required style="background: rgba(0,0,0,0.5);">
                        <option value="internal">Internal (Built-in Class)</option>
                        <option value="webhook" selected>TradingView Webhook</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;" id="class_name_group">
                <label class="form-label">Internal Class Name</label>
                <input type="text" name="class_name" class="form-input" placeholder="e.g. App\Strategies\CustomStrategy">
                <small class="text-secondary" style="display: block; margin-top: 0.25rem;">Only required if type is Internal.</small>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="form-label">Description (Optional)</label>
                <textarea name="description" class="form-input" rows="2" placeholder="Describe the strategy for the users..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">+ Add Strategy</button>
        </form>
    </div>

    <h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-glass);">Active Strategies</h3>
    
    <div class="glass-panel" style="padding: 2rem; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-glass);">
                    <th style="padding: 1rem; color: var(--text-secondary);">Name</th>
                    <th style="padding: 1rem; color: var(--text-secondary);">Type</th>
                    <th style="padding: 1rem; color: var(--text-secondary);">Webhook URL / Class</th>
                    <th style="padding: 1rem; color: var(--text-secondary);">Status</th>
                    <th style="padding: 1rem; color: var(--text-secondary); text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($strategies as $strat)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                        <td style="padding: 1rem;">
                            <strong>{{ $strat->name }}</strong>
                            @if($strat->description)
                                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;">{{ $strat->description }}</div>
                            @endif
                        </td>
                        <td style="padding: 1rem;">
                            @if($strat->type === 'webhook')
                                <span style="background: rgba(179, 136, 255, 0.1); color: #b388ff; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; border: 1px solid rgba(179, 136, 255, 0.2);">TradingView</span>
                            @else
                                <span style="background: rgba(0, 240, 255, 0.1); color: var(--accent-neon); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; border: 1px solid rgba(0, 240, 255, 0.2);">Internal</span>
                            @endif
                        </td>
                        <td style="padding: 1rem; font-family: monospace; font-size: 0.85rem; color: var(--text-secondary);">
                            @if($strat->type === 'webhook')
                                <div style="background: rgba(0,0,0,0.5); padding: 0.5rem; border-radius: 4px; border: 1px solid var(--border-glass); cursor: pointer;" onclick="navigator.clipboard.writeText('{{ url('/api/webhook/'.$strat->webhook_key) }}'); alert('Webhook URL copied to clipboard!');" title="Click to copy">
                                    {{ url('/api/webhook/'.$strat->webhook_key) }}
                                </div>
                            @else
                                {{ $strat->class_name }}
                            @endif
                        </td>
                        <td style="padding: 1rem;">
                            @if($strat->is_active)
                                <span style="color: var(--accent-green);">● Active</span>
                            @else
                                <span style="color: var(--text-secondary);">○ Disabled</span>
                            @endif
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <form action="{{ route('admin.strategies.toggle', $strat->id) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-outline" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                                        {{ $strat->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.strategies.destroy', $strat->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this strategy?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn" style="padding: 0.4rem 0.75rem; font-size: 0.85rem; border: 1px solid var(--accent-red); color: var(--accent-red); background: transparent;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No strategies found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('strategy_type_select');
    const classGroup = document.getElementById('class_name_group');

    function toggleClassInput() {
        if (typeSelect.value === 'internal') {
            classGroup.style.display = 'block';
        } else {
            classGroup.style.display = 'none';
        }
    }

    typeSelect.addEventListener('change', toggleClassInput);
    toggleClassInput();
});
</script>
@endsection
