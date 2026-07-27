<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', getSetting('app_name', config('app.name')))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Transportation Management System" name="description" />
    <link rel="shortcut icon" href="{{ brandingUrl('favicon', 'assets/images/favicon.ico') }}">

    @include('partials.pwa-head')

    <script src="{{ asset('assets/js/dark-mode-fix.js') }}"></script>
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ assetVersioned('assets/css/custom.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ assetVersioned('assets/css/dark-mode-custom.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ assetVersioned('assets/css/auth.css') }}" rel="stylesheet" type="text/css" />
    @stack('styles')
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
                            <img src="{{ brandingUrl('app_logo', 'assets/images/logo-light.png') }}" alt="{{ getSetting('app_name', config('app.name')) }}">
                        </a>
                    </div>
                    <h1>@yield('brand_title', 'Welcome')</h1>
                    <p>@yield('brand_subtitle', 'Manage your transportation operations efficiently.')</p>
                </div>
            </div>

            <div class="auth-right">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins.js') }}"></script>
    @stack('scripts')

    @include('partials.pwa-scripts')
</body>
</html>
