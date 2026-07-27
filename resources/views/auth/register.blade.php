@extends('layouts.auth')

@section('title', 'Sign Up | ' . getSetting('app_name', config('app.name')))
@section('brand_title', 'Create Account')
@section('brand_subtitle', 'Join ' . getSetting('app_name', config('app.name')) . ' and start managing your transportation operations with ease and efficiency.')

@section('content')
<div class="auth-form-header">
    <h2>Sign Up</h2>
    <p>Create your account to get started</p>
</div>

<form method="POST" action="{{ route('register') }}" id="registerForm">
    @csrf

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
        <label class="form-label" for="name">Full Name <span class="required">*</span></label>
        <div class="input-wrapper">
            <i class="ri-user-line input-icon"></i>
            <input type="text"
                   class="form-control @error('name') is-invalid @enderror"
                   id="name"
                   name="name"
                   value="{{ old('name') }}"
                   placeholder="Enter your full name"
                   required
                   autofocus>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="email">Email Address <span class="required">*</span></label>
        <div class="input-wrapper">
            <i class="ri-mail-line input-icon"></i>
            <input type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="Enter your email address"
                   required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password-input">Password <span class="required">*</span></label>
        <div class="input-wrapper">
            <i class="ri-lock-line input-icon"></i>
            <input type="password"
                   class="form-control pe-5 password-input @error('password') is-invalid @enderror"
                   name="password"
                   placeholder="Create a strong password"
                   id="password-input"
                   required>
            <button class="password-toggle-btn" type="button" id="password-addon" aria-label="Toggle password visibility">
                <i class="ri-eye-fill"></i>
            </button>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="password-strength">
            <div class="password-strength-bar" id="password-strength-bar"></div>
        </div>
        <div class="password-strength-text" id="password-strength-text">Password strength</div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password-confirm">Confirm Password <span class="required">*</span></label>
        <div class="input-wrapper">
            <i class="ri-lock-password-line input-icon"></i>
            <input type="password"
                   class="form-control pe-5 password-input"
                   name="password_confirmation"
                   placeholder="Confirm your password"
                   id="password-confirm"
                   required>
            <button class="password-toggle-btn" type="button" id="password-confirm-addon" aria-label="Toggle password visibility">
                <i class="ri-eye-fill"></i>
            </button>
        </div>
    </div>

    <div class="terms-section">
        <p class="terms-text">
            By registering, you agree to the {{ getSetting('app_name', config('app.name')) }}
            <a href="{{ route('contact-us') }}">Terms of Use</a> and
            <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
        </p>
    </div>

    <button class="btn-submit" type="submit">
        <i class="ri-user-add-line me-2"></i>Create Account
    </button>
</form>

<div class="auth-footer">
    <p>Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
</div>
@endsection

@push('scripts')
<script>
    function setupPasswordToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        if (toggle && input) {
            toggle.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('ri-eye-fill');
                    icon.classList.add('ri-eye-off-fill');
                } else {
                    input.type = 'password';
                    icon.classList.remove('ri-eye-off-fill');
                    icon.classList.add('ri-eye-fill');
                }
            });
        }
    }

    setupPasswordToggle('password-addon', 'password-input');
    setupPasswordToggle('password-confirm-addon', 'password-confirm');

    const passwordInput = document.getElementById('password-input');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');

    if (passwordInput && strengthBar && strengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let strengthTextValue = 'Password strength';
            let strengthClass = '';

            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 10;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/\d/.test(password)) strength += 20;
            if (/[^a-zA-Z\d]/.test(password)) strength += 20;

            strengthBar.style.width = Math.min(strength, 100) + '%';

            if (strength < 50) {
                strengthTextValue = 'Weak';
                strengthClass = 'password-strength-weak';
            } else if (strength < 75) {
                strengthTextValue = 'Medium';
                strengthClass = 'password-strength-medium';
            } else {
                strengthTextValue = 'Strong';
                strengthClass = 'password-strength-strong';
            }

            strengthText.textContent = strengthTextValue;
            strengthText.className = 'password-strength-text ' + strengthClass;
        });
    }

    const passwordConfirm = document.getElementById('password-confirm');
    if (passwordInput && passwordConfirm) {
        passwordConfirm.addEventListener('input', function() {
            if (this.value && this.value !== passwordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
</script>
@endpush
