@extends('layouts.app')

@section('title')
    Notifications
@endsection

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Notifications</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Notifications</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-12">
            <div class="card border shadow-sm">
                <div class="card-header border-bottom d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1"><i class="ri-notification-3-line me-2"></i>All Notifications</h5>
                    @if($notifications->where('is_read', false)->count() > 0)
                        <form action="{{ route('notifications.mark-all-as-read') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-soft-primary">
                                <i class="ri-check-double-line me-1"></i> Mark All as Read
                            </button>
                        </form>
                    @endif
                </div>
                <div class="card-body">
                    @if($notifications->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($notifications as $notification)
                                <div class="list-group-item {{ !$notification->is_read ? 'list-group-item-action bg-soft-light' : '' }} border-0 py-3">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0 me-3">
                                            @if($notification->type === 'success')
                                                <div class="avatar-xs">
                                                    <div class="avatar-title bg-success-subtle text-success rounded-circle fs-16">
                                                        <i class="ri-check-line"></i>
                                                    </div>
                                                </div>
                                            @elseif($notification->type === 'warning')
                                                <div class="avatar-xs">
                                                    <div class="avatar-title bg-warning-subtle text-warning rounded-circle fs-16">
                                                        <i class="ri-alert-line"></i>
                                                    </div>
                                                </div>
                                            @elseif($notification->type === 'danger')
                                                <div class="avatar-xs">
                                                    <div class="avatar-title bg-danger-subtle text-danger rounded-circle fs-16">
                                                        <i class="ri-close-circle-line"></i>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="avatar-xs">
                                                    <div class="avatar-title bg-info-subtle text-info rounded-circle fs-16">
                                                        <i class="ri-information-line"></i>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $notification->title }}</h6>
                                            <p class="text-muted mb-2">{{ $notification->message }}</p>
                                            <small class="text-muted">
                                                <i class="ri-time-line align-middle"></i>
                                                {{ $notification->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        @if(!$notification->is_read)
                                            <div class="flex-shrink-0">
                                                <form action="{{ route('notifications.mark-as-read', $notification) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-soft-success" title="Mark as read">
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="avatar-lg mx-auto mb-4">
                                <div class="avatar-title bg-soft-primary text-primary fs-1 rounded-circle">
                                    <i class="ri-notification-off-line"></i>
                                </div>
                            </div>
                            <h5 class="text-muted">No Notifications</h5>
                            <p class="text-muted mb-0">You don't have any notifications yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection