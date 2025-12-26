@extends('layouts.app')

@section('title', 'Daily/Weekly Report | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Daily/Weekly Report</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Daily/Weekly</li>
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
                <form method="GET" action="{{ route('reports.daily-weekly') }}">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Report Type</label>
                            <select name="type" class="form-select">
                                <option value="daily" {{ $reportType == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ $reportType == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo->format('Y-m-d') }}">
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
                        <div class="col-md-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Apply Filters
                            </button>
                            <a href="{{ route('reports.daily-weekly') }}" class="btn btn-secondary">
                                <i class="ri-refresh-line me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
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
                        <h3 class="mb-0 fw-bold">{{ $trips->count() }}</h3>
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
                        <h3 class="mb-0 fw-bold">{{ $trips->where('status', \App\Models\TripCrew::STATUS_COMPLETED)->count() }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Avg Per Day</p>
                        <h3 class="mb-0 fw-bold">
                            @php
                                $days = $dateFrom->diffInDays($dateTo) + 1;
                                $avg = $days > 0 ? round($trips->count() / $days, 1) : 0;
                            @endphp
                            {{ $avg }}
                        </h3>
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
                            <i class="ri-calendar-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Period</p>
                        <h6 class="mb-0 fw-bold">{{ $dateFrom->format('M d') }} - {{ $dateTo->format('M d, Y') }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Trips by {{ ucfirst($reportType) }}</h6>
            </div>
            <div class="card-body">
                <canvas id="tripsChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Peak Hours</h6>
            </div>
            <div class="card-body">
                <canvas id="peakHoursChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Busiest Days & Daily Stats -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Busiest Days</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th class="text-end">Trips</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dayOfWeek as $day => $count)
                            <tr>
                                <td>{{ $day }}</td>
                                <td class="text-end">
                                    <span class="badge bg-primary">{{ $count }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">{{ ucfirst($reportType) }} Statistics</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ $reportType == 'weekly' ? 'Week' : 'Date' }}</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyStats as $stat)
                            <tr>
                                <td>{{ $stat['date'] }}</td>
                                <td class="text-end">{{ $stat['total'] }}</td>
                                <td class="text-end">
                                    <span class="badge bg-success">{{ $stat['completed'] }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trip Details -->
<div class="row">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Trip Details</h6>
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
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trips as $trip)
                            @php
                                $firstCrew = $trip->crews->first();
                            @endphp
                            <tr>
                                <td>{{ $trip->trip_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $trip->crews->pluck('name')->join(', ') }}">
                                        {{ $firstCrew->name ?? '-' }}
                                        @if($trip->crews->count() > 1)
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1">+{{ $trip->crews->count() - 1 }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td>{{ $trip->driver->name ?? '-' }}</td>
                                <td>{{ $firstCrew && $firstCrew->vessel ? $firstCrew->vessel->name : '-' }}</td>
                                <td>{{ $firstCrew && $firstCrew->pick_up_time ? \Carbon\Carbon::parse($firstCrew->pick_up_time)->format('h:i A') : '-' }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 120px;" title="{{ $firstCrew->from_location ?? '-' }}">
                                        {{ $firstCrew->from_location ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 120px;" title="{{ $firstCrew->to_location ?? '-' }}">
                                        {{ $firstCrew->to_location ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $trip->getStatusBadgeClass() }}">
                                        {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="ri-file-list-line fs-3 mb-2 d-block"></i>
                                    No trips found for the selected period
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
    // Trips Chart
    const tripsCtx = document.getElementById('tripsChart').getContext('2d');
    const tripsLabels = {!! json_encode(array_column($dailyStats, 'date')) !!};
    const tripsData = {!! json_encode(array_column($dailyStats, 'total')) !!};
    
    new Chart(tripsCtx, {
        type: 'bar',
        data: {
            labels: tripsLabels,
            datasets: [{
                label: 'Trips',
                data: tripsData,
                backgroundColor: 'rgba(13, 202, 240, 0.8)',
                borderColor: 'rgb(13, 202, 240)',
                borderWidth: 1
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

    // Peak Hours Chart
    const peakCtx = document.getElementById('peakHoursChart').getContext('2d');
    const peakLabels = {!! json_encode(array_keys($peakHours)) !!};
    const peakData = {!! json_encode(array_values($peakHours)) !!};
    
    new Chart(peakCtx, {
        type: 'bar',
        data: {
            labels: peakLabels,
            datasets: [{
                label: 'Trips',
                data: peakData,
                backgroundColor: 'rgba(25, 135, 84, 0.8)',
                borderColor: 'rgb(25, 135, 84)',
                borderWidth: 1
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

