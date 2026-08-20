@extends('layouts.app')

@section('title', 'Partner Requests | ' . config('app.name'))

@push('styles')
<link href="{{ assetVersioned('assets/css/partner-review.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="partner-review-page">
@include('partials.page-header', [
    'title' => 'Partner Requests',
    'subtitle' => 'Review and approve partner transportation submissions.',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Partner Requests'],
    ],
])

@if($status === 'pending' && $partnerRequests->total() > 0)
    <div class="partner-review-queue-banner mb-3" role="status">
        <i class="ri-time-line" aria-hidden="true"></i>
        <div>
            <strong>{{ $partnerRequests->total() }} {{ Str::plural('request', $partnerRequests->total()) }} awaiting review</strong>
            <span class="d-block small text-muted mt-1">Pending requests are highlighted below. Open a request to review crew details and approve or decline.</span>
        </div>
    </div>
@elseif(isset($pendingCount) && $pendingCount > 0 && $status !== 'pending')
    <div class="partner-review-queue-banner mb-3" role="status">
        <i class="ri-time-line" aria-hidden="true"></i>
        <div>
            <strong>{{ $pendingCount }} pending {{ Str::plural('request', $pendingCount) }} need review</strong>
            <a href="{{ route('partner-requests.index', ['status' => 'pending']) }}" class="ms-1">View pending queue</a>
        </div>
    </div>
@endif

