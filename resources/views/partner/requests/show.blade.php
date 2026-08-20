@extends('layouts.partner')

@section('title', $partnerRequest->request_reference . ' - Partner Portal')

@section('content')
@include('partner.partials.page-header', [
    'title' => 'Request Details',
    'subtitle' => $partnerRequest->request_reference,
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('partner.dashboard')],
        ['label' => 'My Requests', 'url' => route('partner.requests.index')],
        ['label' => $partnerRequest->request_reference]
    ]
])

<div class="row">
    <div class="col-lg-12">
        <div class="card partner-page-card">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="flex-grow-1 flex-text-safe min-width-0">
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                            <h5 class="card-title mb-0 text-break-safe">{{ $partnerRequest->request_reference }}</h5>
                            @include('partner.partials.status-badge', ['status' => $partnerRequest->status])
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="ri-calendar-line me-1" aria-hidden="true"></i>
                            Submitted: {{ $partnerRequest->submitted_at ? $partnerRequest->submitted_at->format('M d, Y g:i A') : 'N/A' }}
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if($partnerRequest->canPartnerEdit())
                            <a href="{{ route('partner.requests.edit', $partnerRequest) }}" class="btn btn-sm btn-primary btn-touch">
                                <i class="ri-pencil-line align-middle me-1"></i> Edit
                            </a>
                        @endif
                        @if($partnerRequest->isPending())
                            <form action="{{ route('partner.requests.withdraw', $partnerRequest) }}" method="POST" onsubmit="return confirm('Are you sure you want to withdraw this request? This action cannot be undone.');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-danger btn-touch">
                                    <i class="ri-close-circle-line align-middle me-1"></i> Withdraw
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if(!$partnerRequest->isWithdrawn() && !$partnerRequest->isDeclined())
                    <div class="request-journey" role="region" aria-label="Request status progress">
                        <div class="journey-step {{ $partnerRequest->isPending() || $partnerRequest->isApproved() ? 'completed' : '' }}">
                            <div class="journey-step-icon">
                                <i class="ri-send-plane-line" aria-hidden="true"></i>
                            </div>
                            <span class="journey-step-label">Submitted</span>
                        </div>
                        <div class="journey-step {{ $partnerRequest->isPending() ? 'active' : ($partnerRequest->isApproved() ? 'completed' : '') }}">
                            <div class="journey-step-icon">
                                <i class="ri-search-eye-line" aria-hidden="true"></i>
                            </div>
                            <span class="journey-step-label">Under Review</span>
                        </div>
                        <div class="journey-step {{ $partnerRequest->isApproved() ? 'active' : '' }}">
                            <div class="journey-step-icon">
                                <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                            </div>
                            <span class="journey-step-label">Approved</span>
                        </div>
                    </div>
                @endif

                @if($partnerRequest->isPending())
                    <div class="alert alert-warning d-flex align-items-start mb-4" role="status">
                        <i class="ri-time-line fs-4 me-2 mt-1" aria-hidden="true"></i>
                        <div>
                            <strong>Your request is being reviewed</strong>
                            <p class="mb-0 mt-1">Manage Trans is reviewing your transportation request. We'll process it as soon as possible.</p>
                        </div>
                    </div>
                @endif

                @if($partnerRequest->isDeclined() && $partnerRequest->decline_reason)
                    <div class="alert alert-danger d-flex align-items-start mb-4" role="alert">
                        <i class="ri-error-warning-line fs-4 me-2 mt-1" aria-hidden="true"></i>
                        <div class="flex-grow-1">
                            <strong>Request Declined</strong>
                            <p class="mb-2 mt-1">This request could not be processed for the following reason:</p>
                            <div class="p-3 bg-light-subtle rounded border">
                                {{ $partnerRequest->decline_reason }}
                            </div>
                        </div>
                    </div>
                @endif

                @if($partnerRequest->isWithdrawn())
                    <div class="alert alert-secondary d-flex align-items-start mb-4" role="status">
                        <i class="ri-arrow-go-back-line fs-4 me-2 mt-1" aria-hidden="true"></i>
                        <div>
                            <strong>Request Withdrawn</strong>
                            <p class="mb-0 mt-1">
                                You withdrew this request on {{ $partnerRequest->withdrawn_at ? $partnerRequest->withdrawn_at->format('M d, Y g:i A') : 'N/A' }}.
                            </p>
                        </div>
                    </div>
                @endif

                @if($partnerRequest->isApproved() && $partnerRequest->trips->count() === 0)
                    <div class="alert alert-success d-flex align-items-start mb-4" role="status">
                        <i class="ri-checkbox-circle-line fs-4 me-2 mt-1" aria-hidden="true"></i>
                        <div>
                            <strong>Request Approved</strong>
                            <p class="mb-0 mt-1">Your request has been approved. Transportation scheduling is being prepared.</p>
                        </div>
                    </div>
                @endif

                @if($partnerRequest->trips->count() > 0)
                    <div class="alert alert-success d-flex align-items-start mb-4" role="status">
                        <i class="ri-ship-line fs-4 me-2 mt-1" aria-hidden="true"></i>
                        <div class="flex-grow-1">
                            <strong>Transportation Scheduled</strong>
                            <p class="mb-2 mt-1">The following trips have been created for your request:</p>
                            <div class="list-group list-group-flush">
                                @foreach($partnerRequest->trips as $trip)
                                    <div class="list-group-item bg-transparent px-0 py-2">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <strong class="text-break-safe">{{ $trip->trip_reference }}</strong>
                                            @if($trip->trip_date)
                                                <span class="text-muted">—</span>
                                                <span class="text-muted">{{ \Carbon\Carbon::parse($trip->trip_date)->format('M d, Y') }}</span>
                                            @endif
                                            <span class="text-muted">—</span>
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <h6 class="partner-section-title">
                    <i class="ri-information-line text-primary" aria-hidden="true"></i>
                    Request Information
                </h6>
                <div class="row g-3 mb-4 partner-detail-grid">
                    <div class="col-md-3 col-6">
                        <p class="detail-label">Partner</p>
                        <p class="detail-value text-break-safe">{{ $partnerRequest->partner->title }}</p>
                    </div>
                    <div class="col-md-3 col-6">
                        <p class="detail-label">Submitted By</p>
                        <p class="detail-value">{{ $partnerRequest->partnerUser->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-3 col-6">
                        <p class="detail-label">Submission Method</p>
                        <p class="detail-value mb-0">
                            @include('partner.partials.method-badge', ['method' => $partnerRequest->submission_method])
                        </p>
                    </div>
                    <div class="col-md-3 col-6">
                        <p class="detail-label">Crew Members</p>
                        <p class="detail-value">{{ $partnerRequest->items->count() }}</p>
                    </div>
                </div>

                @if($partnerRequest->partner_updated_at)
                    <div class="alert alert-info d-flex align-items-center mb-4" role="status">
                        <i class="ri-information-line me-2" aria-hidden="true"></i>
                        <span>Last updated: {{ $partnerRequest->partner_updated_at->format('M d, Y g:i A') }}</span>
                    </div>
                @endif

                <hr class="my-4">

                @if($partnerRequest->isImage())
                    <h6 class="partner-section-title">
                        <i class="ri-image-line text-primary" aria-hidden="true"></i>
                        Uploaded Schedule
                    </h6>
                    <div class="alert alert-info mb-4" role="status">
                        <i class="ri-information-line me-2" aria-hidden="true"></i>
                        Your schedule has been submitted. Manage Trans will review and process the transportation details.
                    </div>
                    <div class="partner-image-preview-wrap mb-4">
                        <img src="{{ route('partner.requests.image', $partnerRequest) }}"
                             alt="Schedule for {{ $partnerRequest->request_reference }}"
                             class="img-fluid rounded shadow-sm">
                    </div>
                @else
                    <h6 class="partner-section-title">
                        <i class="ri-team-line text-primary" aria-hidden="true"></i>
                        Crew Details ({{ $partnerRequest->items->count() }} {{ Str::plural('member', $partnerRequest->items->count()) }})
                    </h6>

                    @foreach($partnerRequest->items as $index => $item)
                        <div class="partner-crew-detail-card">
                            <h6 class="mb-3 d-flex align-items-center">
                                <i class="ri-user-3-line text-primary me-2" aria-hidden="true"></i>
                                Crew #{{ $index + 1 }}
                            </h6>

                            <div class="row g-3 partner-detail-grid">
                                <div class="col-md-4 col-sm-6">
                                    <p class="detail-label">Trip Date</p>
                                    <p class="detail-value">{{ $item->trip_date ? \Carbon\Carbon::parse($item->trip_date)->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <p class="detail-label">Name</p>
                                    <p class="detail-value text-break-safe">{{ $item->name ?? 'N/A' }}</p>
                                </div>
                                @if($item->phone)
                                    <div class="col-md-4 col-sm-6">
                                        <p class="detail-label">Phone</p>
                                        <p class="detail-value">{{ $item->phone }}</p>
                                    </div>
                                @endif
                                <div class="col-md-4 col-sm-6">
                                    <p class="detail-label">Vessel</p>
                                    <p class="detail-value text-break-safe">{{ $item->vessel ? $item->vessel->name : 'Not specified' }}</p>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <p class="detail-label">From Location</p>
                                    <p class="detail-value text-break-safe">{{ $item->from_location ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <p class="detail-label">To Location</p>
                                    <p class="detail-value text-break-safe">{{ $item->to_location ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                <div class="mt-4 pt-2 border-top">
                    <a href="{{ route('partner.requests.index') }}" class="btn btn-light btn-touch">
                        <i class="ri-arrow-left-line align-middle me-1"></i> Back to My Requests
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
