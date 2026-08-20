@extends('layouts.partner')

@section('title', 'Dashboard - Partner Portal')

@section('content')
<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Partner Portal</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Card -->
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="card-title mb-3">Welcome, {{ $partnerUser->name }}</h4>
                        <p class="text-muted mb-1"><strong>Partner:</strong> {{ $partner->title }}</p>
                        <p class="text-muted mb-0"><strong>Email:</strong> {{ $partnerUser->email }}</p>
                        @if($partnerUser->phone)
                            <p class="text-muted mb-0"><strong>Phone:</strong> {{ $partnerUser->phone }}</p>
                        @endif
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <div class="avatar-title bg-success-subtle text-success rounded-3">
                                <i class="ri-shield-check-line fs-24"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Account Status -->
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Account Status</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success mb-0" role="alert">
                    <i class="ri-checkbox-circle-line me-2 align-middle fs-16"></i>
                    Your Partner Portal account is active.
                </div>
                
                @if($partnerUser->last_login_at)
                    <p class="text-muted mt-3 mb-0">
                        <small><strong>Last Login:</strong> {{ $partnerUser->last_login_at->format('M d, Y g:i A') }}</small>
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Portal Capabilities Info -->
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Portal Access</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Your Partner Portal account provides access to submit and manage transportation requests for <strong>{{ $partner->title }}</strong>.
                </p>
                <p class="text-muted mb-0">
                    Additional features will be available in upcoming phases.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
