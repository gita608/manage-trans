@extends('layouts.app')

@section('title', $partnerRequest->request_reference . ' | Partner Requests')

@section('content')
@include('partials.page-header', [
    'title' => $partnerRequest->request_reference,
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Partner Requests', 'url' => route('partner-requests.index')],
        ['label' => $partnerRequest->request_reference],
    ],
])

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('approval_errors'))
    <div class="alert alert-danger">
        <strong>Approval could not be completed:</strong>
        <ul class="mb-0 mt-2">
            @foreach(session('approval_errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <p class="text-muted mb-1">Partner</p>
                <p class="fw-medium mb-0">{{ $partnerRequest->partner->title }}</p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Submitted By</p>
                <p class="fw-medium mb-0">{{ $partnerRequest->partnerUser->name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Submitted</p>
                <p class="fw-medium mb-0">{{ $partnerRequest->submitted_at?->format('M d, Y g:i A') ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Method</p>
                <p class="mb-0">
                    @if($partnerRequest->isManual())
                        <span class="badge bg-info-subtle text-info">Manual</span>
                    @else
                        <span class="badge bg-primary-subtle text-primary">Image</span>
                    @endif
                </p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Status</p>
                <p class="mb-0">@include('partner-requests.partials.status-badge', ['status' => $partnerRequest->status])</p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Partner Last Updated</p>
                <p class="fw-medium mb-0">{{ $partnerRequest->partner_updated_at?->format('M d, Y g:i A') ?? '—' }}</p>
            </div>
            @if($partnerRequest->isImage())
                <div class="col-md-3">
                    <p class="text-muted mb-1">Extraction Status</p>
                    <p class="mb-0">
                        @if($partnerRequest->extraction_status === 'completed')
                            <span class="badge bg-success-subtle text-success">Completed</span>
                        @elseif($partnerRequest->extraction_status === 'failed')
                            <span class="badge bg-danger-subtle text-danger">Failed</span>
                        @elseif($partnerRequest->extraction_status === 'processing')
                            <span class="badge bg-warning-subtle text-warning">Processing</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </p>
                </div>
            @endif
        </div>

        @if($partnerRequest->isDeclined() && $partnerRequest->decline_reason)
            <div class="alert alert-danger mt-3 mb-0">
                <strong>Decline Reason:</strong> {{ $partnerRequest->decline_reason }}
            </div>
        @endif

        @if($partnerRequest->isApproved())
            <div class="alert alert-success mt-3 mb-0">
                Approved by {{ $partnerRequest->approvedBy->name ?? 'N/A' }}
                on {{ $partnerRequest->approved_at?->format('M d, Y g:i A') }}.
            </div>
        @endif
    </div>
</div>

@if($partnerRequest->isImage())
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Uploaded Schedule</h5>
        </div>
        <div class="card-body">
            @if($partnerRequest->extraction_status === 'failed')
                <div class="alert alert-warning">
                    Automatic extraction was unsuccessful. Review the uploaded schedule and enter the transportation details manually.
                </div>
            @endif
            <img src="{{ route('partner-requests.image', $partnerRequest) }}"
                 alt="Uploaded schedule for {{ $partnerRequest->request_reference }}"
                 class="img-fluid rounded border"
                 style="max-height: 480px;">
        </div>
    </div>
@endif

@if($partnerRequest->isApproved() && $partnerRequest->trips->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Created Trips</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Driver</th>
                            <th>Date</th>
                            <th>Crew</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($partnerRequest->trips as $trip)
                            <tr>
                                <td><a href="{{ route('trips.show', $trip) }}">{{ $trip->trip_reference }}</a></td>
                                <td>{{ $trip->driver->name ?? 'Unassigned' }}</td>
                                <td>{{ $trip->trip_date ? \Carbon\Carbon::parse($trip->trip_date)->format('M d, Y') : 'N/A' }}</td>
                                <td>{{ $trip->crews->count() }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<form action="{{ route('partner-requests.update', $partnerRequest) }}" method="POST" id="reviewForm">
    @csrf
    @method('PUT')
    <input type="hidden" name="request_version" value="{{ $requestVersion }}">

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title mb-0">Crew Review</h5>
            @if($canEdit)
                <button type="button" class="btn btn-sm btn-success" id="addCrewBtn">
                    <i class="ri-add-line"></i> Add Crew
                </button>
            @endif
        </div>
        <div class="card-body">
            <div id="crew-items-container">
                @forelse($partnerRequest->items as $index => $item)
                    @include('partner-requests.partials.crew-item', [
                        'index' => $index,
                        'item' => $item,
                        'drivers' => $drivers,
                        'vessels' => $vessels,
                        'canEdit' => $canEdit,
                        'isImage' => $partnerRequest->isImage(),
                    ])
                @empty
                    @if(!$canEdit)
                        <p class="text-muted mb-0">No crew items recorded.</p>
                    @endif
                @endforelse
            </div>
        </div>
    </div>

    @if($canEdit)
        <div class="d-flex flex-wrap gap-2 justify-content-between mb-4">
            <a href="{{ route('partner-requests.index') }}" class="btn btn-light">Back to Queue</a>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Save Review</button>
                @if(auth()->user()->hasPermission('create_trips'))
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">Approve</button>
                @endif
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#declineModal">Decline</button>
            </div>
        </div>
    @else
        <div class="mb-4">
            <a href="{{ route('partner-requests.index') }}" class="btn btn-light">Back to Queue</a>
        </div>
    @endif
</form>

@if($canEdit)
    <form action="{{ route('partner-requests.approve', $partnerRequest) }}" method="POST" id="approveForm">
        @csrf
        <input type="hidden" name="request_version" value="{{ $requestVersion }}">
    </form>

    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Approve this request and create operational trips grouped by driver and date?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="document.getElementById('approveForm').submit();">Approve</button>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('partner-requests.decline', $partnerRequest) }}" method="POST" id="declineForm">
        @csrf
        <input type="hidden" name="request_version" value="{{ $requestVersion }}">
        <div class="modal fade" id="declineModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Decline Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Decline Reason <span class="text-danger">*</span></label>
                        <textarea name="decline_reason" class="form-control" rows="4" required maxlength="2000"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Decline Request</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endif

<template id="crew-item-template">
    @include('partner-requests.partials.crew-item', [
        'index' => '__INDEX__',
        'item' => null,
        'drivers' => $drivers,
        'vessels' => $vessels,
        'canEdit' => true,
        'isImage' => $partnerRequest->isImage(),
    ])
</template>
@endsection

@push('scripts')
@if($canEdit)
<script>
document.addEventListener('DOMContentLoaded', function () {
    let crewIndex = {{ max($partnerRequest->items->count(), 0) }};
    const container = document.getElementById('crew-items-container');
    const template = document.getElementById('crew-item-template');
    const addBtn = document.getElementById('addCrewBtn');

    function renumberCrewItems() {
        container.querySelectorAll('.crew-item').forEach((item, index) => {
            const number = item.querySelector('.crew-number');
            if (number) number.textContent = index + 1;
        });
    }

    addBtn?.addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__INDEX__', crewIndex);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        container.appendChild(wrapper.firstElementChild);
        crewIndex++;
        renumberCrewItems();
    });

    container.addEventListener('click', function (event) {
        const removeBtn = event.target.closest('.remove-crew-btn');
        if (!removeBtn) return;
        removeBtn.closest('.crew-item')?.remove();
        renumberCrewItems();
    });
});
</script>
@endif
@endpush
