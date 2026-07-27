@extends('layouts.guest')

@section('title', '403 - Access Forbidden | ' . config('app.name'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card border shadow-sm mt-4">
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
                    <div class="mt-4 d-flex flex-wrap gap-2 justify-content-center">
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
        </div>
    </div>
</div>
@endsection
