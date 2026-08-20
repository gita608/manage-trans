<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        @php
        $logoSm = brandingUrl('app_logo', 'assets/images/logo-sm.png');
        $logoDark = brandingUrl('app_logo', 'assets/images/logo-dark.png');
        $logoLight = brandingUrl('app_logo', 'assets/images/logo-light.png');
        @endphp
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ $logoSm }}" alt="" height="35">
            </span>
            <span class="logo-lg">
                <img src="{{ $logoDark }}" alt="">
            </span>
        </a>
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ $logoSm }}" alt="" height="35">
            </span>
            <span class="logo-lg">
                <img src="{{ $logoLight }}" alt="">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">
                @if(auth()->check() && auth()->user() && auth()->user()->hasAnyPermission(['view_dashboard']))
                <li class="menu-title"><span>Menu</span></li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_dashboard'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">
                        <i class="ri-dashboard-2-line"></i> <span>Dashboard</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasAnyPermission(['view_trips', 'view_drivers', 'view_vessels', 'view_vehicles']))
                <li class="menu-title"><span>Operations</span></li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_trips'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('trips.*') ? 'active' : '' }}"
                        href="{{ route('trips.index') }}">
                        <i class="ri-road-map-line"></i> <span>Trips</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('partner-requests.*') ? 'active' : '' }}"
                        href="{{ route('partner-requests.index') }}">
                        <i class="ri-file-list-3-line"></i>
                        <span>Partner Requests</span>
                        @if(($pendingPartnerRequestCount ?? 0) > 0)
                            <span class="badge rounded-pill bg-warning ms-1">{{ $pendingPartnerRequestCount }}</span>
                        @endif
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_drivers'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('drivers.*') && !request()->routeIs('drivers.map') ? 'active' : '' }}"
                        href="{{ route('drivers.index') }}">
                        <i class="ri-taxi-line"></i> <span>Drivers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('drivers.map') ? 'active' : '' }}"
                        href="{{ route('drivers.map') }}">
                        <i class="ri-map-pin-line"></i> <span>Driver Locations</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('daily-activities.*') ? 'active' : '' }}"
                        href="{{ route('daily-activities.index') }}">
                        <i class="ri-calendar-check-line"></i> <span>Daily Activities</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('check-ins.*') ? 'active' : '' }}"
                        href="{{ route('check-ins.index') }}">
                        <i class="ri-login-circle-line"></i> <span>Check-Ins</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_vessels'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('vessels.*') ? 'active' : '' }}"
                        href="{{ route('vessels.index') }}">
                        <i class="ri-ship-line"></i> <span>Vessels</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_vehicles'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}"
                        href="{{ route('vehicles.index') }}">
                        <i class="ri-car-line"></i> <span>Vehicles</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_trips'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('trip-issue-types.*') ? 'active' : '' }}"
                        href="{{ route('trip-issue-types.index') }}">
                        <i class="ri-alert-line"></i> <span>Trip Issue Types</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('trip-expense-types.*') ? 'active' : '' }}"
                        href="{{ route('trip-expense-types.index') }}">
                        <i class="ri-money-dollar-circle-line"></i> <span>Trip Expense Types</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasAnyPermission(['view_partners', 'view_staff', 'view_reports', 'view_notifications']))
                <li class="menu-title"><span>Management</span></li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_partners'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('partners.*') ? 'active' : '' }}"
                        href="{{ route('partners.index') }}">
                        <i class="ri-group-line"></i> <span>Partners</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_staff'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('staff.*') ? 'active' : '' }}"
                        href="{{ route('staff.index') }}">
                        <i class="ri-team-line"></i> <span>Staff</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_reports'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                        href="{{ route('reports.index') }}">
                        <i class="ri-file-chart-line"></i> <span>Reports</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_notifications'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('notifications.admin-index') ? 'active' : '' }}"
                        href="{{ route('notifications.admin-index') }}">
                        <i class="ri-notification-3-line"></i> <span>Notifications</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasAnyPermission(['view_activity_logs', 'manage_permissions', 'view_settings']))
                <li class="menu-title"><span>System</span></li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_activity_logs'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('activity-logs') ? 'active' : '' }}"
                        href="{{ route('activity-logs') }}">
                        <i class="ri-history-line"></i> <span>Activity Logs</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('manage_permissions'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}"
                        href="{{ route('permissions.index') }}">
                        <i class="ri-shield-user-line"></i> <span>Permissions</span>
                    </a>
                </li>
                @endif

                @if(auth()->check() && auth()->user() && auth()->user()->hasPermission('view_settings'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"
                        href="{{ route('settings.index') }}">
                        <i class="ri-settings-2-line"></i> <span>Settings</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </div>

    <div class="sidebar-background"></div>
</div>
