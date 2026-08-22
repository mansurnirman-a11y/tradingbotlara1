@extends('layouts.app')

@section('title', 'Platform Settings')

@section('content')
<div class="container" style="padding-top: 3rem; max-width: 800px;">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2.5rem; margin: 0;">Account <span class="text-gradient">Settings</span></h1>
        <p class="text-secondary">Configure your platform preferences and notifications</p>
    </div>

    @if(session('success'))
        <div class="alert" style="background: rgba(0, 230, 118, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.2);">
            {{ session('success') }}
        </div>
    @endif

    <div class="glass-panel" style="padding: 2.5rem;">
        <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; border-bottom: 1px solid var(--border-glass); padding-bottom: 0.5rem;">
            Telegram Alerts
        </h3>
        
        <p class="text-secondary" style="margin-bottom: 1.5rem;">
            Get real-time push notifications straight to your phone whenever your bots execute a trade or hit a stop-loss.
        </p>

        <form method="POST" action="{{ route('settings.update') }}">
            @csrf

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Mobile Number</label>
                <input type="text" name="mobile_number" class="form-input" placeholder="+1 234 567 8900" value="{{ old('mobile_number', $user->mobile_number) }}">
                <small class="text-secondary" style="display: block; margin-top: 0.5rem;">
                    Used for SMS alerts (if enabled) and account recovery.
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Telegram Chat ID</label>
                <div style="display: flex; gap: 1rem;">
                    <input type="text" name="telegram_chat_id" class="form-input" placeholder="e.g. 123456789" value="{{ old('telegram_chat_id', $user->telegram_chat_id) }}">
                    <a href="https://t.me/RawDataBot" target="_blank" class="btn btn-outline" style="white-space: nowrap;">Get My ID</a>
                </div>
                <small class="text-secondary" style="display: block; margin-top: 0.5rem;">
                    Message <strong>@RawDataBot</strong> on Telegram to find your unique Chat ID.
                </small>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                Save Settings
            </button>
        </form>
    </div>
</div>
@endsection
