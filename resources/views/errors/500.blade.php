@extends('layouts.app')

@section('title', 'Server Error | ' . config('app.name'))

@section('content')
<div class="auth-page-wrapper py-5 d-flex justify-content-center align-items-center min-vh-100">
    <div class="auth-page-content overflow-hidden p-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-8">
                    <div class="text-center">
                        <div class="mb-4">
                            <h1 class="display-1 fw-bold text-danger">500</h1>
                        </div>
                        <h3 class="mt-4 fw-semibold">Internal Server Error 🚨</h3>
                        <p class="text-muted mb-4 fs-14">Server Error 500. We're not exactly sure what happened, but our servers say something is wrong.</p>
                        <a href="{{ route('dashboard') }}" class="btn btn-success"><i class="ri-home-4-line me-1"></i> Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
