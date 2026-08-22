@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="container" style="padding: 4rem 1rem; margin-top: 2rem;">
    <div class="glass-panel" style="padding: 3rem; max-width: 900px; margin: 0 auto; border-radius: 12px; background: rgba(18, 22, 28, 0.8); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1);">
        <h1 style="color: #fff; font-size: 2.5rem; margin-bottom: 2rem; border-bottom: 2px solid var(--accent-neon); padding-bottom: 1rem; display: inline-block;">
            {{ $title }}
        </h1>
        
        <div class="text-secondary" style="line-height: 1.8; font-size: 1.1rem; display: flex; flex-direction: column; gap: 1rem;">
            {!! $content !!}
        </div>
        
        <div style="margin-top: 4rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 2rem;">
            <a href="{{ url('/') }}" class="btn btn-outline" style="padding: 0.8rem 1.5rem;">&larr; Back to Home</a>
        </div>
    </div>
</div>
@endsection
