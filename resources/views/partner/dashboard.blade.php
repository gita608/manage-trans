@extends('layouts.partner')

@section('title', 'Dashboard - Partner Portal')

@section('content')
@php
    $partnerNav = Auth::guard('partner')->user()->partner;
    $canSubmitRequests = $partnerNav->allow_manual_submission || $partnerNav->allow_image_submission;
    $initials = collect(explode(' ', $partnerUser->name))
        ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
        ->take(2)
        ->join('');
@endphp

<!-- Welcome -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card partner-welcome-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="partner-welcome-avatar" aria-hidden="true">{{ $initials }}</div>
                    <div class="flex-grow-1 flex-text-safe min-width-0">
                        <p class="partner-welcome-eyebrow mb-1">Dashboard</p>
                        <h4 class="mb-1">Welcome, {{ $partnerUser->name }}</h4>
                        <p class="partner-welcome-subtitle mb-0 text-break-safe">{{ $partner->title }}</p>
                    </div>
                    @if($canSubmitRequests)
                        <div class="flex-shrink-0 w-100 w-sm-auto">
                            <a href="{{ route('partner.requests.new') }}" class="btn btn-light btn-touch partner-welcome-cta w-100 w-sm-auto">
                                <i class="ri-add-line align-middle me-1" aria-hidden="true"></i> Create New Request
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request Status Cards -->
<div class="row g-3 mb-1">
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('partner.requests.index', ['status' => 'pending']) }}" class="partner-status-card text-decoration-none" aria-label="View {{ $pendingCount }} pending requests">
            <div class="card card-animate h-100 partner-stat-card partner-stat-card--warning">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1 flex-text-safe">
                            <p class="text-uppercase fw-medium text-muted mb-2">Pending Review</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2">{{ $pendingCount }}</h4>
                            <p class="text-muted mb-0 small">Being reviewed by Manage Trans</p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-warning-subtle rounded fs-3">
                                    <i class="ri-time-line text-warning" aria-hidden="true"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-3 col-md-6">
        <a href="{{ route('partner.requests.index', ['status' => 'approved']) }}" class="partner-status-card text-decoration-none" aria-label="View {{ $approvedCount }} approved requests">
            <div class="card card-animate h-100 partner-stat-card partner-stat-card--success">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1 flex-text-safe">
                            <p class="text-uppercase fw-medium text-muted mb-2">Approved</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2">{{ $approvedCount }}</h4>
                            <p class="text-muted mb-0 small">Ready for transportation</p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-success-subtle rounded fs-3">
                                    <i class="ri-checkbox-circle-line text-success" aria-hidden="true"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-3 col-md-6">
        <a href="{{ route('partner.requests.index', ['status' => 'declined']) }}" class="partner-status-card text-decoration-none" aria-label="View {{ $declinedCount }} declined requests">
            <div class="card card-animate h-100 partner-stat-card partner-stat-card--danger">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1 flex-text-safe">
                            <p class="text-uppercase fw-medium text-muted mb-2">Declined</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2">{{ $declinedCount }}</h4>
                            <p class="text-muted mb-0 small">Could not be processed</p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-danger-subtle rounded fs-3">
                                    <i class="ri-close-circle-line text-danger" aria-hidden="true"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-3 col-md-6">
        <a href="{{ route('partner.requests.index', ['status' => 'withdrawn']) }}" class="partner-status-card text-decoration-none" aria-label="View {{ $withdrawnCount }} withdrawn requests">
            <div class="card card-animate h-100 partner-stat-card partner-stat-card--secondary">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1 flex-text-safe">
                            <p class="text-uppercase fw-medium text-muted mb-2">Withdrawn</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2">{{ $withdrawnCount }}</h4>
                            <p class="text-muted mb-0 small">Cancelled by you</p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-secondary-subtle rounded fs-3">
                                    <i class="ri-arrow-go-back-line text-secondary" aria-hidden="true"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Recent Requests -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="card-title mb-0">Recent Requests</h5>
                <a href="{{ route('partner.requests.index') }}" class="btn btn-sm btn-soft-primary">
                    View All <i class="ri-arrow-right-line align-middle ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                @if($recentRequests->count() > 0)
                    <!-- Desktop Table -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Reference</th>
                                    <th scope="col">Method</th>
                                    <th scope="col">Crew Items</th>
                                    <th scope="col">Submitted</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentRequests as $request)
                                    <tr>
                                        <td>
                                            <a href="{{ route('partner.requests.show', $request) }}" class="fw-medium text-break-safe">
                                                {{ $request->request_reference }}
                                            </a>
                                        </td>
                                        <td>
                                            @include('partner.partials.method-badge', ['method' => $request->submission_method])
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $request->items->count() }}</span>
                                        </td>
                                        <td>{{ $request->submitted_at ? $request->submitted_at->format('M d, Y') : 'N/A' }}</td>
                                        <td>
                                            @include('partner.partials.status-badge', ['status' => $request->status, 'withIcon' => false])
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('partner.requests.show', $request) }}" class="btn btn-sm btn-soft-primary btn-touch">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="d-md-none">
                        @foreach($recentRequests as $request)
                            <div class="request-card">
                                <div class="request-card-header">
                                    <a href="{{ route('partner.requests.show', $request) }}" class="request-card-reference text-break-safe">
                                        {{ $request->request_reference }}
                                    </a>
                                    @include('partner.partials.status-badge', ['status' => $request->status, 'withIcon' => false])
                                </div>
                                <div class="request-card-body">
                                    <div class="request-card-row">
                                        <span class="request-card-label">Method</span>
                                        <span class="request-card-value">
                                            @include('partner.partials.method-badge', ['method' => $request->submission_method])
                                        </span>
                                    </div>
                                    <div class="request-card-row">
                                        <span class="request-card-label">Crew Items</span>
                                        <span class="request-card-value">{{ $request->items->count() }}</span>
                                    </div>
                                    <div class="request-card-row">
                                        <span class="request-card-label">Submitted</span>
                                        <span class="request-card-value">{{ $request->submitted_at ? $request->submitted_at->format('M d, Y') : 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="request-card-footer">
                                    <a href="{{ route('partner.requests.show', $request) }}" class="btn btn-sm btn-primary w-100 btn-touch">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @include('partner.partials.empty-state', [
                        'icon' => 'ri-file-list-3-line',
                        'title' => 'No requests submitted yet',
                        'message' => $canSubmitRequests
                            ? 'Get started by creating your first transportation request.'
                            : 'Transportation request submission will be available soon.',
                    ])
                    @if($canSubmitRequests)
                        <div class="text-center">
                            <a href="{{ route('partner.requests.new') }}" class="btn btn-primary btn-touch">
                                <i class="ri-add-line align-middle me-1"></i> Create Your First Request
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection