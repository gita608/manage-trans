<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">

<head>
    <meta charset="utf-8" />
    <title>Dashboard | {{ config('app.name', 'ManageTrans') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Transportation Management System Dashboard" name="description" />
    <meta content="ManageTrans" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Layout config Js -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />
</head>

<body>
    <!-- Begin page -->
    <div id="layout-wrapper">

        @include('partials.header')
        @include('partials.sidebar')

        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>

        <!-- Start right Content here -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Welcome Section -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h3 class="text-white mb-2">Welcome back, {{ auth()->user()->name }}! 👋</h3>
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

                    <!-- Quick Stats Overview -->
                    <div class="row mb-2">
                        <div class="col-12">
                            <h5 class="text-muted mb-3">Quick Overview</h5>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-3">
                        <!-- Total Drivers -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-animate border shadow-sm position-relative">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-sm flex-shrink-0 me-3">
                                            <span class="avatar-title bg-primary-subtle text-primary rounded">
                                                <i class="ri-user-line fs-4"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-uppercase fw-medium text-muted mb-0 fs-12">DRIVERS</p>
                                        </div>
                                    </div>
                                    <h3 class="mb-3 fw-bold">
                                        <span class="counter-value" data-target="{{ $totalDrivers }}">0</span>
                                    </h3>
                                    <a href="{{ route('drivers.index') }}" class="text-decoration-none text-muted small">
                                        View Details <i class="ri-arrow-right-line align-middle"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Total Vessels -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-animate border shadow-sm position-relative">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-sm flex-shrink-0 me-3">
                                            <span class="avatar-title bg-info-subtle text-info rounded">
                                                <i class="ri-ship-line fs-4"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-uppercase fw-medium text-muted mb-0 fs-12">VESSELS</p>
                                        </div>
                                    </div>
                                    <h3 class="mb-3 fw-bold">
                                        <span class="counter-value" data-target="{{ $totalVessels }}">0</span>
                                    </h3>
                                    <a href="{{ route('vessels.index') }}" class="text-decoration-none text-muted small">
                                        View Details <i class="ri-arrow-right-line align-middle"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Total Trips -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-animate border shadow-sm position-relative">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-sm flex-shrink-0 me-3">
                                            <span class="avatar-title bg-success-subtle text-success rounded">
                                                <i class="ri-route-line fs-4"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-uppercase fw-medium text-muted mb-0 fs-12">TRIPS</p>
                                        </div>
                                    </div>
                                    <h3 class="mb-3 fw-bold">
                                        <span class="counter-value" data-target="{{ $totalTrips }}">0</span>
                                    </h3>
                                    <a href="{{ route('trips.index') }}" class="text-decoration-none text-muted small">
                                        View Details <i class="ri-arrow-right-line align-middle"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Total Staff -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-animate border shadow-sm position-relative">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-sm flex-shrink-0 me-3">
                                            <span class="avatar-title bg-warning-subtle text-warning rounded">
                                                <i class="ri-team-line fs-4"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-uppercase fw-medium text-muted mb-0 fs-12">STAFF</p>
                                        </div>
                                    </div>
                                    <h3 class="mb-3 fw-bold">
                                        <span class="counter-value" data-target="{{ $totalStaff }}">0</span>
                                    </h3>
                                    <a href="{{ route('staff.index') }}" class="text-decoration-none text-muted small">
                                        View Details <i class="ri-arrow-right-line align-middle"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trip Status & Recent Activity -->
                    <div class="row mt-4">
                        <div class="col-12 mb-2">
                            <h5 class="text-muted">Trip Management</h5>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-xl-4 col-md-12">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light border-bottom">
                                    <h6 class="card-title mb-0 fw-semibold">Trip Status</h6>
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

                        <!-- Recent Trips -->
                        <div class="col-xl-8 col-md-12">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light border-bottom">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6 class="card-title mb-0 fw-semibold">Recent Trips</h6>
                                        <a href="{{ route('trips.index') }}" class="btn btn-sm btn-soft-primary">
                                            View All <i class="ri-arrow-right-line align-middle ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-nowrap align-middle mb-0">
                                            <thead class="bg-light">
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
                                                                    {{ substr($trip->crew_name, 0, 1) }}
                                                                </span>
                                                            </div>
                                                            <div class="fw-medium">{{ $trip->crew_name }}</div>
                                                        </div>
                                                    </td>
                                                    <td>{{ $trip->driver->name ?? 'N/A' }}</td>
                                                    <td>{{ $trip->vessel->name ?? 'N/A' }}</td>
                                                    <td><small>{{ $trip->trip_date->format('M d, Y') }}</small></td>
                                                    <td class="pe-4">
                                                        <span class="badge {{ $trip->getStatusBadgeClass() }}">
                                                            {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="ri-file-list-line fs-3 mb-2 d-block"></i>
                                                        No trips found
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

                    <!-- Busiest Driver -->
                    <div class="row mt-4">
                        <div class="col-12 mb-2">
                            <h5 class="text-muted">Driver Performance</h5>
                        </div>
                    </div>

                    <div class="row g-3">
                        @if($busiestDriver)
                        <div class="col-xl-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light border-bottom">
                                    <h6 class="card-title mb-0 fw-semibold">
                                        <i class="ri-trophy-line me-2 text-warning"></i>Busiest Driver
                                    </h6>
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
                                    <div class="row g-3 mt-3">
                                        <div class="col-6">
                                            <div class="p-3 bg-primary-subtle rounded text-center">
                                                <h3 class="mb-0 fw-bold text-primary">{{ $busiestDriver->trips_count }}</h3>
                                                <p class="text-muted mb-0 small">Total Trips</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 bg-success-subtle rounded text-center">
                                                <h3 class="mb-0 fw-bold text-success">
                                                    {{ $busiestDriver->trips()->where('status', \App\Models\Trip::STATUS_COMPLETED)->count() }}
                                                </h3>
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

                        <!-- Top 5 Drivers -->
                        <div class="col-xl-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light border-bottom">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6 class="card-title mb-0 fw-semibold">Top 5 Drivers</h6>
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
                                                            <span class="badge bg-warning">🥇</span>
                                                        @elseif($index == 1)
                                                            <span class="badge bg-secondary">🥈</span>
                                                        @elseif($index == 2)
                                                            <span class="badge bg-warning-subtle">🥉</span>
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
                                                    <td colspan="3" class="text-center text-muted py-3">
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

                    <!-- Recent Activity -->
                    <div class="row mt-4">
                        <div class="col-12 mb-2">
                            <h5 class="text-muted">Recent Activity</h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light border-bottom">
                                    <h6 class="card-title mb-0 fw-semibold">Activity Log</h6>
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
                                        <div class="text-center text-muted py-4">
                                            <i class="ri-history-line fs-3 mb-2 d-block"></i>
                                            <p class="mb-0">No recent activity</p>
                                        </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            @include('partials.footer')
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('assets/js/plugins.js') }}"></script>

    <!-- Counter Up -->
    <script src="{{ asset('assets/libs/counterup/counterup.min.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>

</html>

