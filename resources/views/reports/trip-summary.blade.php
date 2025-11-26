@extends('layouts.app')

@section('title', 'Trip Summary Report | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Trip Summary Report</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Trip Summary</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.trip-summary') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Driver</label>
                            <select name="driver_id" class="form-select">
                                <option value="">All Drivers</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Vessel</label>
                            <select name="vessel_id" class="form-select">
                                <option value="">All Vessels</option>
                                @foreach($vessels as $vessel)
                                    <option value="{{ $vessel->id }}" {{ request('vessel_id') == $vessel->id ? 'selected' : '' }}>
                                        {{ $vessel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                @foreach(\App\Models\Trip::getStatuses() as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Driver Type</label>
                            <select name="driver_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="1" {{ request('driver_type') == '1' ? 'selected' : '' }}>Internal</option>
                                <option value="2" {{ request('driver_type') == '2' ? 'selected' : '' }}>Outsourcing</option>
                            </select>
                        </div>
                        <div class="col-md-9 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Apply Filters
                            </button>
                            <a href="{{ route('reports.trip-summary') }}" class="btn btn-secondary">
                                <i class="ri-refresh-line me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded">
                            <i class="ri-route-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Total Trips</p>
                        <h3 class="mb-0 fw-bold">{{ $totalTrips }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-warning-subtle text-warning rounded">
                            <i class="ri-task-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Assigned</p>
                        <h3 class="mb-0 fw-bold">{{ $assignedTrips }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-info-subtle text-info rounded">
                            <i class="ri-time-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">In Progress</p>
                        <h3 class="mb-0 fw-bold">{{ $inProgressTrips }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-success-subtle text-success rounded">
                            <i class="ri-checkbox-circle-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Completed</p>
                        <h3 class="mb-0 fw-bold">{{ $completedTrips }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Trips by Status</h6>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Trips by Date</h6>
            </div>
            <div class="card-body">
                <canvas id="dateChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Trip Details Table -->
<div class="row">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">Trip Details</h6>
                    <span class="badge bg-primary">{{ $totalTrips }} trips</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Crew Name</th>
                                <th>Driver</th>
                                <th>Vessel</th>
                                <th>Pick-up Time</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Total Expenses</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trips as $trip)
                            <tr>
                                <td>{{ $trip->trip_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $trip->crews->pluck('name')->join(', ') }}">
                                        {{ $trip->crews->first()->name ?? '-' }}
                                        @if($trip->crews->count() > 1)
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1">+{{ $trip->crews->count() - 1 }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td>{{ $trip->driver->name }}</td>
                                <td>{{ $trip->vessel->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($trip->pick_up_time)->format('h:i A') }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 120px;" title="{{ $trip->from_location }}">
                                        {{ $trip->from_location }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 120px;" title="{{ $trip->to_location }}">
                                        {{ $trip->to_location }}
                                    </span>
                                </td>
                                <td>{{ number_format($trip->trip_expenses_sum_amount ?? 0, 2) }}</td>
                                <td>
                                    <span class="badge {{ $trip->getStatusBadgeClass() }}">
                                        {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="ri-file-list-line fs-3 mb-2 d-block"></i>
                                    No trips found for the selected filters
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($statusData)) !!},
            datasets: [{
                data: {!! json_encode(array_values($statusData)) !!},
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(13, 202, 240, 0.8)',
                    'rgba(25, 135, 84, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Date Chart
    const dateCtx = document.getElementById('dateChart').getContext('2d');
    const dateLabels = {!! json_encode(array_keys($tripsByDate->toArray())) !!};
    const dateData = {!! json_encode(array_values($tripsByDate->toArray())) !!};
    
    new Chart(dateCtx, {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [{
                label: 'Trips',
                data: dateData,
                borderColor: 'rgb(13, 202, 240)',
                backgroundColor: 'rgba(13, 202, 240, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection

