@extends('layouts.partner')

@section('title', 'Request Details - Partner Portal')

@section('content')
<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Request Details</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('partner.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('partner.requests.index') }}">My Requests</a></li>
                    <li class="breadcrumb-item active">{{ $partnerRequest->request_reference }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="card-title mb-1">{{ $partnerRequest->request_reference }}</h5>
                        <p class="text-muted mb-0">
                            Submitted: {{ $partnerRequest->submitted_at ? $partnerRequest->submitted_at->format('M d, Y g:i A') : 'N/A' }}
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if($partnerRequest->canPartnerEdit())
                            <a href="{{ route('partner.requests.edit', $partnerRequest) }}" class="btn btn-sm btn-primary">
                                <i class="ri-pencil-line align-middle me-1"></i> Edit
                            </a>
                        @endif
                        @if($partnerRequest->isPending())
                            <form action="{{ route('partner.requests.withdraw', $partnerRequest) }}" method="POST" onsubmit="return confirm('Are you sure you want to withdraw this request? This action cannot be undone.');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="ri-close-circle-line align-middle me-1"></i> Withdraw
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Request Information -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <p class="text-muted mb-1">Partner</p>
                        <p class="fw-medium mb-0">{{ $partnerRequest->partner->title }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1">Submitted By</p>
                        <p class="fw-medium mb-0">{{ $partnerRequest->partnerUser->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1">Submission Method</p>
                        <p class="mb-0">
                            @if($partnerRequest->submission_method === 'manual')
                                <span class="badge bg-info-subtle text-info">Manual</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary">Image</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1">Status</p>
                        <p class="mb-0">
                            @if($partnerRequest->status === 'pending')
                                <span class="badge bg-warning-subtle text-warning fs-12">Pending</span>
                            @elseif($partnerRequest->status === 'approved')
                                <span class="badge bg-success-subtle text-success fs-12">Approved</span>
                            @elseif($partnerRequest->status === 'declined')
                                <span class="badge bg-danger-subtle text-danger fs-12">Declined</span>
                            @elseif($partnerRequest->status === 'withdrawn')
                                <span class="badge bg-secondary-subtle text-secondary fs-12">Withdrawn</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($partnerRequest->partner_updated_at)
                    <div class="alert alert-info mb-4">
                        <i class="ri-information-line me-2"></i>
                        Last updated by partner: {{ $partnerRequest->partner_updated_at->format('M d, Y g:i A') }}
                    </div>
                @endif

                @if($partnerRequest->isDeclined() && $partnerRequest->decline_reason)
                    <div class="alert alert-danger mb-4">
                        <strong>Decline Reason:</strong> {{ $partnerRequest->decline_reason }}
                    </div>
                @endif

                @if($partnerRequest->isWithdrawn())
                    <div class="alert alert-secondary mb-4">
                        <i class="ri-arrow-go-back-line me-2"></i>
                        This request was withdrawn on {{ $partnerRequest->withdrawn_at ? $partnerRequest->withdrawn_at->format('M d, Y g:i A') : 'N/A' }}
                    </div>
                @endif

                @if($partnerRequest->trips->count() > 0)
                    <div class="alert alert-success mb-4">
                        <strong>Created Trips:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($partnerRequest->trips as $trip)
                                <li>
                                    <strong>{{ $trip->trip_reference }}</strong>
                                    @if($trip->trip_date)
                                        — {{ \Carbon\Carbon::parse($trip->trip_date)->format('M d, Y') }}
                                    @endif
                                    — {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($partnerRequest->isImage())
                    <div class="alert alert-info mb-4">
                        Your schedule has been submitted. Manage Trans will review the transportation details.
                    </div>

                    <h5 class="mb-3">Uploaded Schedule</h5>
                    <div class="border rounded p-3 mb-4">
                        <img src="{{ route('partner.requests.image', $partnerRequest) }}"
                             alt="Uploaded schedule for {{ $partnerRequest->request_reference }}"
                             class="img-fluid rounded"
                             style="max-height: 480px;">
                    </div>
                @else
                    <!-- Manual crew items -->
                    <h5 class="mb-3">Crew Details ({{ $partnerRequest->items->count() }} items)</h5>

                    @foreach($partnerRequest->items as $index => $item)
                        <div class="border rounded p-3 mb-3">
                            <h6 class="mb-3">Crew #{{ $index + 1 }}</h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Trip Date</p>
                                    <p class="fw-medium mb-0">{{ $item->trip_date ? \Carbon\Carbon::parse($item->trip_date)->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Name</p>
                                    <p class="fw-medium mb-0">{{ $item->name ?? 'N/A' }}</p>
                                </div>
                                @if($item->phone)
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1">Phone</p>
                                        <p class="fw-medium mb-0">{{ $item->phone }}</p>
                                    </div>
                                @endif
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Vessel</p>
                                    <p class="fw-medium mb-0">{{ $item->vessel ? $item->vessel->name : 'Not specified' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">From Location</p>
                                    <p class="fw-medium mb-0">{{ $item->from_location ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">To Location</p>
                                    <p class="fw-medium mb-0">{{ $item->to_location ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                <div class="mt-4">
                    <a href="{{ route('partner.requests.index') }}" class="btn btn-light">
                        <i class="ri-arrow-left-line align-middle me-1"></i> Back to Requests
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
