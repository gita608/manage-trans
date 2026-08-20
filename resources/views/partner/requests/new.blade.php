@extends('layouts.partner')

@section('title', 'New Request - Partner Portal')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">New Request</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('partner.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">New Request</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="text-center mb-4">
            <h5 class="mb-2">How would you like to submit?</h5>
            <p class="text-muted mb-0">Choose the option that best fits your schedule.</p>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <a href="{{ route('partner.requests.create') }}" class="text-decoration-none">
                    <div class="card h-100 border shadow-sm">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="avatar-md mb-3">
                                <span class="avatar-title bg-primary-subtle text-primary rounded fs-2">
                                    <i class="ri-edit-line"></i>
                                </span>
                            </div>
                            <h5 class="text-dark mb-2">Enter Manually</h5>
                            <p class="text-muted mb-0 flex-grow-1">
                                Enter crew transportation details using a simple form.
                            </p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-6">
                <a href="{{ route('partner.requests.image.create') }}" class="text-decoration-none">
                    <div class="card h-100 border shadow-sm">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="avatar-md mb-3">
                                <span class="avatar-title bg-success-subtle text-success rounded fs-2">
                                    <i class="ri-image-add-line"></i>
                                </span>
                            </div>
                            <h5 class="text-dark mb-2">Upload Schedule Image</h5>
                            <p class="text-muted mb-0 flex-grow-1">
                                Upload the transportation schedule you received. Manage Trans will review the extracted information.
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
