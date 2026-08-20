@extends('layouts.auth')

@section('title', 'Partner Portal Login')
@section('brand_title', 'Partner Portal')
@section('brand_subtitle', 'Sign in to submit and manage transportation requests.')

@section('content')
<div class="auth-form-container">
    <div class="auth-form-header">
        <h2>Partner Sign In</h2>
        <p>Access your Partner Portal account</p>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('partner.login.submit') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                   id="email" name="email" value="{{ old('email') }}" 
                   required autofocus autocomplete="email">
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                   id="password" name="password" 
                   required autocomplete="current-password">
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">
                    Remember Me
                </label>
            </div>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                Sign In
            </button>
        </div>
    </form>

    <div class="auth-form-footer mt-4 text-center">
        <p class="text-muted mb-0">
            <small>Partner accounts are managed by {{ getSetting('app_name', config('app.name')) }} administrators.</small>
        </p>
    </div>
</div>
@endsection
