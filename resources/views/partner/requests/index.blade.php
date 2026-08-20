@extends('layouts.partner')

@section('title', 'My Requests - Partner Portal')

@section('content')
@include('partner.partials.page-header', [
    'title' => 'My Requests',
    'subtitle' => 'View and manage your transportation request history.',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('partner.dashboard')],
        ['label' => 'My Requests']
    ]
])

<div class="row">
    <div class="col-lg-12">
        <div class="card partner-page-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="card-title mb-0">Request History</h5>
                @php
                    $partnerNav = Auth::guard('partner')->user()->partner;
                    $canSubmitRequests = $partnerNav->allow_manual_submission || $partnerNav->allow_image_submission;
                @endphp
                @if($canSubmitRequests)
                    <a href="{{ route('partner.requests.new') }}" class="btn btn-sm btn-primary btn-touch">
                        <i class="ri-add-line align-middle me-1"></i> New Request
                    </a>
                @endif
            </div>
            <div class="card-body">
                <!-- Filter Tabs -->
                <ul class="nav nav-tabs nav-tabs-custom nav-success mb-4 partner-filter-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ !request('status') || request('status') === 'all' ? 'active' : '' }}"
                           href="{{ route('partner.requests.index', ['status' => 'all']) }}"
                           role="tab"
                           aria-current="{{ !request('status') || request('status') === 'all' ? 'page' : 'false' }}">
                            <i class="ri-file-list-line me-1"></i> All
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ request('status') === 'pending' ? 'active' : '' }}"
                           href="{{ route('partner.requests.index', ['status' => 'pending']) }}"
                           role="tab"
                           aria-current="{{ request('status') === 'pending' ? 'page' : 'false' }}">
                            <i class="ri-time-line me-1"></i> Pending
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ request('status') === 'approved' ? 'active' : '' }}"
                           href="{{ route('partner.requests.index', ['status' => 'approved']) }}"
                           role="tab"
                           aria-current="{{ request('status') === 'approved' ? 'page' : 'false' }}">
                            <i class="ri-checkbox-circle-line me-1"></i> Approved
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ request('status') === 'declined' ? 'active' : '' }}"
                           href="{{ route('partner.requests.index', ['status' => 'declined']) }}"
                           role="tab"
                           aria-current="{{ request('status') === 'declined' ? 'page' : 'false' }}">
                            <i class="ri-close-circle-line me-1"></i> Declined
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ request('status') === 'withdrawn' ? 'active' : '' }}"
                           href="{{ route('partner.requests.index', ['status' => 'withdrawn']) }}"
                           role="tab"
                           aria-current="{{ request('status') === 'withdrawn' ? 'page' : 'false' }}">
                            <i class="ri-arrow-go-back-line me-1"></i> Withdrawn
                        </a>
                    </li>
                </ul>

                @if($requests->count() > 0)
                    <!-- Desktop Table -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Reference</th>
                                    <th scope="col">Method</th>
                                    <th scope="col">Crew</th>
                                    <th scope="col">Submitted</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-center" style="min-width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $request)
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
                                        <td>
                                            <span class="text-muted">{{ $request->submitted_at ? $request->submitted_at->format('M d, Y') : 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @include('partner.partials.status-badge', ['status' => $request->status, 'withIcon' => false])
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary dropdown-toggle"
                                                        type="button"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false"
                                                        aria-label="Request actions">
                                                    <i class="ri-more-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('partner.requests.show', $request) }}">
                                                            <i class="ri-eye-fill align-bottom me-2 text-muted"></i> View Details
                                                        </a>
                                                    </li>
                                                    @if($request->canPartnerEdit())
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('partner.requests.edit', $request) }}">
                                                                <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if($request->isPending())
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('partner.requests.withdraw', $request) }}" method="POST" onsubmit="return confirm('Are you sure you want to withdraw this request? This action cannot be undone.');">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="ri-close-circle-fill align-bottom me-2"></i> Withdraw
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="d-md-none">
                        @foreach($requests as $request)
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
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('partner.requests.show', $request) }}" class="btn btn-sm btn-primary flex-grow-1 btn-touch">
                                            <i class="ri-eye-line me-1"></i> View
                                        </a>
                                        @if($request->canPartnerEdit())
                                            <a href="{{ route('partner.requests.edit', $request) }}" class="btn btn-sm btn-soft-primary btn-touch">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                        @endif
                                        @if($request->isPending())
                                            <form action="{{ route('partner.requests.withdraw', $request) }}" method="POST" onsubmit="return confirm('Are you sure you want to withdraw this request?');" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-soft-danger btn-touch" aria-label="Withdraw request">
                                                    <i class="ri-close-circle-line"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if($requests->hasPages())
                        <div class="mt-4">
                            {{ $requests->links() }}
                        </div>
                    @endif
                @else
                    @include('partner.partials.empty-state', [
                        'icon' => 'ri-file-list-3-line',
                        'title' => !request('status') || request('status') === 'all'
                            ? 'No requests submitted yet'
                            : 'No ' . request('status') . ' requests found',
                        'message' => !request('status') || request('status') === 'all'
                            ? ($canSubmitRequests
                                ? 'Get started by creating your first transportation request.'
                                : 'No requests have been submitted yet.')
                            : 'Try viewing all requests or change the filter.',
                    ])
                    @if(!request('status') || request('status') === 'all')
                        @if($canSubmitRequests)
                            <div class="text-center mt-3">
                                <a href="{{ route('partner.requests.new') }}" class="btn btn-primary btn-touch">
                                    <i class="ri-add-line align-middle me-1"></i> Create Request
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center mt-3">
                            <a href="{{ route('partner.requests.index') }}" class="btn btn-soft-primary btn-touch">
                                View All Requests
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection