@extends('layouts.app')

@section('title', 'Trip Details | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Trip Details</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trips</a></li>
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </div>

        </div>
    </div>
</div>
<!-- end page title -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Trip Details</h5>
                    <div>
                        <a href="{{ route('trips.edit', $trip) }}" class="btn btn-primary">
                            <i class="ri-pencil-line align-middle me-1"></i> Edit
                        </a>
                        <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this trip?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="ri-delete-bin-line align-middle me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tripInfo" role="tab" aria-selected="true">
                            <i class="ri-information-line me-1 align-middle"></i> Trip Information
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#activityLogs" role="tab" aria-selected="false">
                            <i class="ri-file-list-line me-1 align-middle"></i> Activity Logs
                            @if($trip->activityLogs->count() > 0)
                                <span class="badge bg-primary rounded-pill ms-1">{{ $trip->activityLogs->count() }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#issuesExpenses" role="tab" aria-selected="false">
                            <i class="ri-alert-line me-1 align-middle"></i> Issues & Expenses
                            @if($trip->tripIssues->count() + $trip->tripExpenses->count() > 0)
                                <span class="badge bg-danger rounded-pill ms-1">{{ $trip->tripIssues->count() + $trip->tripExpenses->count() }}</span>
                            @endif
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Trip Information Tab -->
                    <!-- Trip Information Tab -->
                    <div class="tab-pane fade show active" id="tripInfo" role="tabpanel">
                        @php
                            $totalJobs = $trip->crews->count();
                            $completedJobs = $trip->crews->where('status', 'completed')->count();
                            $inProgressJobs = $trip->crews->where('status', 'in_progress')->count();
                            
                            // Determine overall status
                            if ($completedJobs === $totalJobs && $totalJobs > 0) {
                                $statusBadge = 'success';
                                $statusText = 'All Completed';
                            } elseif ($inProgressJobs > 0) {
                                $statusBadge = 'warning';
                                $statusText = 'In Progress';
                            } else {
                                $statusBadge = 'primary';
                                $statusText = 'Pending';
                            }
                        @endphp

                        <div class="card border shadow-none mb-4 bg-light-subtle">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Driver</p>
                                        <h6 class="mb-0 fs-14">{{ $trip->driver->name }}</h6>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Date</p>
                                        <h6 class="mb-0 fs-14">{{ $trip->trip_date instanceof \Carbon\Carbon ? $trip->trip_date->format('d M, Y') : \Carbon\Carbon::parse($trip->trip_date)->format('d M, Y') }}</h6>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Title</p>
                                        <h6 class="mb-0 fs-14">{{ $trip->title ?? 'N/A' }}</h6>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Status</p>
                                        <span class="badge bg-{{ $statusBadge }} fs-12">{{ $statusText }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0">Crew Details</h5>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0 table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 50px;">#</th>
                                        <th scope="col">Crew Name</th>
                                        <th scope="col">Contact</th>
                                        <th scope="col">Vessel</th>
                                        <th scope="col">Pick-up Time</th>
                                        <th scope="col">Route</th>
                                        <th scope="col">Flight No</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($trip->crews as $index => $crew)
                                        <tr>
                                            <td class="text-center fw-medium">{{ $index + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs bg-light rounded-circle text-center d-flex align-items-center justify-content-center me-2">
                                                        <span class="text-primary fw-medium">{{ substr($crew->name, 0, 2) }}</span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $crew->name }}</h6>
                                                        @if($crew->address)
                                                            <small class="text-muted text-truncate d-block" style="max-width: 150px;" title="{{ $crew->address }}">{{ $crew->address }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($crew->phone)
                                                    <a href="tel:{{ $crew->phone }}" class="text-body">{{ $crew->phone }}</a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $crew->vessel->name ?? 'Unknown' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($crew->pick_up_time)->format('h:i A') }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="text-truncate" style="max-width: 100px;" title="{{ $crew->from_location }}">{{ $crew->from_location }}</span>
                                                    <i class="ri-arrow-right-line mx-2 text-muted"></i>
                                                    <span class="text-truncate" style="max-width: 100px;" title="{{ $crew->to_location }}">{{ $crew->to_location }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $crew->flight_number ?? '-' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $crew->status === 'completed' ? 'success' : ($crew->status === 'in_progress' ? 'warning' : 'primary') }}">
                                                    {{ ucfirst(str_replace('_', ' ', $crew->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($crew->remarks)
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $crew->remarks }}">
                                                        <i class="ri-message-2-line fs-16"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <p class="text-muted mb-0">No crew assigned to this trip.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Activity Logs Tab -->
                    <div class="tab-pane fade" id="activityLogs" role="tabpanel">
                        @if($trip->activityLogs->count() > 0)
                            <div class="vstack gap-4 pt-2">
                                @foreach($trip->activityLogs as $log)
                                    @php
                                        $color = match($log->action) {
                                            'created' => 'success',
                                            'updated' => 'info',
                                            'deleted' => 'danger',
                                            default => 'secondary',
                                        };
                                        $icon = match($log->action) {
                                            'created' => 'ri-add-line',
                                            'updated' => 'ri-pencil-line',
                                            'deleted' => 'ri-delete-bin-line',
                                            default => 'ri-information-line',
                                        };
                                        $userName = 'System';
                                        if($log->user) {
                                            $userName = $log->user->name;
                                        } elseif($log->driver) {
                                            $userName = $log->driver->name . ' (Driver)';
                                        }
                                    @endphp
                                    <div class="d-flex position-relative">
                                        <!-- Timeline Line -->
                                        @if(!$loop->last)
                                            <div class="position-absolute top-0 start-0 translate-middle-x h-100 border-start border-dashed" style="left: 20px; top: 40px !important;"></div>
                                        @endif
                                        
                                        <div class="flex-shrink-0 me-3" style="z-index: 1;">
                                            <div class="avatar-sm">
                                                <span class="avatar-title bg-{{ $color }}-subtle text-{{ $color }} rounded-circle fs-4">
                                                    <i class="{{ $icon }}"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <h5 class="fs-14 mb-1">{{ $log->description }}</h5>
                                                    <p class="text-muted small mb-0">
                                                        <i class="ri-user-line me-1"></i> {{ $userName }}
                                                    </p>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted d-block">{{ $log->created_at->format('M d, Y') }}</small>
                                                    <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}">
                                                    View Details <i class="ri-arrow-right-line ms-1"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="avatar-lg mx-auto mb-3">
                                    <span class="avatar-title bg-light text-muted rounded-circle fs-1">
                                        <i class="ri-file-history-line"></i>
                                    </span>
                                </div>
                                <h5 class="text-muted">No Activity Logs</h5>
                                <p class="text-muted mb-0">No activity has been recorded for this trip yet.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Issues & Expenses Tab -->
                    <div class="tab-pane fade" id="issuesExpenses" role="tabpanel">
                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border shadow-none mb-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm flex-shrink-0 me-3">
                                                <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                                    <i class="ri-alert-line"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="text-muted mb-1">Total Issues</p>
                                                <h4 class="mb-0">{{ $trip->tripIssues->count() }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border shadow-none mb-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm flex-shrink-0 me-3">
                                                <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                                    <i class="ri-money-dollar-circle-line"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="text-muted mb-1">Total Expenses</p>
                                                <h4 class="mb-0">{{ number_format($trip->tripExpenses->sum('amount'), 2) }} <small class="text-muted fs-6 fw-normal">({{ $trip->tripExpenses->count() }} items)</small></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Issues Section -->
                            <div class="col-lg-12 mb-4">
                                <div class="card border shadow-none">
                                    <div class="card-header bg-light-subtle">
                                        <h5 class="card-title mb-0"><i class="ri-alert-line me-2"></i>Trip Issues</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($trip->tripIssues->count() > 0)
                                            <div class="table-responsive">
                                                <table class="table table-nowrap table-hover align-middle mb-0 table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th scope="col">Type</th>
                                                            <th scope="col">Description</th>
                                                            <th scope="col">Reported By</th>
                                                            <th scope="col">Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($trip->tripIssues as $issue)
                                                            <tr>
                                                                <td>
                                                                    <span class="badge bg-danger">{{ $issue->issueType->title ?? 'Unknown' }}</span>
                                                                </td>
                                                                <td>{{ $issue->description }}</td>
                                                                <td>{{ $issue->driver->name ?? 'Unknown' }}</td>
                                                                <td>{{ $issue->created_at->format('M d, Y h:i A') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <div class="avatar-sm mx-auto mb-3">
                                                    <span class="avatar-title bg-light text-primary rounded-circle fs-3">
                                                        <i class="ri-checkbox-circle-line"></i>
                                                    </span>
                                                </div>
                                                <h5 class="text-muted">No Issues Reported</h5>
                                                <p class="text-muted mb-0">This trip has no reported issues.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Expenses Section -->
                            <div class="col-lg-12">
                                <div class="card border shadow-none">
                                    <div class="card-header bg-light-subtle">
                                        <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line me-2"></i>Trip Expenses</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($trip->tripExpenses->count() > 0)
                                            <div class="table-responsive">
                                                <table class="table table-nowrap table-hover align-middle mb-0 table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th scope="col">Type</th>
                                                            <th scope="col">Amount</th>
                                                            <th scope="col">Receipt</th>
                                                            <th scope="col">Submitted By</th>
                                                            <th scope="col">Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($trip->tripExpenses as $expense)
                                                            <tr>
                                                                <td>
                                                                    <span class="badge bg-warning text-dark">{{ $expense->expenseType->title ?? 'Unknown' }}</span>
                                                                </td>
                                                                <td>{{ number_format($expense->amount, 2) }}</td>
                                                                <td>
                                                                    @if($expense->receipt)
                                                                        <a href="{{ Storage::url($expense->receipt) }}" target="_blank" class="btn btn-sm btn-soft-primary">
                                                                            <i class="ri-file-text-line me-1"></i> View Receipt
                                                                        </a>
                                                                    @else
                                                                        <span class="text-muted">No receipt</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $expense->driver->name ?? 'Unknown' }}</td>
                                                                <td>{{ $expense->created_at->format('M d, Y h:i A') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot class="table-light">
                                                        <tr>
                                                            <td class="fw-bold">Total</td>
                                                            <td class="fw-bold">{{ number_format($trip->tripExpenses->sum('amount'), 2) }}</td>
                                                            <td colspan="3"></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <div class="avatar-sm mx-auto mb-3">
                                                    <span class="avatar-title bg-light text-warning rounded-circle fs-3">
                                                        <i class="ri-money-dollar-circle-line"></i>
                                                    </span>
                                                </div>
                                                <h5 class="text-muted">No Expenses</h5>
                                                <p class="text-muted mb-0">No expenses have been submitted for this trip.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('trips.index') }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line align-middle me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modals -->
@foreach($trip->activityLogs as $log)
    @php
        $color = match($log->action) {
            'created' => 'success',
            'updated' => 'info',
            'deleted' => 'danger',
            default => 'secondary',
        };
        $icon = match($log->action) {
            'created' => 'ri-add-line',
            'updated' => 'ri-pencil-line',
            'deleted' => 'ri-delete-bin-line',
            default => 'ri-information-line',
        };
        $userName = 'System';
        if($log->user) {
            $userName = $log->user->name;
        } elseif($log->driver) {
            $userName = $log->driver->name . ' (Driver)';
        }
    @endphp
    <!-- Log Details Modal -->
    <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Activity Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-{{ $color }}-subtle text-{{ $color }} rounded-circle fs-4">
                                    <i class="{{ $icon }}"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fs-16 mb-1">{{ $log->description }}</h5>
                            <p class="text-muted mb-0">
                                by <span class="fw-semibold">{{ $userName }}</span> on {{ $log->created_at->format('M d, Y h:i A') }}
                            </p>
                        </div>
                    </div>

                    @if(($log->old_values && count($log->old_values) > 0) || ($log->new_values && count($log->new_values) > 0))
                        <div class="card border shadow-none mb-0 bg-light-subtle">
                            <div class="card-header bg-transparent border-bottom-0">
                                <h6 class="card-title mb-0">Changes Made</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-borderless table-nowrap align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" style="width: 30%;">Field</th>
                                                @if($log->old_values && count($log->old_values) > 0) <th scope="col">Old Value</th> @endif
                                                @if($log->new_values && count($log->new_values) > 0) <th scope="col">New Value</th> @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $old = $log->old_values ?? [];
                                                $new = $log->new_values ?? [];
                                                $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
                                            @endphp
                                            @foreach($allKeys as $key)
                                                <tr>
                                                    <td class="fw-medium text-muted">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                    @if(count($old) > 0)
                                                        <td class="text-danger">
                                                            @if(isset($old[$key]))
                                                                {{ is_array($old[$key]) ? json_encode($old[$key]) : $old[$key] }}
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                    @if(count($new) > 0)
                                                        <td class="text-success">
                                                            @if(isset($new[$key]))
                                                                {{ is_array($new[$key]) ? json_encode($new[$key]) : $new[$key] }}
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($log->ip_address)
                        <div class="mt-3 text-end">
                            <span class="badge bg-light text-muted border">IP: {{ $log->ip_address }}</span>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>
@endpush

