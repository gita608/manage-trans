<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="{{ route('dashboard') }}" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{ brandingUrl('app_logo', 'assets/images/logo-sm.png') }}" alt="{{ getSetting('app_name', config('app.name')) }}" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ brandingUrl('app_logo', 'assets/images/logo-dark.png') }}" alt="{{ getSetting('app_name', config('app.name')) }}" height="17">
                        </span>
                    </a>

                    <a href="{{ route('dashboard') }}" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{ brandingUrl('app_logo', 'assets/images/logo-sm.png') }}" alt="{{ getSetting('app_name', config('app.name')) }}" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ brandingUrl('app_logo', 'assets/images/logo-light.png') }}" alt="{{ getSetting('app_name', config('app.name')) }}" height="17">
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

            </div>

            <div class="d-flex align-items-center">

                <button type="button" id="pwa-install-btn"
                    class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle ms-1 header-item d-none"
                    title="Install app" aria-label="Install app">
                    <i class="ri-download-2-line fs-22"></i>
                </button>

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-toggle="fullscreen">
                        <i class="ri-fullscreen-line fs-22"></i>
                    </button>
                </div>

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class="ri-moon-line fs-22"></i>
                    </button>
                </div>

                <div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <i class="ri-notification-3-line fs-22"></i>
                        @php
                            $unreadCount = auth()->check() && auth()->user() ? auth()->user()->notifications()->where('is_read', false)->count() : 0;
                        @endphp
                        @if($unreadCount > 0)
                            <span class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger">{{ $unreadCount }}<span class="visually-hidden">unread messages</span></span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">

                        <div class="dropdown-head bg-primary bg-pattern rounded-top">
                            <div class="p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="m-0 fs-16 fw-semibold text-white"> Notifications </h6>
                                    </div>
                                    <div class="col-auto dropdown-tabs">
                                        @if($unreadCount > 0)
                                            <span class="badge bg-light-subtle text-body fs-13"> {{ $unreadCount }} New</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="px-2 pt-2">
                                <ul class="nav nav-tabs dropdown-tabs nav-tabs-custom" data-dropdown-tabs="true" id="notificationItemsTab" role="tablist">
                                    <li class="nav-item waves-effect waves-light">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#all-noti-tab" role="tab" aria-selected="true">
                                            All ({{ auth()->check() && auth()->user() ? auth()->user()->notifications()->count() : 0 }})
                                        </a>
                                    </li>
                                </ul>
                            </div>

                        </div>

                        <div class="tab-content position-relative" id="notificationItemsTabContent">
                            <div class="tab-pane fade show active py-2 ps-2" id="all-noti-tab" role="tabpanel">
                                <div data-simplebar style="max-height: 300px;" class="pe-2">
                                    @php
                                        $recentNotifications = auth()->check() && auth()->user() ? auth()->user()->notifications()->orderBy('created_at', 'desc')->limit(5)->get() : collect([]);
                                    @endphp
                                    
                                    @forelse($recentNotifications as $notification)
                                        <div class="text-reset notification-item d-block dropdown-item position-relative {{ $notification->is_read ? '' : 'active' }}">
                                            <div class="d-flex">
                                                <div class="avatar-xs me-3 flex-shrink-0">
                                                    <span class="avatar-title bg-{{ $notification->type == 'success' ? 'success' : ($notification->type == 'warning' ? 'warning' : ($notification->type == 'danger' ? 'danger' : 'info')) }}-subtle text-{{ $notification->type == 'success' ? 'success' : ($notification->type == 'warning' ? 'warning' : ($notification->type == 'danger' ? 'danger' : 'info')) }} rounded-circle fs-16">
                                                        <i class="ri-{{ $notification->type == 'success' ? 'checkbox-circle-line' : ($notification->type == 'warning' ? 'alert-line' : ($notification->type == 'danger' ? 'close-circle-line' : 'information-line')) }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mt-0 mb-2 lh-base">{{ $notification->title }}</h6>
                                                    <p class="mb-1 fs-13 text-muted">{{ Str::limit($notification->message, 60) }}</p>
                                                    <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                        <span><i class="mdi mdi-clock-outline"></i> {{ $notification->created_at->diffForHumans() }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-4">
                                            <i class="ri-notification-off-line fs-24 text-muted"></i>
                                            <p class="text-muted mt-2">No notifications yet</p>
                                        </div>
                                    @endforelse

                                    @if($recentNotifications->count() > 0)
                                        <div class="my-3 text-center view-all">
                                            <a href="{{ route('notifications.index') }}" class="btn btn-soft-success waves-effect waves-light">View
                                                All Notifications <i class="ri-arrow-right-line align-middle"></i></a>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            @if(auth()->check() && auth()->user() && auth()->user()->photo)
                                <img class="rounded-circle header-profile-user" src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Header Avatar">
                            @else
                                <div class="rounded-circle header-profile-user bg-primary-subtle d-flex align-items-center justify-content-center">
                                    <span class="text-primary fw-semibold">{{ substr(auth()->check() && auth()->user() ? (auth()->user()->name ?? 'U') : 'U', 0, 1) }}</span>
                                </div>
                            @endif
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ auth()->check() && auth()->user() ? (auth()->user()->name ?? 'User') : 'User' }}</span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header">Welcome {{ auth()->check() && auth()->user() ? (auth()->user()->name ?? 'User') : 'User' }}!</h6>
                        <a class="dropdown-item" href="{{ route('profile.show') }}"><i class="ri-user-line text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Profile</span></a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item btn-logout">
                                <i class="ri-logout-box-r-line text-muted fs-16 align-middle me-1"></i> <span class="align-middle" data-key="t-logout">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>