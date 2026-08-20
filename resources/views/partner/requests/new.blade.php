@extends('layouts.partner')

@section('title', 'New Request - Partner Portal')

@section('content')
@include('partner.partials.page-header', [
    'title' => 'New Request',
    'subtitle' => 'Choose how you would like to submit your transportation request.',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('partner.dashboard')],
        ['label' => 'New Request']
    ]
])

<div class="row justify-content-center partner-method-picker">
    <div class="col-lg-10 col-xl-8">
        <div class="row g-4">
            @if(Auth::guard('partner')->user()->partner->allow_manual_submission)
                <div class="col-md-6">
                    <a href="{{ route('partner.requests.create') }}"
                       class="request-method-card text-decoration-none"
                       role="button"
                       aria-label="Enter crew transportation details manually">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="avatar-lg mb-3">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                        <i class="ri-edit-line fs-1" aria-hidden="true"></i>
                                    </span>
                                </div>
                                <h5 class="mb-3">Manual Request</h5>
                                <p class="text-muted mb-0">
                                    Enter transportation details directly using a simple form.
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            @endif

            @if(Auth::guard('partner')->user()->partner->allow_image_submission)
                <div class="col-md-6">
                    <a href="{{ route('partner.requests.image.create') }}"
                       class="request-method-card text-decoration-none"
                       role="button"
                       aria-label="Upload your schedule image for review">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="avatar-lg mb-3">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle">
                                        <i class="ri-image-add-line fs-1" aria-hidden="true"></i>
                                    </span>
                                </div>
                                <h5 class="mb-3">Upload Schedule Image</h5>
                                <p class="text-muted mb-0">
                                    Upload your schedule and Manage Trans will review it.
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            @endif
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('partner.dashboard') }}" class="btn btn-soft-secondary btn-touch">
                <i class="ri-arrow-left-line align-middle me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection