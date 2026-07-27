@extends('layouts.guest')

@section('title', 'Page Not Found | ' . config('app.name'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card border shadow-sm mt-4">
            <div class="card-body p-4">
                <div class="text-center mt-2">
                    <h1 class="display-1 fw-bold text-primary mb-2">404</h1>
                    <h4 class="text-uppercase mb-4">Page Not Found</h4>
                    <p class="text-muted mb-4">The page you are looking for is not available.</p>
                    <div class="mt-4 d-flex flex-wrap gap-2 justify-content-center">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-success">
                                <i class="ri-home-4-line me-1"></i> Back to Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary">
                                <i class="ri-login-box-line me-1"></i> Go to Login
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
