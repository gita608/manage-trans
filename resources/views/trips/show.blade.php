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
                            $totalJobs = $tripStatus['totalJobs'];
                            $isCompleted = $tripStatus['isCompleted'];
                            $statusBadge = $tripStatus['statusBadge'];
                            $statusText = $tripStatus['statusText'];
                        @endphp

                        <div class="card border shadow-none mb-4 bg-light-subtle">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Driver</p>
                                        @if($trip->driver)
                                            <h6 class="mb-0 fs-14">{{ $trip->driver->name }}</h6>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary fs-12">Unassigned</span>
                                        @endif
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
                                        <th scope="col">Crew Contact No</th>
                                        <th scope="col">Crew Contact No 2</th>
                                        <th scope="col">Vessel</th>
                                        <th scope="col">Pick-up Time</th>
                                        <th scope="col">Route</th>
                                        <th scope="col">Flight No</th>
                                        <th scope="col">Remarks</th>
                                        <th scope="col">Sub Remark</th>
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
                                            <td>
                                                @if($crew->phone_2)
                                                    <a href="tel:{{ $crew->phone_2 }}" class="text-body">{{ $crew->phone_2 }}</a>
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
                                                @if($crew->remarks)
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $crew->remarks }}">
                                                        <i class="ri-message-2-line fs-16"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($crew->sub_remark)
                                                    <span class="text-truncate d-inline-block" style="max-width: 120px;" title="{{ $crew->sub_remark }}">
                                                        {{ $crew->sub_remark }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
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
                        @php
                            $isCompleted = $tripStatus['isCompleted'];
                            // Determine start and end times for duration calculation
                            $startTime = $trip->created_at;
                            $latestLog = $trip->activityLogs->sortByDesc('created_at')->first();
                            $endTime = $latestLog ? $latestLog->created_at : now();
                            
                            // Calculate duration string e.g., "2 days 4 hours"
                            $duration = $startTime->diffForHumans($endTime, [
                                'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 
                                'parts' => 2,
                                'short' => true
                            ]);
                        @endphp

                        @if($isCompleted)
                            <div class="alert alert-success d-flex align-items-center mb-4 shadow-sm border-0 bg-success-subtle text-success">
                                <div class="avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-title bg-success text-white rounded-circle fs-4">
                                        <i class="ri-timer-flash-line"></i>
                                    </span>
                                </div>
                                <div>
                                    <h5 class="alert-heading fs-16 mb-1 fw-bold">Trip Completed</h5>
                                    <p class="mb-0 fs-14">
                                        Total time taken: <span class="fw-bold">{{ $duration }}</span>
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if($trip->activityLogs->count() > 0)
                            <div class="acitivity-timeline-2">
                                @foreach($trip->activityLogs->groupBy(function($log) { return \Carbon\Carbon::parse($log->created_at)->format('Y-m-d'); }) as $date => $logs)
                                    <div class="mb-5">
                                        <div class="d-flex align-items-center mb-4">
                                            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill fs-12 fw-medium">
                                                <i class="ri-calendar-event-line me-1"></i>
                                                @if($date == date('Y-m-d')) Today
                                                @elseif($date == date('Y-m-d', strtotime('-1 day'))) Yesterday
                                                @else {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                                                @endif
                                            </span>
                                            <div class="flex-grow-1 border-top border-dashed ms-3 opacity-25"></div>
                                        </div>

                                        <div class="ps-3">
                                            @foreach($logs as $log)
                                                @php
                                                    $actionColors = [
                                                        'created' => 'success',
                                                        'updated' => 'info',
                                                        'deleted' => 'danger',
                                                        'restored' => 'warning',
                                                    ];
                                                    $color = $actionColors[$log->action] ?? 'primary';
                                                    
                                                    $user = $log->user ?? $log->driver;
                                                    $userName = $user ? $user->name : 'System';
                                                    $isDriver = $log->driver ? true : false;
                                                @endphp
                                                <div class="d-flex gap-4 mb-4 position-relative log-item">
                                                    <!-- Vertical Line -->
                                                    @if(!$loop->last)
                                                        <div class="position-absolute top-0 start-0 border-start border-2 border-dashed border-light" style="left: 14px; height: 120%; z-index: 0;"></div>
                                                    @endif

                                                    <!-- Icon/Dot -->
                                                    <div class="flex-shrink-0 position-relative z-1">
                                                        <div class="avatar-xs">
                                                            <div class="avatar-title bg-white border border-2 border-{{ $color }} text-{{ $color }} rounded-circle fs-16 shadow-sm">
                                                                <i class="ri-checkbox-blank-circle-fill fs-8"></i>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Content -->
                                                    <div class="flex-grow-1">
                                                        <div class="row">
                                                            <div class="col-md-9">
                                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                                    <h6 class="fs-15 mb-0 fw-semibold text-dark">{{ $log->description }}</h6>
                                                                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }} fs-10 text-uppercase px-2 py-0.5 rounded-1">{{ $log->action }}</span>
                                                                </div>
                                                                
                                                                <div class="d-flex align-items-center text-muted fs-13 mb-2">
                                                                    <span class="me-3">
                                                                        <i class="ri-user-3-line me-1 align-bottom"></i> 
                                                                        {{ $userName }}
                                                                        @if($isDriver) <span class="badge bg-light text-muted border ms-1">Driver</span> @endif
                                                                    </span>
                                                                    @if($log->ip_address)
                                                                        <span><i class="ri-global-line me-1 align-bottom"></i> {{ $log->ip_address }}</span>
                                                                    @endif
                                                                </div>

                                                                @if(($log->old_values && count($log->old_values) > 0) || ($log->new_values && count($log->new_values) > 0))
                                                                    <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fs-13" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}">
                                                                        View changes details <i class="ri-arrow-right-line ms-1"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                            <div class="col-md-3 text-md-end">
                                                                <span class="text-muted fs-12 fw-medium bg-light px-2 py-1 rounded">
                                                                    {{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="avatar-lg mx-auto mb-3">
                                    <div class="avatar-title bg-light-subtle text-muted rounded-circle fs-1 border border-light-subtle border-dashed">
                                        <i class="ri-file-history-line"></i>
                                    </div>
                                </div>
                                <h5 class="text-dark">No Activity Logs</h5>
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
                                by <span class="fw-semibold">{{ $userName }}</span> on {{ formatDate($log->created_at) }}
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

