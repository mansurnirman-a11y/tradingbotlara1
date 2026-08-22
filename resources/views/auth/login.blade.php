@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="auth-layout">
    <div class="glass-panel auth-card">
        <div class="auth-header">
            <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">
                {{ ucfirst($role) }} Login
            </h2>
            <p class="text-secondary">Sign in to access your {{ $role }} dashboard</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ url()->current() }}">
            @csrf
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input id="email" type="email" class="form-input" name="email" value="{{ old('email') }}" required autofocus placeholder="email@tradingbot.com">
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" class="form-input" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Access Terminal
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem;">
            <p class="text-secondary" style="font-size: 0.875rem;">
                Don't have an account? 
                @php
                    $registerRoute = $role === 'user' ? route('register') : route("{$role}.register");
                @endphp
                <a href="{{ $registerRoute }}" style="color: var(--accent-neon); text-decoration: none;">Sign Up</a>
            </p>
        </div>
    </div>
</div>
@endsection
