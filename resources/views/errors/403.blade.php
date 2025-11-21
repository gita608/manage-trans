<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">

<head>
    <meta charset="utf-8" />
    <title>403 - Access Forbidden | {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ getSetting('favicon') ? asset('storage/' . getSetting('favicon')) : asset('assets/images/favicon.ico') }}">

    <!-- Layout config Js -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <!-- Dark Mode Persistence Fix -->
    <script src="{{ asset('assets/js/dark-mode-fix.js') }}"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />
</head>

<body>
    <div class="auth-page-wrapper pt-5">
        <!-- auth page bg -->
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>
            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
        </div>

        <!-- auth page content -->
        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <div>
                                <a href="{{ url('/') }}" class="d-inline-block auth-logo">
                                    <img src="{{ getSetting('app_logo') ? asset('storage/' . getSetting('app_logo')) : asset('assets/images/logo-light.png') }}" alt="" height="50">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end row -->

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <div class="mb-4">
                                        <i class="ri-error-warning-line text-danger" style="font-size: 80px;"></i>
                                    </div>
                                    <h1 class="display-1 fw-semibold text-danger mb-2">403</h1>
                                    <h4 class="text-uppercase mb-4">Access Forbidden</h4>
                                    <p class="text-muted mb-4">
                                        {{ $message ?? 'You do not have permission to access this resource.' }}
                                    </p>
                                    <div class="mt-4">
                                        @auth
                                            <a href="{{ route('dashboard') }}" class="btn btn-success">
                                                <i class="ri-home-4-line align-middle me-1"></i> Back to Dashboard
                                            </a>
                                        @else
                                            <a href="{{ route('login') }}" class="btn btn-primary">
                                                <i class="ri-login-box-line align-middle me-1"></i> Go to Login
                                            </a>
                                        @endauth
                                        <a href="javascript:history.back()" class="btn btn-secondary">
                                            <i class="ri-arrow-left-line align-middle me-1"></i> Go Back
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->
                    </div>
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end auth page content -->
    </div>
    <!-- end auth-page-wrapper -->

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('assets/js/plugins.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>

</html>

