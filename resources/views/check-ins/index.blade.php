@extends('layouts.app')

@section('title', 'Check-Ins | ' . config('app.name'))

@section('content')
@include('partials.page-header', [
    'title' => 'Driver Check-Ins',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Check-Ins'],
    ],
])

<div class="row g-3 mb-4">
    @include('partials.stat-card', [
        'label' => 'Total Check-Ins',
        'value' => $totalCheckIns,
        'icon' => 'ri-login-circle-line',
        'color' => 'primary',
        'colClass' => 'col-xl-4 col-md-6',
        'useCounter' => false,
    ])
    @include('partials.stat-card', [
        'label' => 'Active Now',
        'value' => $activeCheckIns,
        'icon' => 'ri-radio-button-line',
        'color' => 'success',
        'colClass' => 'col-xl-4 col-md-6',
        'useCounter' => false,
    ])
    @include('partials.stat-card', [
        'label' => "Today's Check-Ins",
        'value' => $todayCheckIns,
        'icon' => 'ri-calendar-check-line',
        'color' => 'info',
        'colClass' => 'col-xl-4 col-md-6',
        'useCounter' => false,
    ])
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0">Check-Ins</h5>
                        @if(request()->filled('date_range') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('driver_id') || request()->filled('vehicle_id') || request()->filled('status'))
                            <small class="text-muted">
                                <i class="ri-filter-line me-1"></i>Filtered check-in results
                            </small>
                        @else
                            <small class="text-muted">
                                <i class="ri-information-line me-1"></i>Showing all check-ins. Use filters to narrow down results.
                            </small>
                        @endif
                    </div>
                    <button type="button" class="btn btn-soft-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#filterSection">
                        <i class="ri-filter-3-line me-1"></i> Filters
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('check-ins.index') }}" class="collapse show mb-4" id="filterSection">
                    <div class="row g-3 p-3 filter-bar">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Driver</label>
                            <select name="driver_id" class="form-select">
                                <option value="">All Drivers</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Vehicle</label>
                            <select name="vehicle_id" class="form-select">
                                <option value="">All Vehicles</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->name }}@if($vehicle->number) ({{ $vehicle->number }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="checked_in" {{ request('status') == 'checked_in' ? 'selected' : '' }}>Checked In</option>
                                <option value="checked_out" {{ request('status') == 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Date Range</label>
                            <select name="date_range" class="form-select">
                                <option value="all" {{ request('date_range', 'all') == 'all' && !request('date_from') && !request('date_to') ? 'selected' : '' }}>All Time</option>
                                <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ request('date_range') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                <option value="last_7_days" {{ request('date_range') == 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="this_month" {{ request('date_range') == 'this_month' ? 'selected' : '' }}>This Month</option>
                                <option value="last_month" {{ request('date_range') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-12 d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-search-line me-1"></i> Apply
                            </button>
                            <a href="{{ route('check-ins.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="check-ins-table" class="table table-nowrap align-middle mb-0 datatable">
                        <thead class="table-light">
                            <tr>
                                <th>Driver</th>
                                <th>Vehicle</th>
                                <th>Check-In Time</th>
                                <th>Start KM</th>
                                <th>Status</th>
                                <th>Checked Out / Duration</th>
                                <th class="text-center no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($checkIns as $checkIn)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($checkIn->driver?->photo)
                                                <img src="{{ asset('storage/' . $checkIn->driver->photo) }}" alt="{{ $checkIn->driver->name }}" class="rounded-circle avatar-xs me-2">
                                            @else
                                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center me-2 fw-semibold fs-13">
                                                    {{ $checkIn->driver ? strtoupper(substr($checkIn->driver->name, 0, 1)) : '?' }}
                                                </div>
                                            @endif
                                            <div>
                                                @if($checkIn->driver_id)
                                                    <a href="{{ route('drivers.show', $checkIn->driver_id) }}" class="fw-medium text-body">
                                                        {{ $checkIn->driver->name }}
                                                    </a>
                                                @else
                                                    <span class="fw-medium">N/A</span>
                                                @endif
                                                @if($checkIn->driver?->type)
                                                    <small class="text-muted d-block">{{ ucfirst($checkIn->driver->type) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($checkIn->vehicle_id)
                                            <a href="{{ route('vehicles.show', $checkIn->vehicle_id) }}" class="fw-medium text-body">
                                                {{ $checkIn->vehicle->name }}
                                            </a>
                                        @else
                                            <span class="fw-medium">N/A</span>
                                        @endif
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            @if($checkIn->vehicle?->number)
                                                <span class="badge bg-light text-dark border">{{ $checkIn->vehicle->number }}</span>
                                            @endif
                                            @if($checkIn->vehicle?->brand)
                                                <span class="badge bg-info-subtle text-info">{{ $checkIn->vehicle->brand }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $checkIn->check_in_date?->format('M d, Y') }}</div>
                                        <small class="text-muted"><i class="ri-time-line me-1"></i>{{ \Carbon\Carbon::parse($checkIn->check_in_time)->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ number_format((float) $checkIn->start_km, 2) }}</span>
                                        <small class="text-muted">KM</small>
                                    </td>
                                    <td>
                                        @if($checkIn->status === \App\Models\DriverCheckIn::STATUS_CHECKED_IN)
                                            <span class="badge bg-success-subtle text-success fs-12 px-2 py-1">
                                                <i class="ri-radio-button-line me-1"></i>Checked In
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary fs-12 px-2 py-1">
                                                <i class="ri-checkbox-circle-line me-1"></i>Checked Out
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($checkIn->checked_out_at)
                                            <div>{{ formatDate($checkIn->checked_out_at) }}</div>
                                            <small class="text-muted d-block">
                                                <i class="ri-history-line me-1"></i>{{ $checkIn->check_in_at ? $checkIn->check_in_at->diffForHumans($checkIn->checked_out_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) : 'N/A' }}
                                            </small>
                                        @else
                                            <span class="badge bg-info-subtle text-info">Active Session</span>
                                            <small class="text-muted d-block">
                                                <i class="ri-timer-line me-1"></i>Active {{ $checkIn->check_in_at ? $checkIn->check_in_at->diffForHumans(now(), ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) : 'N/A' }}
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-center no-export">
                                        <button type="button" class="btn btn-soft-info btn-sm" data-bs-toggle="modal" data-bs-target="#checkInModal{{ $checkIn->id }}" title="View Full Details">
                                            <i class="ri-eye-line me-1"></i> Details
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-muted mb-0">No check-ins found matching the selected criteria.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('partials.datatable', ['selector' => '#check-ins-table', 'order' => [[2, 'desc']]])
            </div>
        </div>
    </div>
</div>

@foreach ($checkIns as $checkIn)
    <div class="modal fade" id="checkInModal{{ $checkIn->id }}" tabindex="-1" aria-labelledby="checkInModalLabel{{ $checkIn->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="checkInModalLabel{{ $checkIn->id }}">
                        <i class="ri-login-circle-line me-1 text-primary"></i> Check-In Details #{{ $checkIn->id }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Status Banner -->
                        <div class="col-12">
                            <div class="p-3 rounded {{ $checkIn->status === \App\Models\DriverCheckIn::STATUS_CHECKED_IN ? 'bg-success-subtle text-success-emphasis' : 'bg-light text-secondary-emphasis' }} d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <span class="fw-semibold me-2">Status:</span>
                                    @if($checkIn->status === \App\Models\DriverCheckIn::STATUS_CHECKED_IN)
                                        <span class="badge bg-success">Checked In (Active)</span>
                                    @else
                                        <span class="badge bg-secondary">Checked Out</span>
                                    @endif
                                </div>
                                <div>
                                    <span class="fw-semibold me-2">Duration:</span>
                                    <span class="badge bg-primary-subtle text-primary">
                                        @if($checkIn->checked_out_at)
                                            {{ $checkIn->check_in_at ? $checkIn->check_in_at->diffForHumans($checkIn->checked_out_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) : 'N/A' }}
                                        @else
                                            Active for {{ $checkIn->check_in_at ? $checkIn->check_in_at->diffForHumans(now(), ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) : 'N/A' }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Driver Card -->
                        <div class="col-md-6">
                            <div class="card border h-100 mb-0">
                                <div class="card-header bg-light py-2">
                                    <h6 class="card-title mb-0 fs-14"><i class="ri-user-3-line me-1"></i> Driver Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        @if($checkIn->driver?->photo)
                                            <img src="{{ asset('storage/' . $checkIn->driver->photo) }}" alt="{{ $checkIn->driver->name }}" class="rounded-circle avatar-sm me-3">
                                        @else
                                            <div class="avatar-sm rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center me-3">
                                                <span class="text-primary fw-bold">{{ $checkIn->driver ? strtoupper(substr($checkIn->driver->name, 0, 1)) : '?' }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ $checkIn->driver->name ?? 'N/A' }}</h6>
                                            <small class="text-muted">{{ ucfirst($checkIn->driver->type ?? 'Driver') }}</small>
                                        </div>
                                    </div>
                                    <ul class="list-group list-group-flush border-top">
                                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                            <span class="text-muted">Phone:</span>
                                            <span class="fw-medium">{{ $checkIn->driver->phone ?? 'N/A' }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                            <span class="text-muted">Total KM Logged:</span>
                                            <span class="fw-medium">{{ number_format((float) ($checkIn->driver->total_kilometers ?? 0), 2) }} KM</span>
                                        </li>
                                    </ul>
                                    @if($checkIn->driver_id)
                                        <div class="mt-2 text-end">
                                            <a href="{{ route('drivers.show', $checkIn->driver_id) }}" class="btn btn-link btn-sm p-0">View Driver Profile &rarr;</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Card -->
                        <div class="col-md-6">
                            <div class="card border h-100 mb-0">
                                <div class="card-header bg-light py-2">
                                    <h6 class="card-title mb-0 fs-14"><i class="ri-car-line me-1"></i> Vehicle Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6 class="mb-1 fw-semibold">{{ $checkIn->vehicle->name ?? 'N/A' }}</h6>
                                        @if($checkIn->vehicle?->number)
                                            <span class="badge bg-light text-dark border">{{ $checkIn->vehicle->number }}</span>
                                        @endif
                                        @if($checkIn->vehicle?->brand)
                                            <span class="badge bg-info-subtle text-info">{{ $checkIn->vehicle->brand }}</span>
                                        @endif
                                    </div>
                                    <ul class="list-group list-group-flush border-top">
                                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                            <span class="text-muted">Vehicle Plate:</span>
                                            <span class="fw-medium">{{ $checkIn->vehicle->number ?? 'N/A' }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                            <span class="text-muted">Brand / Model:</span>
                                            <span class="fw-medium">{{ $checkIn->vehicle->brand ?? 'N/A' }}</span>
                                        </li>
                                    </ul>
                                    @if($checkIn->vehicle_id)
                                        <div class="mt-2 text-end">
                                            <a href="{{ route('vehicles.show', $checkIn->vehicle_id) }}" class="btn btn-link btn-sm p-0">View Vehicle Details &rarr;</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Session Timestamps & Odometer -->
                        <div class="col-12">
                            <div class="card border mb-0">
                                <div class="card-header bg-light py-2">
                                    <h6 class="card-title mb-0 fs-14"><i class="ri-time-line me-1"></i> Check-In Session Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label text-muted mb-0 small">Start Odometer (KM)</label>
                                            <div class="fw-bold fs-15 text-primary">{{ number_format((float) $checkIn->start_km, 2) }} KM</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted mb-0 small">Check-In Time</label>
                                            <div class="fw-medium">{{ $checkIn->check_in_date?->format('M d, Y') }} at {{ \Carbon\Carbon::parse($checkIn->check_in_time)->format('h:i A') }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted mb-0 small">Checked Out Time</label>
                                            <div class="fw-medium">
                                                @if($checkIn->checked_out_at)
                                                    {{ formatDate($checkIn->checked_out_at) }}
                                                @else
                                                    <span class="text-success fw-semibold">Active Session</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6 border-top pt-2">
                                            <label class="form-label text-muted mb-0 small">Exact Check-In Timestamp</label>
                                            <div>{{ $checkIn->check_in_at?->format('F d, Y h:i:s A') }}</div>
                                        </div>
                                        <div class="col-md-6 border-top pt-2">
                                            <label class="form-label text-muted mb-0 small">Auto Check-Out Deadline</label>
                                            <div>{{ $checkIn->autoCheckoutDueAt()->format('F d, Y h:i:s A') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

