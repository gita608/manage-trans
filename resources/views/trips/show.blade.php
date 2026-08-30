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
                        @if(!$trip->isCancelled())
                        <form action="{{ route('trips.cancel', $trip) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this trip?');">
                            @csrf
                            <button type="submit" class="btn btn-warning text-white">
                                <i class="ri-close-circle-line align-middle me-1"></i> Cancel
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this trip?');">
                            @csrf
                            @method('DELETE')
                            @if(!$trip->partner_request_id)
                            <button type="submit" class="btn btn-danger">
                                <i class="ri-delete-bin-line align-middle me-1"></i> Delete
                            </button>
                            @endif
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
                            <i class="ri-route-line me-1 align-middle"></i> Activity & Lifecycle
                            @if(($lifecycle['timeline_count'] ?? 0) > 0)
                                <span class="badge bg-primary rounded-pill ms-1">{{ $lifecycle['timeline_count'] }}</span>
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
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#removedCrew" role="tab" aria-selected="false">
                            <i class="ri-user-unfollow-line me-1 align-middle"></i> Removed Crew
                            @if($trip->crewRemovals->count() > 0)
                                <span class="badge bg-secondary rounded-pill ms-1">{{ $trip->crewRemovals->count() }}</span>
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
                                        <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Reference</p>
                                        <h6 class="mb-0 fs-14">{{ $trip->trip_reference ?? 'N/A' }}</h6>
                                    </div>
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
                                @if($trip->partnerRequest)
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Source Request</p>
                                            <h6 class="mb-0 fs-14">
                                                <a href="{{ route('partner-requests.show', $trip->partnerRequest) }}">
                                                    {{ $trip->partnerRequest->request_reference }}
                                                </a>
                                            </h6>
                                        </div>
                                        @if($trip->partner)
                                            <div class="col-md-6">
                                                <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Partner</p>
                                                <h6 class="mb-0 fs-14">{{ $trip->partner->title }}</h6>
                                            </div>
                                        @endif
                                    </div>
                                @endif
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

                    <!-- Activity & Lifecycle Tab -->
                    <div class="tab-pane fade" id="activityLogs" role="tabpanel">
                        @php
                            $summary = $lifecycle['summary'] ?? [];
                            $steps = $lifecycle['steps'] ?? [];
                            $timeline = $lifecycle['timeline'] ?? [];
                        @endphp

                        {{-- Compact status summary --}}
                        <div class="card border shadow-none mb-4 bg-light-subtle">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-6 col-md-3">
                                        <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Current Status</p>
                                        <span class="badge bg-{{ $summary['status_badge'] ?? 'secondary' }}">{{ $summary['status'] ?? 'Unknown' }}</span>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Scheduled Date</p>
                                        <h6 class="mb-0 fs-14">{{ $summary['scheduled_date'] ?? '—' }}</h6>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Driver</p>
                                        <h6 class="mb-0 fs-14">{{ $summary['driver'] ?? 'Unassigned' }}</h6>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        @if(!empty($summary['is_completed']) && !empty($summary['duration']))
                                            <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Actual Duration</p>
                                            <h6 class="mb-0 fs-14 text-success">{{ $summary['duration'] }}</h6>
                                        @elseif(!empty($summary['is_in_progress']) && !empty($summary['running_for']))
                                            <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Running For</p>
                                            <h6 class="mb-0 fs-14 text-info">{{ $summary['running_for'] }}</h6>
                                        @elseif(!empty($summary['is_completed']) && empty($summary['started_at']))
                                            <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Actual Duration</p>
                                            <h6 class="mb-0 fs-14 text-muted">Start not recorded</h6>
                                        @elseif(!empty($summary['started_at']))
                                            <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Actual Start</p>
                                            <h6 class="mb-0 fs-14">{{ $summary['started_at']->format('g:i A') }}</h6>
                                        @else
                                            <p class="text-muted mb-1 text-uppercase fw-medium fs-12">Actual Start</p>
                                            <h6 class="mb-0 fs-14 text-muted">Not started</h6>
                                        @endif
                                    </div>
                                </div>
                                @if(!empty($summary['is_completed']) && !empty($summary['started_at']) && !empty($summary['completed_at']))
                                    <div class="mt-3 pt-3 border-top small text-muted">
                                        Started {{ $summary['started_at']->format('M j, Y · g:i A') }}
                                        <span class="mx-2">·</span>
                                        Completed {{ $summary['completed_at']->format('M j, Y · g:i A') }}
                                    </div>
                                @elseif(!empty($summary['is_in_progress']) && !empty($summary['started_at']))
                                    <div class="mt-3 pt-3 border-top small text-muted">
                                        Started {{ $summary['started_at']->format('M j, Y · g:i A') }}
                                    </div>
                                @elseif(!empty($summary['is_completed']) && empty($summary['started_at']))
                                    <div class="mt-3 pt-3 border-top small text-muted">
                                        Actual start time not recorded — duration is unavailable.
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Lifecycle stepper --}}
                        <div class="mb-4">
                            <h5 class="mb-3">Trip Lifecycle</h5>
                            <div class="trip-lifecycle-stepper">
                                @foreach($steps as $step)
                                    @php
                                        $state = $step['state'] ?? 'pending';
                                        $stateClass = match($state) {
                                            'completed' => 'is-completed',
                                            'current' => 'is-current',
                                            'cancelled' => 'is-cancelled',
                                            default => 'is-pending',
                                        };
                                        $iconWrap = match($state) {
                                            'completed' => 'bg-success text-white',
                                            'current' => 'bg-info text-white',
                                            'cancelled' => 'bg-danger text-white',
                                            default => 'bg-light text-muted border',
                                        };
                                    @endphp
                                    <div class="trip-lifecycle-step {{ $stateClass }}">
                                        <div class="trip-lifecycle-icon {{ $iconWrap }}">
                                            @if($state === 'completed')
                                                <i class="ri-check-line"></i>
                                            @elseif($state === 'cancelled')
                                                <i class="ri-close-line"></i>
                                            @else
                                                <i class="{{ $step['icon'] ?? 'ri-checkbox-blank-circle-line' }}"></i>
                                            @endif
                                        </div>
                                        <div class="trip-lifecycle-body">
                                            <div class="fw-semibold">{{ $step['title'] }}</div>
                                            @if(!empty($step['meta']))
                                                <div class="text-muted small">{{ $step['meta'] }}</div>
                                            @endif
                                            @if(!empty($step['time']))
                                                <div class="text-muted small">{{ $step['time']->format('M j, Y · g:i A') }}</div>
                                            @elseif($state === 'pending')
                                                <div class="text-muted small">Pending</div>
                                            @elseif($state === 'current' && empty($step['meta']))
                                                <div class="text-muted small">In progress</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Activity history --}}
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0">Activity History</h5>
                        </div>

                        @if(count($timeline) > 0)
                            <div class="acitivity-timeline-2">
                                @foreach(collect($timeline)->groupBy(fn ($event) => $event['time']->format('Y-m-d')) as $date => $events)
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
                                            @foreach($events as $event)
                                                <div class="d-flex gap-4 mb-4 position-relative log-item">
                                                    @if(!$loop->last)
                                                        <div class="position-absolute top-0 start-0 border-start border-2 border-dashed border-light" style="left: 14px; height: 120%; z-index: 0;"></div>
                                                    @endif

                                                    <div class="flex-shrink-0 position-relative z-1">
                                                        <div class="avatar-xs">
                                                            <div class="avatar-title bg-{{ $event['color'] }}-subtle text-{{ $event['color'] }} rounded-circle fs-16">
                                                                <i class="{{ $event['icon'] }}"></i>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div class="row">
                                                            <div class="col-md-9">
                                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                                    <h6 class="fs-15 mb-0 fw-semibold text-dark">{{ $event['title'] }}</h6>
                                                                    <span class="badge bg-{{ $event['color'] }}-subtle text-{{ $event['color'] }} fs-10 px-2 py-0.5 rounded-1">{{ $event['badge'] }}</span>
                                                                </div>

                                                                @if(!empty($event['description']))
                                                                    <p class="mb-1 fs-13">{{ $event['description'] }}</p>
                                                                @endif

                                                                <div class="d-flex align-items-center text-muted fs-13 mb-2 flex-wrap gap-2">
                                                                    <span>
                                                                        <i class="ri-user-3-line me-1 align-bottom"></i>
                                                                        {{ $event['actor_name'] }}
                                                                        <span class="badge bg-light text-muted border ms-1">{{ $event['actor_type'] }}</span>
                                                                    </span>
                                                                </div>

                                                                @if(!empty($event['duration']))
                                                                    <p class="mb-2 fs-13 text-success fw-medium">
                                                                        <i class="ri-timer-line me-1"></i>Actual duration: {{ $event['duration'] }}
                                                                    </p>
                                                                @endif

                                                                @if(!empty($event['changes']))
                                                                    <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fs-13" data-bs-toggle="modal" data-bs-target="#lifecycleModal{{ $event['id'] }}">
                                                                        View changes <i class="ri-arrow-right-line ms-1"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                            <div class="col-md-3 text-md-end">
                                                                <span class="text-muted fs-12 fw-medium bg-light px-2 py-1 rounded">
                                                                    {{ $event['time']->format('g:i A') }}
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
                                        <i class="ri-route-line"></i>
                                    </div>
                                </div>
                                <h5 class="text-dark">No Activity Yet</h5>
                                <p class="text-muted mb-0">Lifecycle events for this trip will appear here.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Issues & Expenses Tab -->
                    <div class="tab-pane fade" id="issuesExpenses" role="tabpanel">
                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <div class="card border shadow-none mb-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm flex-shrink-0 me-3">
                                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                                    <i class="ri-time-line"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="text-muted mb-1">Total Trip Hours</p>
                                                <h4 class="mb-0">{{ number_format((float) $trip->tripExpenses->sum('hours'), 2) }} hrs</h4>
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
                                                            <th scope="col">Hours</th>
                                                            <th scope="col">Description / Note</th>
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
                                                                <td>{{ $expense->displayAmount() }}</td>
                                                                <td>{{ $expense->displayHours() }}</td>
                                                                <td>
                                                                    @if($expense->description)
                                                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $expense->description }}">
                                                                            {{ $expense->description }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">-</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($expense->receipt)
                                                                        <a href="{{ Storage::url($expense->receipt) }}" target="_blank" class="btn btn-sm btn-soft-primary">
                                                                            <i class="ri-file-text-line me-1"></i> View Receipt
                                                                        </a>
                                                                    @else
                                                                        <span class="text-muted">-</span>
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
                                                            <td class="fw-bold">{{ number_format((float) $trip->tripExpenses->sum('hours'), 2) }} hrs</td>
                                                            <td colspan="4"></td>
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

                    <!-- Removed Crew Tab -->
                    <div class="tab-pane fade" id="removedCrew" role="tabpanel">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h5 class="mb-0">Removed Crew History</h5>
                        </div>

                        @if($trip->crewRemovals->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0 table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Crew Name</th>
                                            <th scope="col">Contact</th>
                                            <th scope="col">Vessel</th>
                                            <th scope="col">Pickup Time</th>
                                            <th scope="col">Route</th>
                                            <th scope="col">Flight No.</th>
                                            <th scope="col">Assigned Driver</th>
                                            <th scope="col">Removed By</th>
                                            <th scope="col">Removed On</th>
                                            <th scope="col">Removal Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($trip->crewRemovals as $removal)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center min-width-0">
                                                        <div class="avatar-xs me-2 flex-shrink-0">
                                                            <div class="avatar-title rounded-circle bg-light text-danger">
                                                                {{ strtoupper(substr($removal->crew_name, 0, 2)) }}
                                                            </div>
                                                        </div>
                                                        <div class="min-width-0">
                                                            <h6 class="mb-0 text-truncate" title="{{ $removal->crew_name }}">{{ $removal->crew_name }}</h6>
                                                            @if($removal->address || $removal->remarks || $removal->sub_remark)
                                                                <button type="button"
                                                                        class="btn btn-sm btn-link text-decoration-none p-0 fs-12"
                                                                        data-bs-toggle="collapse"
                                                                        data-bs-target="#removalDetails{{ $removal->id }}"
                                                                        aria-expanded="false">
                                                                    More details
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if($removal->address || $removal->remarks || $removal->sub_remark)
                                                        <div class="collapse mt-2" id="removalDetails{{ $removal->id }}">
                                                            <div class="bg-light-subtle border rounded p-2 small text-muted">
                                                                @if($removal->address)
                                                                    <div><span class="fw-medium text-body">Address:</span> {{ $removal->address }}</div>
                                                                @endif
                                                                @if($removal->remarks)
                                                                    <div><span class="fw-medium text-body">Remarks:</span> {{ $removal->remarks }}</div>
                                                                @endif
                                                                @if($removal->sub_remark)
                                                                    <div><span class="fw-medium text-body">Sub Remark:</span> {{ $removal->sub_remark }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="min-width-0">
                                                    @if($removal->phone)
                                                        <a href="tel:{{ $removal->phone }}" class="text-body d-block">{{ $removal->phone }}</a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                    @if($removal->phone_2)
                                                        <small class="text-muted d-block">{{ $removal->phone_2 }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $removal->vessel_name ?? '—' }}</td>
                                                <td>
                                                    @if($removal->pick_up_time)
                                                        {{ \Carbon\Carbon::parse($removal->pick_up_time)->format('h:i A') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="min-width-0">
                                                    <div class="d-flex flex-column" style="max-width: 180px;">
                                                        <span class="text-truncate" title="{{ $removal->from_location }}">{{ $removal->from_location ?? '—' }}</span>
                                                        <small class="text-muted">→</small>
                                                        <span class="text-truncate" title="{{ $removal->to_location }}">{{ $removal->to_location ?? '—' }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ $removal->flight_number ?? '—' }}</td>
                                                <td>{{ $removal->driver_name ?? 'Unassigned' }}</td>
                                                <td>{{ $removal->removedByUser->name ?? '—' }}</td>
                                                <td>{{ formatDate($removal->removed_at) }}</td>
                                                <td class="min-width-0" style="max-width: 200px;">
                                                    @if($removal->removal_remark)
                                                        <span class="text-break" title="{{ $removal->removal_remark }}">{{ $removal->removal_remark }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="avatar-sm mx-auto mb-3">
                                    <span class="avatar-title bg-light text-secondary rounded-circle fs-3">
                                        <i class="ri-user-unfollow-line"></i>
                                    </span>
                                </div>
                                <h5 class="text-muted">No Removed Crew</h5>
                                <p class="text-muted mb-0">No crew members have been removed from this trip.</p>
                            </div>
                        @endif
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

<!-- Friendly change modals (no IP / raw IDs) -->
@foreach(($lifecycle['timeline'] ?? []) as $event)
    @if(!empty($event['changes']))
    <div class="modal fade" id="lifecycleModal{{ $event['id'] }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $event['title'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-{{ $event['color'] }}-subtle text-{{ $event['color'] }} rounded-circle fs-4">
                                    <i class="{{ $event['icon'] }}"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fs-16 mb-1">{{ $event['title'] }}</h5>
                            <p class="text-muted mb-0">
                                by <span class="fw-semibold">{{ $event['actor_name'] }}</span>
                                <span class="badge bg-light text-muted border ms-1">{{ $event['actor_type'] }}</span>
                                on {{ $event['time']->format('M j, Y · g:i A') }}
                            </p>
                        </div>
                    </div>

                    <div class="card border shadow-none mb-0 bg-light-subtle">
                        <div class="card-header bg-transparent border-bottom-0">
                            <h6 class="card-title mb-0">What changed</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-borderless table-nowrap align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" style="width: 30%;">Field</th>
                                            <th scope="col">From</th>
                                            <th scope="col">To</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($event['changes'] as $change)
                                            <tr>
                                                <td class="fw-medium text-muted">{{ $change['label'] }}</td>
                                                <td class="text-danger">{{ $change['old'] }}</td>
                                                <td class="text-success">{{ $change['new'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endsection

@push('styles')
<style>
    .trip-lifecycle-stepper {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .trip-lifecycle-stepper {
            flex-direction: row;
            align-items: stretch;
            gap: 0;
        }
        .trip-lifecycle-step {
            flex: 1 1 0;
            position: relative;
            padding-right: 1rem;
        }
        .trip-lifecycle-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 18px;
            left: 44px;
            right: 8px;
            border-top: 2px dashed var(--vz-border-color, #dee2e6);
            z-index: 0;
        }
        .trip-lifecycle-step.is-completed:not(:last-child)::after {
            border-top-color: var(--vz-success, #0ab39c);
        }
    }
    .trip-lifecycle-step {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
    }
    .trip-lifecycle-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        font-size: 1rem;
    }
    .trip-lifecycle-body {
        min-width: 0;
    }
    .trip-lifecycle-step.is-pending {
        opacity: 0.65;
    }
</style>
@endpush

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

