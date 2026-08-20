@extends('layouts.partner')

@section('title', 'My Requests - Partner Portal')

@section('content')
<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">My Requests</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('partner.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Requests</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2 justify-content-between">
                <h5 class="card-title mb-0">Request List</h5>
                @php
                    $partnerNav = Auth::guard('partner')->user()->partner;
                    $canSubmitRequests = $partnerNav->allow_manual_submission || $partnerNav->allow_image_submission;
                @endphp
                @if($canSubmitRequests)
                    <a href="{{ route('partner.requests.new') }}" class="btn btn-sm btn-primary">
                        <i class="ri-add-line align-middle me-1"></i> New Request
                    </a>
                @endif
            </div>
            <div class="card-body">
                <!-- Filter Tabs -->
                <ul class="nav nav-tabs nav-tabs-custom nav-success mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ !request('status') || request('status') === 'all' ? 'active' : '' }}" 
                           href="{{ route('partner.requests.index', ['status' => 'all']) }}">
                            All
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'pending' ? 'active' : '' }}" 
                           href="{{ route('partner.requests.index', ['status' => 'pending']) }}">
                            Pending
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'approved' ? 'active' : '' }}" 
                           href="{{ route('partner.requests.index', ['status' => 'approved']) }}">
                            Approved
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'declined' ? 'active' : '' }}" 
                           href="{{ route('partner.requests.index', ['status' => 'declined']) }}">
                            Declined
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'withdrawn' ? 'active' : '' }}" 
                           href="{{ route('partner.requests.index', ['status' => 'withdrawn']) }}">
                            Withdrawn
                        </a>
                    </li>
                </ul>

                @if($requests->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Method</th>
                                    <th>Crew Items</th>
                                    <th>Submitted</th>
                                    <th>Submitted By</th>
                                    <th>Status</th>
                                    <th class="text-center" style="min-width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $request)
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
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('partner.requests.show', $request) }}">
                                                            <i class="ri-eye-fill align-bottom me-2 text-muted"></i> View
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

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $requests->links() }}
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="ri-file-list-3-line display-4 text-muted"></i>
                        </div>
                        <h5 class="mb-3">No requests found</h5>
                        @if(!request('status') || request('status') === 'all')
                            @if($canSubmitRequests)
                                <p class="text-muted mb-3">Get started by creating your first request.</p>
                                <a href="{{ route('partner.requests.new') }}" class="btn btn-primary">
                                    <i class="ri-add-line align-middle me-1"></i> Create Request
                                </a>
                            @else
                                <p class="text-muted mb-0">No requests have been submitted yet.</p>
                            @endif
                        @else
                            <p class="text-muted mb-3">No {{ request('status') }} requests found.</p>
                            <a href="{{ route('partner.requests.index') }}" class="btn btn-soft-primary">View All Requests</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
