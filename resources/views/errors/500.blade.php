@extends('layouts.guest')

@section('title', 'Server Error | ' . config('app.name'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card border shadow-sm mt-4">
            <div class="card-body p-4">
                <div class="text-center mt-2">
                    <h1 class="display-1 fw-bold text-danger mb-2">500</h1>
                    <h4 class="text-uppercase mb-4">Internal Server Error</h4>
                    <p class="text-muted mb-4">Something went wrong on our servers. Please try again later.</p>
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