<div class="card partner-review-card">
    <div class="card-header partner-review-card-header">
        <div class="partner-review-card-header-row">
            <h5 class="card-title mb-0 partner-review-card-header-title">
                Request Queue
                <span class="partner-review-card-header-meta">
                    @if($status === 'all')
                        · All requests
                    @else
                        · {{ ucfirst($status) }}
                    @endif
                    @if($submissionMethod !== 'all')
                        · {{ ucfirst($submissionMethod) }}
                    @endif
                </span>
            </h5>
            <form method="GET" action="{{ route('partner-requests.index') }}" class="partner-review-filter-toolbar">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="submission_method" value="{{ $submissionMethod }}">
                <select name="partner_id" class="form-select form-select-sm partner-review-filter-select" onchange="this.form.submit()" aria-label="Filter by partner">
                    <option value="">All Partners</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" @selected((string) $partnerId === (string) $partner->id)>{{ $partner->title }}</option>
                    @endforeach
                </select>
                <input type="search" name="search" value="{{ $search }}" class="form-control form-control-sm partner-review-filter-search" placeholder="Search REQ reference" aria-label="Search requests">
                <button type="submit" class="btn btn-sm btn-primary partner-review-filter-submit">
                    <i class="ri-search-line me-1"></i><span class="d-none d-xl-inline">Search</span>
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs nav-tabs-custom nav-primary mb-3 partner-review-filter-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}"
                   href="{{ route('partner-requests.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}"
                   role="tab"
                   aria-current="{{ $status === 'pending' ? 'page' : 'false' }}">
                    <i class="ri-time-line me-1"></i> Pending
                    @if($status !== 'pending' && isset($pendingCount) && $pendingCount > 0)
                        <span class="badge bg-warning ms-1">{{ $pendingCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $status === 'approved' ? 'active' : '' }}"
                   href="{{ route('partner-requests.index', array_merge(request()->except('page'), ['status' => 'approved'])) }}"
                   role="tab"
                   aria-current="{{ $status === 'approved' ? 'page' : 'false' }}">
                    <i class="ri-checkbox-circle-line me-1"></i> Approved
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $status === 'declined' ? 'active' : '' }}"
                   href="{{ route('partner-requests.index', array_merge(request()->except('page'), ['status' => 'declined'])) }}"
                   role="tab"
                   aria-current="{{ $status === 'declined' ? 'page' : 'false' }}">
                    <i class="ri-close-circle-line me-1"></i> Declined
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $status === 'withdrawn' ? 'active' : '' }}"
                   href="{{ route('partner-requests.index', array_merge(request()->except('page'), ['status' => 'withdrawn'])) }}"
                   role="tab"
                   aria-current="{{ $status === 'withdrawn' ? 'page' : 'false' }}">
                    <i class="ri-arrow-go-back-line me-1"></i> Withdrawn
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $status === 'all' ? 'active' : '' }}"
                   href="{{ route('partner-requests.index', array_merge(request()->except('page'), ['status' => 'all'])) }}"
                   role="tab"
                   aria-current="{{ $status === 'all' ? 'page' : 'false' }}">
                    <i class="ri-file-list-line me-1"></i> All
                </a>
            </li>
        </ul>

        <div class="d-flex flex-wrap gap-2 mb-4 partner-review-method-filters">
            <span class="text-muted small align-self-center me-1">Method:</span>
            @foreach(['all' => 'All Methods', 'manual' => 'Manual', 'image' => 'Image'] as $methodValue => $methodLabel)
                <a href="{{ route('partner-requests.index', array_merge(request()->except('page'), ['submission_method' => $methodValue])) }}"
                   class="btn btn-sm {{ $submissionMethod === $methodValue ? 'btn-primary' : 'btn-soft-primary' }}">
                    @if($methodValue === 'manual')
                        <i class="ri-edit-line me-1"></i>
                    @elseif($methodValue === 'image')
                        <i class="ri-image-line me-1"></i>
                    @endif
                    {{ $methodLabel }}
                </a>
            @endforeach
        </div>

        <div class="table-responsive d-none d-lg-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Partner</th>
                        <th scope="col">Method</th>
                        <th scope="col">Submitted</th>
                        <th scope="col">Crew</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partnerRequests as $partnerRequest)
                        <tr class="{{ $partnerRequest->isPending() ? 'partner-request-row-pending' : '' }}">
                            <td>
                                <span class="partner-request-reference text-break-safe">{{ $partnerRequest->request_reference }}</span>
                            </td>
                            <td class="text-break-safe">{{ $partnerRequest->partner->title ?? 'N/A' }}</td>
                            <td>
                                @if($partnerRequest->submission_method === 'manual')
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="ri-edit-line me-1"></i> Manual
                                    </span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary">
                                        <i class="ri-image-line me-1"></i> Image
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted">{{ $partnerRequest->submitted_at?->format('M d, g:i A') ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $partnerRequest->items_count }}</span>
                            </td>
                            <td>
                                @include('partner-requests.partials.status-badge', ['status' => $partnerRequest->status])
                            </td>
                            <td class="text-end">
                                <a href="{{ route('partner-requests.show', $partnerRequest) }}"
                                   class="btn btn-sm {{ $partnerRequest->isPending() ? 'btn-primary' : 'btn-soft-primary' }}">
                                    <i class="ri-{{ $partnerRequest->isPending() ? 'search-eye' : 'eye' }}-line me-1"></i>
                                    {{ $partnerRequest->isPending() ? 'Review' : 'View' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="partner-review-empty">
                                    <div class="partner-review-empty-icon">
                                        <i class="ri-inbox-line" aria-hidden="true"></i>
                                    </div>
                                    <h6>No requests found</h6>
                                    <p class="text-muted mb-0 small">No {{ $status !== 'all' ? $status : '' }} partner requests match your filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-lg-none">
            @forelse($partnerRequests as $partnerRequest)
                <div class="partner-review-queue-card {{ $partnerRequest->isPending() ? 'partner-request-row-pending' : '' }}">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <span class="partner-request-reference fw-bold text-break-safe">{{ $partnerRequest->request_reference }}</span>
                        @include('partner-requests.partials.status-badge', ['status' => $partnerRequest->status])
                    </div>
                    <div class="row g-2 small mb-3">
                        <div class="col-6">
                            <span class="text-muted d-block">Partner</span>
                            <span class="fw-medium text-break-safe">{{ $partnerRequest->partner->title ?? 'N/A' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">Method</span>
                            @if($partnerRequest->submission_method === 'manual')
                                <span class="badge bg-info-subtle text-info mt-1">Manual</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary mt-1">Image</span>
                            @endif
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">Submitted</span>
                            <span>{{ $partnerRequest->submitted_at?->format('M d, g:i A') ?? 'N/A' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">Crew</span>
                            <span>{{ $partnerRequest->items_count }}</span>
                        </div>
                    </div>
                    <a href="{{ route('partner-requests.show', $partnerRequest) }}"
                       class="btn btn-sm {{ $partnerRequest->isPending() ? 'btn-primary' : 'btn-soft-primary' }} w-100">
                        <i class="ri-{{ $partnerRequest->isPending() ? 'search-eye' : 'eye' }}-line me-1"></i>
                        {{ $partnerRequest->isPending() ? 'Review' : 'View' }}
                    </a>
                </div>
            @empty
                <div class="partner-review-empty">
                    <div class="partner-review-empty-icon">
                        <i class="ri-inbox-line" aria-hidden="true"></i>
                    </div>
                    <h6>No requests found</h6>
                    <p class="text-muted mb-0 small">No {{ $status !== 'all' ? $status : '' }} partner requests match your filters.</p>
                </div>
            @endforelse
        </div>

        @if($partnerRequests->hasPages())
            <div class="mt-4">
                {{ $partnerRequests->links() }}
            </div>
        @endif
    </div>
</div>
</div>
@endsection
