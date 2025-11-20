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
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0">Dashboard</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item active">Welcome, {{ auth()->user()->name }}!</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row">
                        <!-- Total Drivers -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-animate border shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 overflow-hidden">
                                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Drivers</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-end justify-content-between mt-4">
                                        <div>
                                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                <span class="counter-value" data-target="{{ $totalDrivers }}">0</span>
                                            </h4>
                                            <a href="{{ route('drivers.index') }}" class="text-decoration-underline">View all drivers</a>
                                        </div>
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                                <i class="ri-user-line text-primary"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Vessels -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-animate border shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 overflow-hidden">
                                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Vessels</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-end justify-content-between mt-4">
                                        <div>
                                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                <span class="counter-value" data-target="{{ $totalVessels }}">0</span>
                                            </h4>
                                            <a href="{{ route('vessels.index') }}" class="text-decoration-underline">View all vessels</a>
                                        </div>
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                                <i class="ri-ship-line text-info"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Trips -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-animate border shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 overflow-hidden">
                                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Trips</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-end justify-content-between mt-4">
                                        <div>
                                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                <span class="counter-value" data-target="{{ $totalTrips }}">0</span>
                                            </h4>
                                            <a href="{{ route('trips.index') }}" class="text-decoration-underline">View all trips</a>
                                        </div>
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                                <i class="ri-route-line text-success"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Staff -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-animate border shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 overflow-hidden">
                                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Staff</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-end justify-content-between mt-4">
                                        <div>
                                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                <span class="counter-value" data-target="{{ $totalStaff }}">0</span>
                                            </h4>
                                            <a href="{{ route('staff.index') }}" class="text-decoration-underline">View all staff</a>
                                        </div>
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                                <i class="ri-team-line text-warning"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trip Status Cards -->
                    <div class="row">
                        <div class="col-xl-4 col-md-4">
                            <div class="card card-height-100 border shadow-sm">
                                <div class="card-header bg-light border-bottom align-items-center d-flex">
                                    <h5 class="card-title mb-0 flex-grow-1">Trip Status Overview</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                        <div>
                                            <h5 class="mb-0">{{ $assignedTrips }}</h5>
                                            <p class="text-muted mb-0">Assigned</p>
                                        </div>
                                        <div class="avatar-xs">
                                            <span class="avatar-title bg-warning-subtle text-warning rounded fs-3">
                                                <i class="ri-task-line"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                        <div>
                                            <h5 class="mb-0">{{ $inProgressTrips }}</h5>
                                            <p class="text-muted mb-0">In Progress</p>
                                        </div>
                                        <div class="avatar-xs">
                                            <span class="avatar-title bg-info-subtle text-info rounded fs-3">
                                                <i class="ri-time-line"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0">{{ $completedTrips }}</h5>
                                            <p class="text-muted mb-0">Completed</p>
                                        </div>
                                        <div class="avatar-xs">
                                            <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                                <i class="ri-checkbox-circle-line"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Trips -->
                        <div class="col-xl-8 col-md-8">
                            <div class="card border shadow-sm">
                                <div class="card-header bg-light border-bottom align-items-center d-flex">
                                    <h5 class="card-title mb-0 flex-grow-1">Recent Trips</h5>
                                    <div class="flex-shrink-0">
                                        <a href="{{ route('trips.index') }}" class="btn btn-soft-primary btn-sm">
                                            <i class="ri-eye-line align-middle"></i> View All
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive table-card">
                                        <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                                            <thead class="text-muted table-light">
                                                <tr>
                                                    <th scope="col">Crew Name</th>
                                                    <th scope="col">Driver</th>
                                                    <th scope="col">Vessel</th>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($recentTrips as $trip)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-grow-1">{{ $trip->crew_name }}</div>
                                                        </div>
                                                    </td>
                                                    <td>{{ $trip->driver->name ?? 'N/A' }}</td>
                                                    <td>{{ $trip->vessel->name ?? 'N/A' }}</td>
                                                    <td>{{ $trip->trip_date->format('M d, Y') }}</td>
                                                    <td>
                                                        <span class="badge {{ $trip->getStatusBadgeClass() }}">
                                                            {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No trips found</td>
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
                    <div class="row">
                        <div class="col-12">
                            <div class="card border shadow-sm">
                                <div class="card-header bg-light border-bottom align-items-center d-flex">
                                    <h5 class="card-title mb-0 flex-grow-1">Recent Activity</h5>
                                </div>
                                <div class="card-body">
                                    <div class="acitivity-timeline">
                                        @forelse($recentActivities as $activity)
                                        <div class="acitivity-item d-flex mb-3">
                                            <div class="flex-shrink-0">
                                                <div class="avatar-xs acitivity-avatar">
                                                    <div class="avatar-title bg-soft-success text-success rounded-circle">
                                                        <i class="ri-check-line"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">{{ $activity->description }}</h6>
                                                <p class="text-muted mb-0">
                                                    <small>by {{ $activity->user->name ?? 'System' }} - {{ $activity->created_at->diffForHumans() }}</small>
                                                </p>
                                            </div>
                                        </div>
                                        @empty
                                        <p class="text-muted text-center">No recent activity</p>
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

