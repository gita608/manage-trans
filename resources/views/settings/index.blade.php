@extends('layouts.app')

@section('title', 'Settings | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Settings</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">General Settings</h5>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="app_name" class="form-label">App Name</label>
                        <input type="text" class="form-control" name="app_name" id="app_name" value="{{ $settings['app_name']->value }}" placeholder="Enter application name">
                        @error('app_name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="app_logo" class="form-label">App Logo</label>
                        @if($settings['app_logo']->value)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $settings['app_logo']->value) }}" alt="Current Logo" style="max-height: 60px;">
                            </div>
                        @endif
                        <input type="file" class="form-control" name="app_logo" id="app_logo" accept="image/*">
                        <small class="text-muted">Accepted formats: jpeg, png, jpg, gif, svg. Max size: 2MB</small>
                        @error('app_logo')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check form-switch form-switch-lg mb-3">
                        <input class="form-check-input" type="checkbox" name="enable_signup" id="enable_signup" {{ $settings['enable_signup']->value == 'true' ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_signup">Enable Sign-up</label>
                    </div>

                    <div class="form-check form-switch form-switch-lg mb-3">
                        <input class="form-check-input" type="checkbox" name="enable_forgot_password" id="enable_forgot_password" {{ $settings['enable_forgot_password']->value == 'true' ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_forgot_password">Enable Forgot Password</label>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
