@extends('layouts.auth')

@section('title', 'Partner Portal Sign In')
@section('body_class', 'partner-auth-login')

@push('styles')
<link href="{{ assetVersioned('assets/css/partner-portal.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('brand_title', 'Partner Portal')
@section('brand_subtitle', 'Sign in to submit and manage crew transportation requests with ' . config('app.name') . '.')

@section('content')
<div class="auth-form-header">
    <h2>Partner Sign In</h2>
    <p>Access your Partner Portal account</p>
</div>

@if (session('error'))
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="ri-error-warning-line fs-5 me-2" aria-hidden="true"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

@if (session('status'))
    <div class="alert alert-success d-flex align-items-center" role="status">
        <i class="ri-checkbox-circle-line fs-5 me-2" aria-hidden="true"></i>
        <div>{{ session('status') }}</div>
    </div>
@endif

<form method="POST" action="{{ route('partner.login.submit') }}" id="partnerLoginForm" novalidate>
    @csrf

    <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-wrapper">
            <i class="ri-mail-line input-icon" aria-hidden="true"></i>
            <input type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="Enter your email"
                   required
                   autofocus
                   autocomplete="email"
                   aria-required="true"
                   @error('email') aria-describedby="email-error" aria-invalid="true" @enderror>
            @error('email')
                <div class="invalid-feedback d-block" id="email-error" role="alert">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password-input">Password</label>
        <div class="input-wrapper">
            <i class="ri-lock-password-line input-icon" aria-hidden="true"></i>
            <input type="password"
                   class="form-control pe-5 @error('password') is-invalid @enderror"
                   id="password-input"
                   name="password"
                   placeholder="Enter your password"
                   required
                   autocomplete="current-password"
                   aria-required="true"
                   @error('password') aria-describedby="password-error" aria-invalid="true" @enderror>
            <button class="password-toggle-btn" type="button" id="password-addon" aria-label="Toggle password visibility">
                <i class="ri-eye-fill" aria-hidden="true"></i>
            </button>
            @error('password')
                <div class="invalid-feedback d-block" id="password-error" role="alert">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-options">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember">Keep me signed in</label>
        </div>
    </div>

    <button class="btn-submit" type="submit" id="partnerLoginBtn">
        <i class="ri-login-circle-line me-2" aria-hidden="true"></i> Sign In
    </button>
</form>

<div class="partner-auth-notice" role="status">
    <i class="ri-information-line" aria-hidden="true"></i>
    <span>Partner accounts are managed by {{ config('app.name') }} administrators.</span>
</div>

<div class="auth-footer">
    <p class="mb-0">
        <i class="ri-shield-user-line me-1" aria-hidden="true"></i>
        Staff member?
        <a href="{{ route('login') }}">Sign in to admin portal</a>
    </p>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('password-addon')?.addEventListener('click', function () {
    const passwordInput = document.getElementById('password-input');
    const icon = this.querySelector('i');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('ri-eye-fill', 'ri-eye-off-fill');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('ri-eye-off-fill', 'ri-eye-fill');
    }
});

document.getElementById('partnerLoginForm')?.addEventListener('submit', function (e) {
    const btn = document.getElementById('partnerLoginBtn');
    if (btn.disabled) {
        e.preventDefault();
        return false;
    }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Signing in...';
});
</script>
@endpush
