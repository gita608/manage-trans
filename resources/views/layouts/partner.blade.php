<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="light" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Partner Portal - ' . getSetting('app_name', config('app.name')))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content="Partner Portal" name="description" />
    <link rel="shortcut icon" href="{{ brandingUrl('favicon', 'assets/images/favicon.ico') }}">

    @include('partials.pwa-head')

    <script src="{{ asset('assets/js/dark-mode-fix.js') }}"></script>
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ assetVersioned('assets/css/custom.css') }}" rel="stylesheet" type="text/css" />
    @stack('styles')
    <link href="{{ assetVersioned('assets/css/dark-mode-custom.css') }}" rel="stylesheet" type="text/css" />
</head>

<body>
    <div id="layout-wrapper">
        <!-- Header -->
        <header id="page-topbar">
            <div class="layout-width">
                <div class="navbar-header">
                    <div class="d-flex">
                        <!-- LOGO -->
                        <div class="navbar-brand-box horizontal-logo">
                            <a href="{{ route('partner.dashboard') }}" class="logo logo-dark">
                                <span class="logo-sm">
                                    <img src="{{ brandingUrl('app_logo', 'assets/images/logo-sm.png') }}" alt="" height="22">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{ brandingUrl('app_logo', 'assets/images/logo-dark.png') }}" alt="" height="17">
                                </span>
                            </a>

                            <a href="{{ route('partner.dashboard') }}" class="logo logo-light">
                                <span class="logo-sm">
                                    <img src="{{ brandingUrl('app_logo', 'assets/images/logo-sm.png') }}" alt="" height="22">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{ brandingUrl('app_logo', 'assets/images/logo-light.png') }}" alt="" height="17">
                                </span>
                            </a>
                        </div>

                        <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
                            <span class="hamburger-icon">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </button>
                    </div>

                    <div class="d-flex align-items-center">
                        <!-- Partner Info -->
                        <div class="dropdown ms-sm-3 header-item topbar-user">
                            <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="d-flex align-items-center">
                                    <span class="text-start ms-xl-2">
                                        <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ Auth::guard('partner')->user()->name }}</span>
                                        <span class="d-none d-xl-block ms-1 fs-12 text-muted user-name-sub-text">{{ Auth::guard('partner')->user()->partner->title }}</span>
                                    </span>
                                </span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <h6 class="dropdown-header">Partner Portal</h6>
                                <a class="dropdown-item" href="{{ route('partner.dashboard') }}">
                                    <i class="mdi mdi-view-dashboard text-muted fs-16 align-middle me-1"></i> 
                                    <span class="align-middle">Dashboard</span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('partner.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i> 
                                        <span class="align-middle">Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <div class="app-menu navbar-menu">
            <div class="navbar-brand-box">
                <a href="{{ route('partner.dashboard') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ brandingUrl('app_logo', 'assets/images/logo-sm.png') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ brandingUrl('app_logo', 'assets/images/logo-dark.png') }}" alt="" height="17">
                    </span>
                </a>
                <a href="{{ route('partner.dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ brandingUrl('app_logo', 'assets/images/logo-sm.png') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ brandingUrl('app_logo', 'assets/images/logo-light.png') }}" alt="" height="17">
                    </span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>

            <div id="scrollbar">
                <div class="container-fluid">
                    <ul class="navbar-nav" id="navbar-nav">
                        <li class="menu-title"><span>Partner Portal</span></li>

                        <li class="nav-item">
                            <a class="nav-link menu-link {{ request()->routeIs('partner.dashboard') ? 'active' : '' }}" href="{{ route('partner.dashboard') }}">
                                <i class="ri-dashboard-2-line"></i> <span>Dashboard</span>
                            </a>
                        </li>

                        @if(Auth::guard('partner')->user()->partner->allow_manual_submission)
                            <li class="nav-item">
                                <a class="nav-link menu-link {{ request()->routeIs('partner.requests.create') ? 'active' : '' }}" href="{{ route('partner.requests.create') }}">
                                    <i class="ri-add-circle-line"></i> <span>New Request</span>
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link menu-link {{ request()->routeIs('partner.requests.*') && !request()->routeIs('partner.requests.create') ? 'active' : '' }}" href="{{ route('partner.requests.index') }}">
                                <i class="ri-file-list-3-line"></i> <span>My Requests</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="sidebar-background"></div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            {{ date('Y') }} © {{ getSetting('app_name', config('app.name')) }}.
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                Partner Portal
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('assets/js/plugins.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @stack('scripts')

    @include('partials.pwa-scripts')
</body>
</html>
