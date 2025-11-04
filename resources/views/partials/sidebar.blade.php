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
                    <span class="logo-lg">
                        <img src="{{ $logoDark }}" alt="" height="40">
                    </span>
                </a>
                <!-- Light Logo-->
                <a href="{{ route('dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ $logoSm }}" alt="" height="35">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ $logoLight }}" alt="" height="40">
                    </span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>

            <div id="scrollbar">
                <div class="container-fluid">

                    <div id="two-column-menu">
                    </div>
                    <ul class="navbar-nav" id="navbar-nav">
                        <li class="menu-title"><span data-key="t-menu">Menu</span></li>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('dashboard') }}">
                                <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('drivers.index') }}">
                                <i class="ri-taxi-line"></i> <span>Drivers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('vessels.index') }}">
                                <i class="ri-ship-line"></i> <span>Vessels</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('trips.index') }}">
                                <i class="ri-road-map-line"></i> <span>Trips</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('staff.index') }}">
                                <i class="ri-team-line"></i> <span>Staff</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('settings.index') }}">
                                <i class="ri-settings-2-line"></i> <span>Settings</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <!-- Sidebar -->
            </div>

            <div class="sidebar-background"></div>
        </div>
