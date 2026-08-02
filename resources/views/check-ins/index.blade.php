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
                        @if(!request()->filled('date_range') && !request()->filled('date_from') && !request()->filled('date_to'))
                            <small class="text-muted">
                                <i class="ri-information-line me-1"></i>Showing today's check-ins. Use filters to view previous data.
                            </small>
                        @else
                            <small class="text-muted">
                                <i class="ri-filter-line me-1"></i>Filtered results
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
                                <option value="">Custom / Today</option>
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
                                <th>Date</th>
                                <th>Time</th>
                                <th>Start KM</th>
                                <th>Status</th>
                                <th>Checked Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($checkIns as $checkIn)
                                <tr>
                                    <td>{{ $checkIn->driver->name ?? 'N/A' }}</td>
                                    <td>
                                        <div>{{ $checkIn->vehicle->name ?? 'N/A' }}</div>
                                        @if($checkIn->vehicle?->number)
                                            <small class="text-muted">{{ $checkIn->vehicle->number }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $checkIn->check_in_date?->format('M d, Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($checkIn->check_in_time)->format('h:i A') }}</td>
                                    <td>{{ number_format((float) $checkIn->start_km, 2) }}</td>
                                    <td>
                                        @if($checkIn->status === \App\Models\DriverCheckIn::STATUS_CHECKED_IN)
                                            <span class="badge bg-success-subtle text-success">Checked In</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Checked Out</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($checkIn->checked_out_at)
                                            {{ formatDate($checkIn->checked_out_at) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-muted mb-0">No check-ins found.</p>
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
@endsection
