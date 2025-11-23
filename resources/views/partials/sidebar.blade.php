<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        @php
        $appLogo = getSetting('app_logo');
        $logoSm = $appLogo ? asset('storage/' . $appLogo) : asset('assets/images/logo-sm.png');
        $logoDark = $appLogo ? asset('storage/' . $appLogo) : asset('assets/images/logo-dark.png');
        $logoLight = $appLogo ? asset('storage/' . $appLogo) : asset('assets/images/logo-light.png');
        @endphp
        <!-- Dark Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ $logoSm }}" alt="" height="35">
            </span>
            <span class="logo-lg" style="width: 220px !important; height: auto !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 10px 0;">
                <img src="{{ $logoDark }}" alt="" style="max-width: 100%; height: auto; max-height: 100px; width: auto;">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ $logoSm }}" alt="" height="35">
            </span>
            <span class="logo-lg" style="width: 220px !important; height: auto !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 10px 0;">
                <img src="{{ $logoLight }}" alt="" style="max-width: 100%; height: auto; max-height: 100px; width: auto;">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span data-key="t-menu">Menu</span></li>

                @if(auth()->user()->hasPermission('view_dashboard'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">
                        <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">Dashboard</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_drivers'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}"
                        href="{{ route('drivers.index') }}">
                        <i class="ri-taxi-line"></i> <span>Drivers</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_vessels'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('vessels.*') ? 'active' : '' }}"
                        href="{{ route('vessels.index') }}">
                        <i class="ri-ship-line"></i> <span>Vessels</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_trips'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('trips.*') ? 'active' : '' }}"
                        href="{{ route('trips.index') }}">
                        <i class="ri-road-map-line"></i> <span>Trips</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_trips'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('trip-issue-types.*') ? 'active' : '' }}"
                        href="{{ route('trip-issue-types.index') }}">
                        <i class="ri-alert-line"></i> <span>Trip Issue Types</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_trips'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('trip-expense-types.*') ? 'active' : '' }}"
                        href="{{ route('trip-expense-types.index') }}">
                        <i class="ri-money-dollar-circle-line"></i> <span>Trip Expense Types</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_staff'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('staff.*') ? 'active' : '' }}"
                        href="{{ route('staff.index') }}">
                        <i class="ri-team-line"></i> <span>Staff</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_activity_logs'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('activity-logs') ? 'active' : '' }}"
                        href="{{ route('activity-logs') }}">
                        <i class="ri-history-line"></i> <span>Activity Logs</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_reports'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                        href="{{ route('reports.index') }}">
                        <i class="ri-file-chart-line"></i> <span>Reports</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('manage_permissions'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}"
                        href="{{ route('permissions.index') }}">
                        <i class="ri-shield-user-line"></i> <span>Permissions</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_settings'))
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"
                        href="{{ route('settings.index') }}">
                        <i class="ri-settings-2-line"></i> <span>Settings</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>