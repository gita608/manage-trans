@extends('layouts.auth')

@section('title', 'Sign In | ' . getSetting('app_name', config('app.name')))
@section('brand_title', 'Welcome Back')
@section('brand_subtitle', 'Sign in to access your ' . getSetting('app_name', config('app.name')) . ' account and manage your transportation operations efficiently.')

@section('content')
<div class="auth-form-header">
    <h2>Sign In</h2>
    <p>Enter your credentials to continue</p>
</div>

<form method="POST" action="{{ route('login') }}">
    @csrf

    @if (session('error'))
        <div class="alert alert-danger">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <i class="ri-error-warning-line me-2"></i>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-wrapper">
            <i class="ri-mail-line input-icon"></i>
            <input type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="Enter your email"
                   required
                   autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password-input">Password</label>
        <div class="input-wrapper">
            <i class="ri-lock-line input-icon"></i>
            <input type="password"
                   class="form-control pe-5 @error('password') is-invalid @enderror"
                   name="password"
                   placeholder="Enter your password"
                   id="password-input"
                   required>
            <button class="password-toggle-btn" type="button" id="password-addon" aria-label="Toggle password visibility">
                <i class="ri-eye-fill"></i>
            </button>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-options">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="auth-remember-check" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="auth-remember-check">Remember me</label>
        </div>
        @if(getSetting('enable_forgot_password', 'true') == 'true')
            <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
        @endif
    </div>

    <button class="btn-submit" type="submit">
        <i class="ri-login-box-line me-2"></i>Sign In
    </button>
</form>

@if(isset($enableSignup) && $enableSignup)
<div class="auth-footer">
    <p>Don't have an account? <a href="{{ route('register') }}">Sign up</a></p>
</div>
@endif
@endsection

@push('scripts')
<script>
    document.getElementById('password-addon')?.addEventListener('click', function() {
        const passwordInput = document.getElementById('password-input');
        const icon = this.querySelector('i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('ri-eye-fill');
            icon.classList.add('ri-eye-off-fill');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('ri-eye-off-fill');
            icon.classList.add('ri-eye-fill');
        }
    });
</script>
@endpush
