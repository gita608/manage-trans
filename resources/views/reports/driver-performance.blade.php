@extends('layouts.app')

@section('title', 'Driver Performance Report | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Driver Performance Report</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Driver Performance</li>
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
            <div class="card-header bg-light border-bottom">
                <h6 class="card-title mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.driver-performance') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Driver Type</label>
                            <select name="driver_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="1" {{ request('driver_type') == '1' ? 'selected' : '' }}>Internal</option>
                                <option value="2" {{ request('driver_type') == '2' ? 'selected' : '' }}>Outsourcing</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Apply
                            </button>
                            <a href="{{ route('reports.driver-performance') }}" class="btn btn-secondary">
                                <i class="ri-refresh-line me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Comparison Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0">Internal Drivers</h6>
                    <span class="badge bg-info">{{ $internalDrivers }} drivers</span>
                </div>
                <h3 class="mb-0 fw-bold">{{ $internalTrips }}</h3>
                <p class="text-muted mb-0 small">Total trips</p>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0">Outsourcing Drivers</h6>
                    <span class="badge bg-warning">{{ $outsourcingDrivers }} drivers</span>
                </div>
                <h3 class="mb-0 fw-bold">{{ $outsourcingTrips }}</h3>
                <p class="text-muted mb-0 small">Total trips</p>
            </div>
        </div>
    </div>
</div>

<!-- Driver Performance Table -->
<div class="row">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h6 class="card-title mb-0">Driver Performance Details</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Driver Name</th>
                                <th>Type</th>
                                <th>Total Trips</th>
                                <th>Assigned</th>
                                <th>In Progress</th>
                                <th>Completed</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($driverStats as $index => $stat)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($stat['driver']->photo)
                                            <img src="{{ asset('storage/' . $stat['driver']->photo) }}" alt="{{ $stat['driver']->name }}" class="rounded-circle avatar-xs me-2">
                                        @else
                                            <div class="avatar-xs rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center me-2">
                                                <span class="text-primary small">{{ substr($stat['driver']->name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                        <span class="fw-medium">{{ $stat['driver']->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($stat['driver']->type == \App\Models\Driver::TYPE_INTERNAL)
                                        <span class="badge bg-info">Internal</span>
                                    @else
                                        <span class="badge bg-warning">Outsourcing</span>
                                    @endif
                                </td>
                                <td><strong>{{ $stat['total_trips'] }}</strong></td>
                                <td><span class="badge bg-warning">{{ $stat['assigned'] }}</span></td>
                                <td><span class="badge bg-info">{{ $stat['in_progress'] }}</span></td>
                                <td><span class="badge bg-success">{{ $stat['completed'] }}</span></td>
                                <td>
                                    @php
                                        $completionRate = $stat['total_trips'] > 0 
                                            ? round(($stat['completed'] / $stat['total_trips']) * 100, 1) 
                                            : 0;
                                    @endphp
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 me-2">
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $completionRate }}%"></div>
                                            </div>
                                        </div>
                                        <span class="fw-medium">{{ $completionRate }}%</span>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="ri-user-line fs-3 mb-2 d-block"></i>
                                    No drivers found
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
@endsection

