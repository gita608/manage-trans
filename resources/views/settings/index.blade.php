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

@if (session('success'))
    <div class="alert alert-success alert-dismissible alert-border-left fade show" role="alert">
        <i class="ri-check-double-line me-2 align-middle"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="row">
        <!-- Application Settings -->
        <div class="col-lg-6">
            <div class="card border shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ri-settings-3-line me-2 align-middle"></i>Application Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label for="app_name" class="form-label fw-semibold">
                            <i class="ri-app-store-line me-1 text-muted"></i>Application Name
                        </label>
                        <input type="text" class="form-control @error('app_name') is-invalid @enderror" 
                               name="app_name" id="app_name" 
                               value="{{ $settings['app_name']->value }}" 
                               placeholder="Enter application name">
                        @error('app_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">This name will appear throughout the application</small>
                    </div>

                    <div class="mb-4">
                        <label for="app_logo" class="form-label fw-semibold">
                            <i class="ri-image-line me-1 text-muted"></i>Application Logo
                        </label>
                        @if($settings['app_logo']->value)
                            <div class="mb-3 p-3 bg-light rounded text-center">
                                <img src="{{ asset('storage/' . $settings['app_logo']->value) }}" 
                                     alt="Current Logo" 
                                     class="img-thumbnail" 
                                     style="max-height: 80px;">
                                <div class="mt-2">
                                    <small class="text-muted">Current Logo</small>
                                </div>
                            </div>
                        @endif
                        <input type="file" class="form-control @error('app_logo') is-invalid @enderror" 
                               name="app_logo" id="app_logo" accept="image/*">
                        <small class="text-muted d-block mt-1">
                            <i class="ri-information-line"></i> Accepted formats: JPEG, PNG, JPG, GIF, SVG | Max size: 2MB
                        </small>
                        @error('app_logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="favicon" class="form-label fw-semibold">
                            <i class="ri-file-image-line me-1 text-muted"></i>Favicon
                        </label>
                        @if($settings['favicon']->value)
                            <div class="mb-3 p-3 bg-light rounded text-center">
                                <img src="{{ asset('storage/' . $settings['favicon']->value) }}" 
                                     alt="Current Favicon" 
                                     class="img-thumbnail" 
                                     style="max-height: 32px;">
                                <div class="mt-2">
                                    <small class="text-muted">Current Favicon</small>
                                </div>
                            </div>
                        @endif
                        <input type="file" class="form-control @error('favicon') is-invalid @enderror" 
                               name="favicon" id="favicon" accept="image/x-icon,image/png">
                        <small class="text-muted d-block mt-1">
                            <i class="ri-information-line"></i> Accepted formats: ICO, PNG | Recommended size: 32x32px or 16x16px
                        </small>
                        @error('favicon')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label for="app_timezone" class="form-label fw-semibold">
                            <i class="ri-time-zone-line me-1 text-muted"></i>Application Timezone
                        </label>
                        <select class="form-select @error('app_timezone') is-invalid @enderror" 
                                name="app_timezone" id="app_timezone">
                            @foreach($timezoneGroups as $region => $regionTimezones)
                                <optgroup label="{{ $region }}">
                                    @foreach($regionTimezones as $timezone)
                                        <option value="{{ $timezone }}" 
                                                {{ $settings['app_timezone']->value == $timezone ? 'selected' : '' }}>
                                            {{ str_replace('_', ' ', $timezone) }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">
                            <i class="ri-information-line"></i> This timezone will be used throughout the entire application for all date and time displays.
                        </small>
                        @error('app_timezone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Authentication Settings -->
        <div class="col-lg-6">
            <div class="card border shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ri-shield-user-line me-2 align-middle"></i>Authentication Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">User Registration</h6>
                                <p class="text-muted mb-0 small">Allow new users to create accounts</p>
                            </div>
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" 
                                       name="enable_signup" id="enable_signup" 
                                       {{ $settings['enable_signup']->value == 'true' ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-4">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Password Recovery</h6>
                                <p class="text-muted mb-0 small">Enable forgot password functionality</p>
                            </div>
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" 
                                       name="enable_forgot_password" id="enable_forgot_password" 
                                       {{ $settings['enable_forgot_password']->value == 'true' ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile App Settings -->
    <div class="row">
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ri-smartphone-line me-2 align-middle"></i>Mobile App Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="android_version" class="form-label fw-semibold">Android Version</label>
                                <input type="text" class="form-control @error('android_version') is-invalid @enderror"
                                       name="android_version" id="android_version"
                                       value="{{ old('android_version', $settings['android_version']->value) }}"
                                       placeholder="e.g. 1.0.0">
                                @error('android_version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Latest Android app version available</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="ios_version" class="form-label fw-semibold">iOS Version</label>
                                <input type="text" class="form-control @error('ios_version') is-invalid @enderror"
                                       name="ios_version" id="ios_version"
                                       value="{{ old('ios_version', $settings['ios_version']->value) }}"
                                       placeholder="e.g. 1.0.0">
                                @error('ios_version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Latest iOS app version available</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="force_android_version" class="form-label fw-semibold">Force Android Version</label>
                                <input type="text" class="form-control @error('force_android_version') is-invalid @enderror"
                                       name="force_android_version" id="force_android_version"
                                       value="{{ old('force_android_version', $settings['force_android_version']->value) }}"
                                       placeholder="e.g. 1.0.0">
                                @error('force_android_version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Minimum Android version required (force update)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="force_ios_version" class="form-label fw-semibold">Force iOS Version</label>
                                <input type="text" class="form-control @error('force_ios_version') is-invalid @enderror"
                                       name="force_ios_version" id="force_ios_version"
                                       value="{{ old('force_ios_version', $settings['force_ios_version']->value) }}"
                                       placeholder="e.g. 1.0.0">
                                @error('force_ios_version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Minimum iOS version required (force update)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label for="location_sync_intervel" class="form-label fw-semibold">Location Sync Interval (seconds)</label>
                                <input type="number" class="form-control @error('location_sync_intervel') is-invalid @enderror"
                                       name="location_sync_intervel" id="location_sync_intervel"
                                       value="{{ old('location_sync_intervel', $settings['location_sync_intervel']->value) }}"
                                       min="1" max="86400" step="1"
                                       placeholder="e.g. 30">
                                @error('location_sync_intervel')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">How often the driver app should sync GPS location</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="row">
        <div class="col-12">
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="ri-save-line me-1"></i>Save All Settings
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
