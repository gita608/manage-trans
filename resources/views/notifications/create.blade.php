@extends('layouts.app')

@section('title', 'Send Notification | ' . config('app.name'))

@section('content')
@if(!auth()->user()->hasPermission('create_notifications'))
    <div class="alert alert-danger">
        <i class="ri-error-warning-line me-2"></i>You do not have permission to create notifications.
    </div>
    <div class="text-center mt-4">
        {{-- Temporarily commented out to debug route issue --}}
        {{-- <a href="{{ route('notifications.admin-index') }}" class="btn btn-secondary">Go Back</a> --}}
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Go Back</a>
    </div>
@else
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Send Notification</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Send Notification</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-notification-3-line me-2"></i>Notification Details</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('notifications.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="driver_id" class="form-label">Send To</label>
                                <select class="form-select @error('driver_id') is-invalid @enderror" id="driver_id" name="driver_id">
                                    <option value="">All Drivers</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->name }} 
                                            @if($driver->email)
                                                ({{ $driver->email }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Leave empty to send notification to all drivers</small>
                                @error('driver_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" placeholder="Enter notification title" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" placeholder="Enter notification message" required>{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary" type="submit">
                            <i class="ri-send-plane-line me-1"></i>Send Notification
                        </button>
                        {{-- Temporarily commented out to debug route issue --}}
                        {{-- <a href="{{ route('notifications.admin-index') }}" class="btn btn-secondary">Cancel</a> --}}
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

