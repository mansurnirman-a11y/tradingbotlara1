@extends('layouts.app')

@section('title', 'Sign Up')

@section('content')
<div class="auth-layout">
    <div class="glass-panel auth-card">
        <div class="auth-header">
            <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">Create {{ ucfirst($role) }} Account</h2>
            <p class="text-secondary">Join the premier trading platform</p>
            @if(in_array($role, ['admin', 'superadmin']))
                <p style="color: var(--accent-red); font-size: 0.8rem; margin-top: 0.5rem;">⚠️ Admin/Superadmin Registration Portal</p>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ url()->current() }}">
            @csrf
            
            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input id="name" type="text" class="form-input" name="name" value="{{ old('name') }}" required autofocus placeholder="John Doe">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input id="email" type="email" class="form-input" name="email" value="{{ old('email') }}" required placeholder="john@example.com">
            </div>

            <div class="form-group">
                <label for="mobile_number" class="form-label">Mobile Number</label>
                <input id="mobile_number" type="text" class="form-input" name="mobile_number" value="{{ old('mobile_number') }}" required placeholder="+1 234 567 8900">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" class="form-input" name="password" required placeholder="••••••••">
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input id="password_confirmation" type="password" class="form-input" name="password_confirmation" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Create Account
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem;">
            <p class="text-secondary" style="font-size: 0.875rem;">
                Already have an account? 
                @php
                    $loginRoute = $role === 'user' ? route('login') : route("{$role}.login");
                @endphp
                <a href="{{ $loginRoute }}" style="color: var(--accent-neon); text-decoration: none;">Log In</a>
            </p>
        </div>
    </div>
</div>
@endsection
