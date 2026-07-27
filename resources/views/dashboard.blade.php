@extends('layouts.app')

@section('title', 'Dashboard | ' . config('app.name', 'ManageTrans'))

@section('content')
    @include('partials.page-header', [
        'title' => 'Dashboard',
        'breadcrumbs' => [
            ['label' => 'Dashboard'],
        ],
    ])

    {{-- Welcome --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-primary-gradient">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h3 class="text-white mb-2">Welcome back, {{ auth()->user()->name }}</h3>
                            <p class="text-white-50 mb-0">Here's what's happening with your transportation management today.</p>
                        </div>
                        <div class="text-end d-none d-md-block">
                            <p class="text-white mb-1 fw-medium">{{ now()->format('l') }}</p>
                            <p class="text-white-50 mb-0">{{ now()->format('F j, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-12">
            <h5 class="text-muted mb-3">Quick Overview</h5>
        </div>
    </div>

    <div class="row g-3">
        @include('partials.stat-card', [
            'label' => 'Drivers',
            'value' => $totalDrivers,
            'icon' => 'ri-user-line',
            'color' => 'primary',
            'url' => route('drivers.index'),
        ])
        @include('partials.stat-card', [
            'label' => 'Vessels',
            'value' => $totalVessels,
            'icon' => 'ri-ship-line',
            'color' => 'info',
            'url' => route('vessels.index'),
        ])
        @include('partials.stat-card', [
            'label' => 'Trips',
            'value' => $totalTrips,
            'icon' => 'ri-route-line',
            'color' => 'success',
            'url' => route('trips.index'),
        ])
        @include('partials.stat-card', [
            'label' => 'Staff',
            'value' => $totalStaff,
            'icon' => 'ri-team-line',
            'color' => 'warning',
            'url' => route('staff.index'),
        ])
    </div>

    <div class="row g-3 mt-1">
        @include('partials.stat-card', [
            'label' => 'Daily Activities',
            'value' => $totalDailyActivities,
            'icon' => 'ri-calendar-check-line',
            'color' => 'secondary',
            'subtitle' => '<i class="ri-calendar-todo-line me-1"></i><span class="fw-medium">' . $todayDailyActivities . '</span> today',
            'url' => route('daily-activities.index'),
        ])
    </div>

    <div class="row mt-4">
        <div class="col-12 mb-2">
            <h5 class="text-muted">Trip Management</h5>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4 col-md-12">
            <div class="card border shadow-sm h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Trip Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4 p-3 bg-warning-subtle rounded">
                        <div class="avatar-sm flex-shrink-0 me-3">
                            <span class="avatar-title bg-warning text-white rounded">
                                <i class="ri-task-line fs-4"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-0 fw-bold">{{ $assignedTrips }}</h4>
                            <p class="text-muted mb-0 small">Assigned Trips</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-4 p-3 bg-info-subtle rounded">
                        <div class="avatar-sm flex-shrink-0 me-3">
                            <span class="avatar-title bg-info text-white rounded">
                                <i class="ri-time-line fs-4"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-0 fw-bold">{{ $inProgressTrips }}</h4>
                            <p class="text-muted mb-0 small">In Progress</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center p-3 bg-success-subtle rounded">
                        <div class="avatar-sm flex-shrink-0 me-3">
                            <span class="avatar-title bg-success text-white rounded">
                                <i class="ri-checkbox-circle-line fs-4"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-0 fw-bold">{{ $completedTrips }}</h4>
                            <p class="text-muted mb-0 small">Completed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-md-12">
            <div class="card border shadow-sm h-100">
                <div class="card-header border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Recent Trips</h5>
                        <a href="{{ route('trips.index') }}" class="btn btn-sm btn-soft-primary">
                            View All <i class="ri-arrow-right-line align-middle ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Crew Name</th>
                                    <th>Driver</th>
                                    <th>Vessel</th>
                                    <th>Date</th>
                                    <th class="pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTrips as $trip)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs flex-shrink-0 me-2">
                                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                                    {{ substr($trip->crews->first()->name ?? '?', 0, 1) }}
                                                </span>
                                            </div>
                                            <div class="fw-medium">{{ $trip->crews->first()->name ?? 'Unknown' }}</div>
                                        </div>
                                    </td>
                                    <td>{{ $trip->driver->name ?? 'N/A' }}</td>
                                    <td>{{ $trip->crews->first()->vessel->name ?? 'N/A' }}</td>
                                    <td><small>{{ $trip->trip_date->format('M d, Y') }}</small></td>
                                    <td class="pe-4">
                                        <span class="badge {{ $trip->getStatusBadgeClass() }}">
                                            {{ ucfirst(str_replace('_', ' ', $trip->status ?? 'unknown')) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5">
                                        @include('partials.empty-state', [
                                            'icon' => 'ri-file-list-line',
                                            'title' => 'No trips found',
                                            'hint' => 'New trips will appear here.',
                                        ])
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

    <div class="row mt-4">
        <div class="col-12 mb-2">
            <h5 class="text-muted">Driver Performance</h5>
        </div>
    </div>

    <div class="row g-3">
        @if($busiestDriver)
        <div class="col-xl-6">
            <div class="card border shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ri-trophy-line me-2 text-warning"></i>Busiest Driver
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        @if($busiestDriver->photo)
                            <img src="{{ asset('storage/' . $busiestDriver->photo) }}" alt="{{ $busiestDriver->name }}" class="rounded-circle avatar-lg me-3">
                        @else
                            <div class="avatar-lg rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center me-3">
                                <span class="text-primary fs-3 fw-bold">{{ substr($busiestDriver->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <h5 class="mb-1 fw-bold">{{ $busiestDriver->name }}</h5>
                            <p class="text-muted mb-0 small">
                                @if($busiestDriver->type == \App\Models\Driver::TYPE_INTERNAL)
                                    <span class="badge bg-info">Internal</span>
                                @else
                                    <span class="badge bg-warning">Outsourcing</span>
                                @endif
                                @if($busiestDriver->contact)
                                    <span class="ms-2"><i class="ri-phone-line me-1"></i>{{ $busiestDriver->contact }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-6">
                            <div class="p-3 bg-primary-subtle rounded text-center">
                                <h3 class="mb-0 fw-bold text-primary">{{ $busiestDriver->trips_count }}</h3>
                                <p class="text-muted mb-0 small">Total Trips</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-success-subtle rounded text-center">
                                <h3 class="mb-0 fw-bold text-success">{{ $busiestDriverCompletedTrips }}</h3>
                                <p class="text-muted mb-0 small">Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('drivers.show', $busiestDriver) }}" class="btn btn-sm btn-primary w-100">
                            <i class="ri-eye-line me-1"></i> View Driver Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="col-xl-6">
            <div class="card border shadow-sm h-100">
                <div class="card-header border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Top 5 Drivers</h5>
                        <a href="{{ route('reports.driver-performance') }}" class="btn btn-sm btn-soft-primary">
                            View Report <i class="ri-arrow-right-line align-middle ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Driver</th>
                                    <th class="text-end">Trips</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topDrivers as $index => $driver)
                                <tr>
                                    <td>
                                        @if($index == 0)
                                            <span class="badge bg-warning">1</span>
                                        @elseif($index == 1)
                                            <span class="badge bg-secondary">2</span>
                                        @elseif($index == 2)
                                            <span class="badge bg-warning-subtle text-warning">3</span>
                                        @else
                                            <span class="text-muted">#{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($driver->photo)
                                                <img src="{{ asset('storage/' . $driver->photo) }}" alt="{{ $driver->name }}" class="rounded-circle avatar-xs me-2">
                                            @else
                                                <div class="avatar-xs rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center me-2">
                                                    <span class="text-primary small">{{ substr($driver->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <span class="fw-medium">{{ $driver->name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-primary">{{ $driver->trips_count }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3">
                                        @include('partials.empty-state', [
                                            'icon' => 'ri-user-line',
                                            'title' => 'No drivers found',
                                        ])
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

    <div class="row mt-4">
        <div class="col-12 mb-2">
            <h5 class="text-muted">Daily Activities</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-header border-bottom">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-0">Today's Daily Activities</h5>
                            <small class="text-muted">Showing today's activities only</small>
                        </div>
                        <a href="{{ route('daily-activities.index') }}" class="btn btn-sm btn-soft-primary">
                            View All <i class="ri-arrow-right-line align-middle ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Driver</th>
                                    <th>Activity Date</th>
                                    <th>Note</th>
                                    <th>Image</th>
                                    <th class="pe-4">Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentDailyActivities as $activity)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            @if($activity->driver && $activity->driver->photo)
                                                <img src="{{ asset('storage/' . $activity->driver->photo) }}" alt="{{ $activity->driver->name }}" class="rounded-circle avatar-xs me-2">
                                            @else
                                                <div class="avatar-xs rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center me-2">
                                                    <span class="text-primary small">{{ $activity->driver ? substr($activity->driver->name, 0, 1) : '?' }}</span>
                                                </div>
                                            @endif
                                            <span class="fw-medium">{{ $activity->driver->name ?? 'Unknown Driver' }}</span>
                                        </div>
                                    </td>
                                    <td><small>{{ $activity->activity_date ? $activity->activity_date->format('M d, Y') : 'N/A' }}</small></td>
                                    <td>
                                        <span class="text-truncate d-inline-block mt-note-truncate" title="{{ $activity->note }}">
                                            {{ $activity->note ? Str::limit($activity->note, 50) : 'No note' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($activity->image)
                                            <a href="{{ asset('storage/' . $activity->image) }}" target="_blank" class="btn btn-sm btn-soft-primary">
                                                <i class="ri-image-line me-1"></i>View
                                            </a>
                                        @else
                                            <span class="text-muted small">No image</span>
                                        @endif
                                    </td>
                                    <td class="pe-4">
                                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5">
                                        @include('partials.empty-state', [
                                            'icon' => 'ri-calendar-check-line',
                                            'title' => 'No daily activities found',
                                        ])
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

    <div class="row mt-4">
        <div class="col-12 mb-2">
            <h5 class="text-muted">Recent Activity</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Activity Log</h5>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        @forelse($recentActivities as $activity)
                        <div class="d-flex align-items-start mb-4 pb-3 border-bottom">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar-xs">
                                    <div class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                        <i class="ri-check-line"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 fw-medium">{{ $activity->description }}</p>
                                <p class="text-muted mb-0">
                                    <small>
                                        <i class="ri-user-line me-1"></i>{{ $activity->user->name ?? 'System' }}
                                        <span class="mx-2">•</span>
                                        <i class="ri-time-line me-1"></i>{{ $activity->created_at->diffForHumans() }}
                                    </small>
                                </p>
                            </div>
                        </div>
                        @empty
                            @include('partials.empty-state', [
                                'icon' => 'ri-history-line',
                                'title' => 'No recent activity',
                            ])
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('assets/libs/counterup/counterup.min.js') }}"></script>
@endpush
