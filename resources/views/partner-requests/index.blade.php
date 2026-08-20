@extends('layouts.app')

@section('title', 'Partner Requests | ' . config('app.name'))

@push('styles')
<link href="{{ assetVersioned('assets/css/partner-review.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="partner-review-page" data-partner-request-queue-page data-partner-request-status="{{ $status }}">
@include('partials.page-header', [
    'title' => 'Partner Requests',
    'subtitle' => 'Review and approve partner transportation submissions.',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Partner Requests'],
    ],
])

<div data-partner-request-live-part="banner">
    @include('partner-requests.partials.queue-banner')
</div>

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
                    <span class="badge bg-warning ms-1"
                          data-partner-request-pending-tab-badge
                          @if($status === 'pending' || ($pendingCount ?? 0) < 1) hidden @endif>{{ $pendingCount ?? 0 }}</span>
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

        <div data-partner-request-live-part="results" aria-live="polite">
            @include('partner-requests.partials.queue-results')
        </div>
    </div>
</div>
</div>
@endsection
