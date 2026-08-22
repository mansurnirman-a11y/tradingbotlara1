@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-white">Import TradingView History</h2>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light">Back to Dashboard</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success bg-opacity-10 border-success text-success">
            {!! session('success') !!}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger bg-opacity-10 border-danger text-danger">
            {!! session('error') !!}
        </div>
    @endif

    <div class="card bg-dark border-secondary">
        <div class="card-body">
            <p class="text-secondary">Upload a TradingView 'List of Trades' CSV file to inject historical execution data for a specific user. This function is restricted to Administrators.</p>

            <form action="{{ route('admin.import.process') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="user_id" class="form-label text-white">Target User</label>
                    <select name="user_id" id="user_id" class="form-select bg-dark text-white border-secondary" required>
                        <option value="">Select User...</option>
                        @foreach(\App\Models\User::all() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} (ID: {{ $user->id }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="csv" class="form-label text-white">TradingView CSV File</label>
                    <input type="file" name="csv" id="csv" class="form-control bg-dark text-white border-secondary" accept=".csv" required>
                </div>

                <button type="submit" class="btn btn-primary px-4">Upload and Import</button>
            </form>
        </div>
    </div>
</div>
@endsection
