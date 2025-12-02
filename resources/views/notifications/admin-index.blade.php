@extends('layouts.app')

@section('title', 'Driver Notifications | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Driver Notifications</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Driver Notifications</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ri-check-double-line me-2 align-middle"></i><strong>Success!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Quick Action Card -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0 me-3">
                            <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                                <i class="ri-notification-3-line"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">Send New Notification</h5>
                            <p class="text-muted mb-0 small">Send a notification to a driver or all drivers</p>
                        </div>
                    </div>
                    <a href="{{ route('notifications.create') }}" class="btn btn-primary">
                        <i class="ri-add-line me-1"></i> Send Notification
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notifications List -->
<div class="row">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0"><i class="ri-notification-3-line me-2"></i>All Driver Notifications</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="notifications-table" class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Title</th>
                                <th scope="col">Message</th>
                                <th scope="col">Driver</th>
                                <th scope="col">Sent By</th>
                                <th scope="col">Status</th>
                                <th scope="col">Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($notifications as $notification)
                                <tr>
                                    <td>
                                        <strong>{{ $notification->title }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 300px;" title="{{ $notification->message }}">
                                            {{ $notification->message }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($notification->driver)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    @php
                                                        $driverPhotoExists = $notification->driver->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($notification->driver->photo);
                                                    @endphp
                                                    @if($driverPhotoExists)
                                                        <img src="{{ asset('storage/' . $notification->driver->photo) }}" alt="{{ $notification->driver->name }}" class="rounded-circle w-100 h-100" style="object-fit: cover;" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'avatar-title bg-primary-subtle text-primary rounded-circle\'>{{ substr($notification->driver->name, 0, 1) }}</div>';">
                                                    @else
                                                        <div class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                                            {{ substr($notification->driver->name, 0, 1) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-medium">{{ $notification->driver->name }}</div>
                                                    @if($notification->driver->email)
                                                        <small class="text-muted">{{ $notification->driver->email }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($notification->user)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    @php
                                                        $userPhotoExists = $notification->user->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($notification->user->photo);
                                                    @endphp
                                                    @if($userPhotoExists)
                                                        <img src="{{ asset('storage/' . $notification->user->photo) }}" alt="{{ $notification->user->name }}" class="rounded-circle w-100 h-100" style="object-fit: cover;" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'avatar-title bg-secondary-subtle text-secondary rounded-circle\'>{{ substr($notification->user->name, 0, 1) }}</div>';">
                                                    @else
                                                        <div class="avatar-title bg-secondary-subtle text-secondary rounded-circle">
                                                            {{ substr($notification->user->name, 0, 1) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-medium">{{ $notification->user->name }}</div>
                                                    <small class="text-muted">{{ $notification->user->email }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($notification->is_read)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="ri-check-line me-1"></i> Read
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="ri-time-line me-1"></i> Unread
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $notification->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $notification->created_at->format('h:i A') }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="avatar-lg mx-auto mb-3">
                                            <div class="avatar-title bg-soft-primary text-primary fs-1 rounded-circle">
                                                <i class="ri-notification-off-line"></i>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-0">No notifications found. <a href="{{ route('notifications.create') }}">Send your first notification</a></p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($notifications->hasPages())
                    <div class="mt-3">
                        {{ $notifications->links() }}
                    </div>
                @endif

                @include('partials.datatable', ['selector' => '#notifications-table'])
            </div>
        </div>
    </div>
</div>
@endsection

