@extends('layouts.app')

@section('title', $partnerRequest->request_reference . ' | Partner Requests')

@push('styles')
<link href="{{ assetVersioned('assets/css/partner-review.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="partner-review-page">
@include('partials.page-header', [
    'title' => 'Review Request',
    'subtitle' => $partnerRequest->request_reference . ' · ' . ($partnerRequest->partner->title ?? 'Partner'),
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Partner Requests', 'url' => route('partner-requests.index')],
        ['label' => $partnerRequest->request_reference],
    ],
])

<div class="partner-request-summary partner-review-card mb-4">
    <div class="summary-grid">
        <div class="summary-item">
            <label>Partner</label>
            <div class="value">{{ $partnerRequest->partner->title }}</div>
        </div>
        <div class="summary-item">
            <label>Submitted By</label>
            <div class="value">{{ $partnerRequest->partnerUser->name ?? 'N/A' }}</div>
        </div>
        <div class="summary-item">
            <label>Submitted</label>
            <div class="value">{{ $partnerRequest->submitted_at?->format('M d, Y g:i A') ?? 'N/A' }}</div>
        </div>
        <div class="summary-item">
            <label>Method</label>
            <div class="value">
                @if($partnerRequest->isManual())
                    <span class="badge bg-info-subtle text-info"><i class="ri-edit-line me-1"></i> Manual</span>
                @else
                    <span class="badge bg-primary-subtle text-primary"><i class="ri-image-line me-1"></i> Image</span>
                @endif
            </div>
        </div>
        <div class="summary-item">
            <label>Status</label>
            <div class="value">
                @include('partner-requests.partials.status-badge', ['status' => $partnerRequest->status])
            </div>
        </div>
        @if($partnerRequest->partner_updated_at)
            <div class="summary-item">
                <label>Partner Last Updated</label>
                <div class="value">{{ $partnerRequest->partner_updated_at->format('M d, g:i A') }}</div>
            </div>
        @endif
        @if($partnerRequest->isImage())
            <div class="summary-item">
                <label>Extraction</label>
                <div class="value">
                    @if($partnerRequest->extraction_status === 'completed')
                        <span class="badge bg-success-subtle text-success">Completed</span>
                    @elseif($partnerRequest->extraction_status === 'failed')
                        <span class="badge bg-danger-subtle text-danger">Failed</span>
                    @elseif($partnerRequest->extraction_status === 'processing')
                        <span class="badge bg-warning-subtle text-warning">Processing</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if($partnerRequest->isDeclined() && $partnerRequest->decline_reason)
        <div class="alert alert-danger mt-3 mb-0">
            <strong><i class="ri-error-warning-line me-2"></i>Decline Reason:</strong>
            <div class="mt-2 p-3 bg-light-subtle rounded border">{{ $partnerRequest->decline_reason }}</div>
        </div>
    @endif

    @if($partnerRequest->isApproved())
        <div class="alert alert-success mt-3 mb-0">
            <i class="ri-checkbox-circle-line me-2"></i>
            Approved by <strong>{{ $partnerRequest->approvedBy->name ?? 'N/A' }}</strong>
            on {{ $partnerRequest->approved_at?->format('M d, Y g:i A') }}.
        </div>
        @if($partnerRequest->trips->isEmpty())
            <div class="alert alert-info mt-3 mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <strong>Approved — Awaiting Trip Creation</strong>
                    <p class="mb-0 small mt-1">Operational trips have not been created yet. Continue in the normal trip workflow.</p>
                </div>
                @if($canCreateTrip)
                    <a href="{{ route('trips.create-from-partner-request', $partnerRequest) }}" class="btn btn-primary btn-sm">
                        <i class="ri-add-line me-1"></i> Create Trip
                    </a>
                @endif
            </div>
        @endif
    @endif
</div>

@if($partnerRequest->isImage())
    <div class="image-review-container">
        <div class="image-review-main">
            @if($partnerRequest->extraction_status === 'failed')
                <div class="alert alert-warning extraction-failed-alert d-flex align-items-start mb-4">
                    <i class="ri-error-warning-line alert-icon me-3"></i>
                    <div>
                        <strong>Automatic extraction was unsuccessful.</strong>
                        <p class="mb-0 mt-1">The source image is preserved for reference. Approve the request, then enter operational details in trip creation.</p>
                    </div>
                </div>
            @endif

            @include('partner-requests.partials.submission-items')

            @if($canDecide)
                @include('partner-requests.partials.decision-actions')
            @endif
        </div>

        <div class="image-review-preview">
            <div class="card partner-review-card">
                <div class="card-header py-2">
                    <h6 class="card-title mb-0 d-flex align-items-center gap-2">
                        <span class="partner-review-card-header-icon"><i class="ri-image-line" aria-hidden="true"></i></span>
                        Source Schedule
                    </h6>
                </div>
                <div class="card-body p-2">
                    <div class="image-preview-controls">
                        <a href="{{ route('partner-requests.image', $partnerRequest) }}" target="_blank" class="btn btn-sm btn-soft-primary">
                            <i class="ri-external-link-line me-1"></i> View Source Schedule
                        </a>
                    </div>
                    <img src="{{ route('partner-requests.image', $partnerRequest) }}"
                         alt="Schedule for {{ $partnerRequest->request_reference }}"
                         class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>
