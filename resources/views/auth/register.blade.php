<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">

<head>
    <meta charset="utf-8" />
    <title>Sign Up | {{ getSetting('app_name', config('app.name')) }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Transportation Management System" name="description" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ getSetting('favicon') ? asset('storage/' . getSetting('favicon')) : asset('assets/images/favicon.ico') }}">

    <!-- Dark Mode Persistence Fix - MUST load before layout.js -->
    <script src="{{ asset('assets/js/dark-mode-fix.js') }}"></script>
    <!-- Layout config Js -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Dark Mode Custom Styles -->
    <link href="{{ asset('assets/css/dark-mode-custom.css') }}" rel="stylesheet" type="text/css" />

    <style>
        :root {
            /* Light Mode Colors */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-tertiary: #64748b;
            --border-color: #e2e8f0;
            --card-bg: rgba(255, 255, 255, 0.95);
            --input-bg: #ffffff;
            --input-border: #e2e8f0;
            --input-focus-border: #6366f1;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --grid-opacity: 0.1;
            --orb-opacity: 0.2;
        }

        [data-bs-theme="dark"] {
            /* Dark Mode Colors */
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --border-color: rgba(99, 102, 241, 0.2);
            --card-bg: rgba(15, 23, 42, 0.8);
            --input-bg: rgba(15, 23, 42, 0.6);
            --input-border: rgba(99, 102, 241, 0.2);
            --input-focus-border: #6366f1;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.6);
            --grid-opacity: 0.3;
            --orb-opacity: 0.3;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 40px 0;
            transition: background 0.3s ease;
        }

        /* Animated grid background */
        .grid-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(99, 102, 241, var(--grid-opacity)) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, var(--grid-opacity)) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
            transition: opacity 0.3s ease;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* Floating orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: var(--orb-opacity);
            animation: float 20s infinite ease-in-out;
            transition: opacity 0.3s ease;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: #6366f1;
            top: -250px;
            left: -250px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: #8b5cf6;
            bottom: -200px;
            right: -200px;
            animation-delay: 5s;
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: #ec4899;
            top: 50%;
            right: -150px;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(100px, -100px) scale(1.1); }
            66% { transform: translate(-50px, 50px) scale(0.9); }
        }

        .auth-wrapper {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
            min-height: auto;
        }

        .auth-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-left {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            padding: 80px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .auth-left-content {
            position: relative;
            z-index: 1;
        }

        .auth-logo {
            margin-bottom: 40px;
        }

        .auth-logo img {
            height: 180px;
            width: auto;
            max-width: 100%;
            filter: brightness(0) invert(1);
        }

        .auth-left h1 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
            letter-spacing: -1px;
        }

        .auth-left p {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.6;
            max-width: 400px;
        }

        .auth-right {
            padding: 40px 50px;
            background: var(--bg-secondary);
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .auth-form-header {
            margin-bottom: 24px;
        }

        .auth-form-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
            transition: color 0.3s ease;
        }

        .auth-form-header p {
            font-size: 14px;
            color: var(--text-tertiary);
            transition: color 0.3s ease;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
            transition: color 0.3s ease;
        }

        .form-label .required {
            color: #ef4444;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-tertiary);
            font-size: 20px;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 12px 20px 12px 50px;
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-tertiary);
        }

        .form-control:focus {
            border-color: var(--input-focus-border);
            background: var(--input-bg);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .form-control:focus + .input-icon {
            color: var(--input-focus-border);
        }

        .password-toggle-btn {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
            z-index: 2;
        }

        .password-toggle-btn:hover {
            color: var(--input-focus-border);
            background: rgba(99, 102, 241, 0.1);
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: var(--bg-tertiary);
            border-radius: 2px;
            overflow: hidden;
            transition: background 0.3s ease;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981);
            transition: width 0.3s ease;
            border-radius: 3px;
        }

        .password-strength-text {
            margin-top: 6px;
            font-size: 11px;
            font-weight: 500;
            text-align: right;
            transition: color 0.3s ease;
        }

        .password-strength-weak { color: #ef4444; }
        .password-strength-medium { color: #f59e0b; }
        .password-strength-strong { color: #10b981; }

        .terms-section {
            margin: 16px 0;
        }

        .terms-text {
            font-size: 12px;
            color: var(--text-tertiary);
            line-height: 1.5;
            text-align: center;
            transition: color 0.3s ease;
        }

        .terms-text a {
            color: var(--input-focus-border);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .terms-text a:hover {
            color: #8b5cf6;
        }

        .btn-submit {
            width: 100%;
            padding: 12px 24px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            border-left: 4px solid;
            font-size: 13px;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
            color: #dc2626;
        }

        [data-bs-theme="dark"] .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .auth-footer {
            margin-top: 20px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            transition: border-color 0.3s ease;
        }

        .auth-footer p {
            color: var(--text-tertiary);
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .auth-footer a {
            color: var(--input-focus-border);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .auth-footer a:hover {
            color: #8b5cf6;
        }

        /* Light mode specific adjustments */
        @media (prefers-color-scheme: light) {
            body {
                background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 50%, #fce7f3 100%);
            }

            .auth-right {
                background: rgba(255, 255, 255, 0.95);
            }

            .form-control {
                background: #ffffff;
                border-color: #e2e8f0;
            }

            .form-control:focus {
                background: #ffffff;
            }

            .password-strength {
                background: #e2e8f0;
            }
        }

        /* Dark mode specific adjustments */
        [data-bs-theme="dark"] body {
            background: #0f172a;
        }

        [data-bs-theme="dark"] .auth-right {
            background: rgba(30, 41, 59, 0.5);
        }

        [data-bs-theme="dark"] .form-control {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(99, 102, 241, 0.2);
        }

        [data-bs-theme="dark"] .form-control:focus {
            background: rgba(15, 23, 42, 0.8);
        }

        [data-bs-theme="dark"] .password-strength {
            background: rgba(15, 23, 42, 0.6);
        }

        @media (max-width: 968px) {
            .auth-container {
                grid-template-columns: 1fr;
            }

            .auth-left {
                padding: 50px 40px;
                min-height: 300px;
            }

            .auth-left h1 {
                font-size: 32px;
            }

            .auth-right {
                padding: 50px 40px;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 20px 0;
            }

            .auth-wrapper {
                padding: 16px;
            }

            .auth-left {
                padding: 40px 30px;
            }

            .auth-left h1 {
                font-size: 28px;
            }

            .auth-right {
                padding: 40px 30px;
            }

            .auth-form-header h2 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>
    <div class="grid-background"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-left">
                <div class="auth-left-content">
                    <div class="auth-logo">
                        <a href="{{ url('/') }}">
                            @if(getSetting('app_logo'))
                                <img src="{{ asset('storage/' . getSetting('app_logo')) }}" alt="{{ getSetting('app_name', config('app.name')) }}">
                            @else
                                <img src="{{ asset('assets/images/logo-light.png') }}" alt="{{ getSetting('app_name', config('app.name')) }}">
                            @endif
                        </a>
                    </div>
                    <h1>Create Account</h1>
                    <p>Join {{ getSetting('app_name', config('app.name')) }} and start managing your transportation operations with ease and efficiency.</p>
                </div>
            </div>

            <div class="auth-right">
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
                            <a href="#">Terms of Use</a> and <a href="#">Privacy Policy</a>
                        </p>
                    </div>

                    <button class="btn-submit" type="submit">
                        <i class="ri-user-add-line me-2"></i>Create Account
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('assets/js/plugins.js') }}"></script>

    <script>
        // Password toggle functionality
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

        // Password strength indicator
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

        // Password match validation
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
</body>

</html>
