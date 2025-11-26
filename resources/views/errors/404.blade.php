@extends('layouts.app')

@section('title', 'Page Not Found | ' . config('app.name'))

@section('content')
<div class="auth-page-wrapper py-5 d-flex justify-content-center align-items-center min-vh-100">
    <div class="auth-page-content overflow-hidden p-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-8">
                    <div class="text-center">
                        <div class="mb-4">
                            <h1 class="display-1 fw-bold text-primary">404</h1>
                        </div>
                        <h3 class="mt-4 fw-semibold">Sorry, Page not Found 😭</h3>
                        <p class="text-muted mb-4 fs-14">The page you are looking for not available!</p>
                        <a href="{{ route('dashboard') }}" class="btn btn-success"><i class="ri-home-4-line me-1"></i> Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