@else
    @include('partner-requests.partials.submission-items')

    @if($canDecide)
        @include('partner-requests.partials.decision-actions')
    @else
        <div class="mb-4">
            <a href="{{ route('partner-requests.index') }}" class="btn btn-light">
                <i class="ri-arrow-left-line me-1"></i> Back to Queue
            </a>
        </div>
    @endif
@endif

@if($partnerRequest->isApproved() && $partnerRequest->trips->count() > 0)
    <div class="card partner-review-card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                <span class="partner-review-card-header-icon"><i class="ri-ship-line" aria-hidden="true"></i></span>
                Created Trips
            </h5>
        </div>
        <div class="card-body">
            <ul class="created-trips-list">
                @foreach($partnerRequest->trips as $trip)
                    <li>
                        <a href="{{ route('trips.show', $trip) }}" class="trip-reference">{{ $trip->trip_reference }}</a>
                        <div class="trip-meta">
                            <span class="trip-meta-item"><i class="ri-user-line"></i>{{ $trip->driver->name ?? 'Unassigned' }}</span>
                            <span class="trip-meta-item"><i class="ri-calendar-line"></i>{{ $trip->trip_date ? \Carbon\Carbon::parse($trip->trip_date)->format('M d, Y') : 'N/A' }}</span>
                            <span class="trip-meta-item"><i class="ri-team-line"></i>{{ $trip->crews->count() }} crew</span>
                            <span class="trip-meta-item">
                                <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</span>
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if($canDecide)
    <div class="modal fade modal-approve" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Approve Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Approve this request and continue to operational Trip setup?</p>
                    <div class="approval-info d-flex align-items-start">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            <strong>Approval records the decision only</strong>
                            <p class="mb-0 mt-1 small">No trips are created yet. You will enter driver, vessel, pickup time, and crew details on the next screen.</p>
                        </div>
                    </div>
                    <p class="mb-0 mt-3 text-muted small">Request: <strong>{{ $partnerRequest->request_reference }}</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="approveBtn">
                        <i class="ri-checkbox-circle-line me-1"></i> Approve Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-decline" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="declineModalLabel">Decline Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="decline-warning d-flex align-items-start mb-3">
                        <i class="ri-error-warning-line"></i>
                        <div>
                            <strong>Request will be marked as declined</strong>
                            <p class="mb-0 mt-1 small">The submission history is preserved. The partner will see your decline reason.</p>
                        </div>
                    </div>
                    <label class="form-label" for="decline_reason">Decline Reason<span class="text-danger ms-1">*</span></label>
                    <textarea name="decline_reason" id="decline_reason" class="form-control" rows="4" required maxlength="2000" placeholder="Provide a clear reason for declining this request..."></textarea>
                    <div class="char-count"><span id="charCount">0</span> / 2000 characters</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="declineBtn">
                        <i class="ri-close-circle-line me-1"></i> Decline Request
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
</div>
@endsection

@push('scripts')
@if($canDecide)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const approveBtn = document.getElementById('approveBtn');
    const approveForm = document.getElementById('approveForm');
    approveBtn?.addEventListener('click', function() {
        approveBtn.disabled = true;
        approveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Approving...';
        approveForm.submit();
    });

    const declineTextarea = document.getElementById('decline_reason');
    const declineBtn = document.getElementById('declineBtn');
    const declineForm = document.getElementById('declineForm');
    const declineHidden = document.getElementById('decline_reason_hidden');
    const charCount = document.getElementById('charCount');

    declineTextarea?.addEventListener('input', function() {
        charCount.textContent = this.value.length;
        declineBtn.disabled = this.value.trim().length === 0;
    });

    declineBtn?.addEventListener('click', function() {
        const reason = declineTextarea.value.trim();
        if (!reason) return;
        declineHidden.value = reason;
        declineBtn.disabled = true;
        declineBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Declining...';
        declineForm.submit();
    });
});
</script>
@endif
@endpush
