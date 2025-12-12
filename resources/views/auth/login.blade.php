<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">

<head>
    <meta charset="utf-8" />
    <title>Sign In | {{ getSetting('app_name', config('app.name')) }}</title>
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
            padding: 60px;
            background: var(--bg-secondary);
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .auth-form-header {
            margin-bottom: 40px;
        }

        .auth-form-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            letter-spacing: -0.5px;
            transition: color 0.3s ease;
        }

        .auth-form-header p {
            font-size: 16px;
            color: var(--text-tertiary);
            transition: color 0.3s ease;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 10px;
            letter-spacing: 0.3px;
            transition: color 0.3s ease;
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
            padding: 16px 20px 16px 56px;
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 15px;
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 24px 0;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid var(--input-border);
            border-radius: 4px;
            background: var(--input-bg);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .form-check-input:checked {
            background: var(--input-focus-border);
            border-color: var(--input-focus-border);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e");
        }

        .form-check-label {
            color: var(--text-secondary);
            font-size: 14px;
            cursor: pointer;
            margin: 0;
            transition: color 0.3s ease;
        }

        .forgot-link {
            color: var(--input-focus-border);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: #8b5cf6;
        }

        .btn-submit {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
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
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border-left: 4px solid;
            font-size: 14px;
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
            margin-top: 32px;
            text-align: center;
            padding-top: 32px;
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
                    <h1>Welcome Back</h1>
                    <p>Sign in to access your {{ getSetting('app_name', config('app.name')) }} account and manage your transportation operations efficiently.</p>
                        </div>
                    </div>

            <div class="auth-right">
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
                            <label class="form-check-label" for="auth-remember-check">
                                Remember me
                            </label>
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
        // Password toggle
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
</body>

</html>
