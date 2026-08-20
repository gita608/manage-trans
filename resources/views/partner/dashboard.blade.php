@extends('layouts.partner')

@section('title', 'Dashboard - Partner Portal')

@section('content')
<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Dashboard</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="flex-grow-1">
                        <h4 class="mb-2">Welcome, {{ $partnerUser->name }}</h4>
                        <p class="text-muted mb-0">{{ $partner->title }}</p>
                    </div>
                    @if($partner->allow_manual_submission)
                        <div class="flex-shrink-0">
                            <a href="{{ route('partner.requests.create') }}" class="btn btn-primary">
                                <i class="ri-add-line align-middle me-1"></i> Create New Request
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request Status Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Pending</p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="badge bg-warning-subtle text-warning fs-12">{{ $pendingCount }}</span>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-2">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $pendingCount }}</h4>
                        <a href="{{ route('partner.requests.index', ['status' => 'pending']) }}" class="text-decoration-underline text-muted">View All</a>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-3">
                            <i class="ri-time-line text-warning"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Approved</p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="badge bg-success-subtle text-success fs-12">{{ $approvedCount }}</span>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-2">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $approvedCount }}</h4>
                        <a href="{{ route('partner.requests.index', ['status' => 'approved']) }}" class="text-decoration-underline text-muted">View All</a>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-3">
                            <i class="ri-checkbox-circle-line text-success"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Declined</p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="badge bg-danger-subtle text-danger fs-12">{{ $declinedCount }}</span>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-2">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $declinedCount }}</h4>
                        <a href="{{ route('partner.requests.index', ['status' => 'declined']) }}" class="text-decoration-underline text-muted">View All</a>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded fs-3">
                            <i class="ri-close-circle-line text-danger"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Withdrawn</p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="badge bg-secondary-subtle text-secondary fs-12">{{ $withdrawnCount }}</span>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-2">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $withdrawnCount }}</h4>
                        <a href="{{ route('partner.requests.index', ['status' => 'withdrawn']) }}" class="text-decoration-underline text-muted">View All</a>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-secondary-subtle rounded fs-3">
                            <i class="ri-arrow-go-back-line text-secondary"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Requests -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recent Requests</h5>
                <a href="{{ route('partner.requests.index') }}" class="btn btn-sm btn-soft-primary">View All</a>
            </div>
            <div class="card-body">
                @if($recentRequests->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Method</th>
                                    <th>Crew Items</th>
                                    <th>Submitted</th>
                                    <th>Submitted By</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentRequests as $request)
                                    <tr>
                                        <td>
                                            <a href="{{ route('partner.requests.show', $request) }}" class="fw-medium">
                                                {{ $request->request_reference }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($request->submission_method === 'manual')
                                                <span class="badge bg-info-subtle text-info">Manual</span>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary">Image</span>
                                            @endif
                                        </td>
                                        <td>{{ $request->items->count() }} Crew</td>
                                        <td>{{ $request->submitted_at ? $request->submitted_at->format('M d, Y g:i A') : 'N/A' }}</td>
                                        <td>{{ $request->partnerUser->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($request->status === 'pending')
                                                <span class="badge bg-warning-subtle text-warning">Pending</span>
                                            @elseif($request->status === 'approved')
                                                <span class="badge bg-success-subtle text-success">Approved</span>
                                            @elseif($request->status === 'declined')
                                                <span class="badge bg-danger-subtle text-danger">Declined</span>
                                            @elseif($request->status === 'withdrawn')
                                                <span class="badge bg-secondary-subtle text-secondary">Withdrawn</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('partner.requests.show', $request) }}" class="btn btn-sm btn-soft-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="ri-file-list-3-line display-4 text-muted"></i>
                        </div>
                        <h5 class="mb-3">No requests submitted yet</h5>
                        @if($partner->allow_manual_submission)
                            <p class="text-muted mb-3">Get started by creating your first transportation request.</p>
                            <a href="{{ route('partner.requests.create') }}" class="btn btn-primary">
                                <i class="ri-add-line align-middle me-1"></i> Create Your First Request
                            </a>
                        @else
                            <p class="text-muted mb-0">Transportation request submission will be available soon.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
