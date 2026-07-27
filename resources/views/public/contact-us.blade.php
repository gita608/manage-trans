<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">

<head>
    <meta charset="utf-8" />
    <title>Contact Us | {{ getSetting('app_name', config('app.name')) }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Contact Us" name="description" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ brandingUrl('favicon', 'assets/images/favicon.ico') }}">

    @include('partials.pwa-head')

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
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-tertiary: #64748b;
            --border-color: #e2e8f0;
            --card-bg: rgba(255, 255, 255, 0.95);
            --input-bg: #ffffff;
            --input-border: #e2e8f0;
            --input-focus-border: #6366f1;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        [data-bs-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --border-color: rgba(99, 102, 241, 0.2);
            --card-bg: rgba(15, 23, 42, 0.8);
            --input-bg: rgba(15, 23, 42, 0.6);
            --input-border: rgba(99, 102, 241, 0.2);
            --input-focus-border: #6366f1;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.6);
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
            transition: background 0.3s ease;
            color: var(--text-primary);
        }

        .grid-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
            z-index: 0;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .public-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .public-header {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-lg);
        }

        .public-header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .public-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
        }

        .public-logo img {
            height: 40px;
            width: auto;
        }

        .public-nav {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .public-nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .public-nav a:hover {
            color: #6366f1;
        }

        .public-content {
            flex: 1;
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 20px;
            width: 100%;
        }

        .public-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-xl);
            padding: 60px;
            animation: fadeInUp 0.8s ease-out;
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

        .public-card h1 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .public-card p {
            font-size: 16px;
            line-height: 1.8;
            color: var(--text-secondary);
            margin-bottom: 40px;
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
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 15px;
            transition: all 0.3s ease;
            outline: none;
            font-family: inherit;
        }

        .form-control::placeholder {
            color: var(--text-tertiary);
        }

        .form-control:focus {
            border-color: var(--input-focus-border);
            background: var(--input-bg);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 150px;
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

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border-left: 4px solid;
            font-size: 14px;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border-color: #22c55e;
            color: #16a34a;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
            color: #dc2626;
        }

        .invalid-feedback {
            display: block;
            color: #ef4444;
            font-size: 14px;
            margin-top: 8px;
        }

        .contact-info {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 1px solid var(--border-color);
        }

        .contact-info h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
            color: var(--text-secondary);
        }

        .contact-item i {
            font-size: 24px;
            color: #6366f1;
            margin-top: 4px;
            flex-shrink: 0;
        }

        .contact-item a {
            transition: color 0.2s ease;
        }

        .contact-item a:hover {
            color: #6366f1;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            color: var(--text-secondary);
            text-decoration: none;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-home:hover {
            border-color: #6366f1;
            color: #6366f1;
        }

        .public-footer {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border-color);
            padding: 40px 20px;
            text-align: center;
            color: var(--text-tertiary);
            font-size: 14px;
        }

        .public-footer a {
            transition: color 0.2s ease;
        }

        .public-footer a:hover {
            color: #6366f1;
        }

        .public-footer a[title] {
            display: inline-block;
            transition: all 0.2s ease;
        }

        .public-footer a[title]:hover {
            color: #6366f1;
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .public-card {
                padding: 40px 30px;
            }

            .public-card h1 {
                font-size: 32px;
            }

            .public-header-content {
                flex-direction: column;
                gap: 16px;
            }

            .public-nav {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="grid-background"></div>

    <div class="public-wrapper">
        <header class="public-header">
            <div class="public-header-content">
                <a href="{{ url('/') }}" class="public-logo">
                    <img src="{{ brandingUrl('app_logo', 'assets/images/logo-light.png') }}" alt="{{ getSetting('app_name', config('app.name')) }}">
                    <span style="font-weight: 700; font-size: 18px;">{{ getSetting('app_name', config('app.name')) }}</span>
                </a>
                <nav class="public-nav">
                    <a href="{{ url('/') }}">Home</a>
                    <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
                </nav>
            </div>
        </header>

        <main class="public-content">
            <div class="public-card">
                <h1>Contact Us</h1>
                <p>Have a question or need assistance? We're here to help! Fill out the form below and we'll get back to you as soon as possible.</p>

                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('contact-us.submit') }}">
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
                        <label class="form-label" for="name">Full Name <span style="color: #ef4444;">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="Enter your full name"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address <span style="color: #ef4444;">*</span></label>
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

                    <div class="form-group">
                        <label class="form-label" for="subject">Subject <span style="color: #ef4444;">*</span></label>
                        <input type="text"
                               class="form-control @error('subject') is-invalid @enderror"
                               id="subject"
                               name="subject"
                               value="{{ old('subject') }}"
                               placeholder="What is this regarding?"
                               required>
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="message">Message <span style="color: #ef4444;">*</span></label>
                        <textarea class="form-control @error('message') is-invalid @enderror"
                                  id="message"
                                  name="message"
                                  rows="6"
                                  placeholder="Please provide details about your inquiry..."
                                  required>{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="ri-send-plane-line me-2"></i>Send Message
                    </button>
                </form>

                <div class="contact-info">
                    <h3>Other Ways to Reach Us</h3>
                    <div class="contact-item">
                        <i class="ri-phone-line"></i>
                        <div>
                            <strong>Phone:</strong><br>
                            <a href="tel:026358789" style="color: var(--text-secondary); text-decoration: none;">026358789</a><br>
                            <a href="tel:+971565059598" style="color: var(--text-secondary); text-decoration: none;">+971 56 505 9598</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="ri-mail-line"></i>
                        <div>
                            <strong>Email:</strong><br>
                            <a href="mailto:info@m2mservicesuae.com" style="color: var(--text-secondary); text-decoration: none;">info@m2mservicesuae.com</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="ri-map-pin-line"></i>
                        <div>
                            <strong>Address:</strong><br>
                            Office no 6, 18th floor Al ferdous tower,<br>
                            Salam Street, Abu Dhabi, UAE
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="ri-whatsapp-line"></i>
                        <div>
                            <strong>WhatsApp:</strong><br>
                            <a href="https://wa.me/971565059598" target="_blank" style="color: #25D366; text-decoration: none; font-weight: 600;">Chat with us on WhatsApp</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="ri-time-line"></i>
                        <div>
                            <strong>Response Time:</strong><br>
                            We typically respond within 24-48 hours
                        </div>
                    </div>
                </div>

                <div style="text-align: center;">
                    <a href="{{ url('/') }}" class="btn-home">
                        <i class="ri-home-line"></i> Back to Home
                    </a>
                </div>
            </div>
        </main>

        <footer class="public-footer">
            <div style="margin-bottom: 20px;">
                <p style="margin-bottom: 16px;">&copy; {{ date('Y') }} {{ getSetting('app_name', 'MilestoMemories') }}. All rights reserved.</p>
                <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 16px;">
                    <a href="https://www.instagram.com/m2mservicesuae" target="_blank" style="color: var(--text-tertiary); font-size: 24px; text-decoration: none; transition: color 0.2s ease;" title="Instagram">
                        <i class="ri-instagram-line"></i>
                    </a>
                    <a href="https://www.facebook.com/m2mservicesuae" target="_blank" style="color: var(--text-tertiary); font-size: 24px; text-decoration: none; transition: color 0.2s ease;" title="Facebook">
                        <i class="ri-facebook-line"></i>
                    </a>
                    <a href="https://www.twitter.com/m2mservicesuae" target="_blank" style="color: var(--text-tertiary); font-size: 24px; text-decoration: none; transition: color 0.2s ease;" title="Twitter">
                        <i class="ri-twitter-line"></i>
                    </a>
                </div>
                <p>
                    <a href="{{ route('privacy-policy') }}" style="color: #6366f1; text-decoration: none;">Privacy Policy</a> | 
                    <a href="{{ route('contact-us') }}" style="color: #6366f1; text-decoration: none;">Contact Us</a>
                </p>
            </div>
        </footer>
    </div>

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('assets/js/plugins.js') }}"></script>
    <script src="{{ asset('assets/js/app-compat.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @include('partials.pwa-scripts')

</body>

</html>
