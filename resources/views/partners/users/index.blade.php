@extends('layouts.app')

@section('title', 'Partner Users | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Partner Users - {{ $partner->title }}</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('partners.index') }}">Partners</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<div class="row">
    <div class="col-lg-12">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="card-title mb-0">Partner Login Users</h5>
                @if(auth()->user()->hasPermission('edit_partners'))
                    <a href="{{ route('partners.users.create', $partner) }}" class="btn btn-success btn-sm">
                        <i class="ri-add-line align-middle me-1"></i> Add User
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if($partnerUsers->count() > 0)
                    <div class="table-responsive" style="overflow: visible;">
                        <table class="table table-hover table-nowrap align-middle" style="margin-bottom: 100px;">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th class="text-end" style="min-width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($partnerUsers as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->phone ?? 'N/A' }}</td>
                                        <td>
                                            @if($user->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->last_login_at)
                                                {{ $user->last_login_at->format('M d, Y g:i A') }}
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
                                        </td>
                                        <td class="text-end" style="position: relative; min-width: 100px;">
                                            @if(auth()->user()->hasPermission('edit_partners'))
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-2-fill"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" style="z-index: 1050;">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('partners.users.edit', [$partner, $user]) }}">
                                                                <i class="ri-pencil-line align-middle me-1"></i> Edit
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#passwordModal{{ $user->id }}">
                                                                <i class="ri-lock-password-line align-middle me-1"></i> Reset Password
                                                            </button>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="POST" action="{{ route('partners.users.toggleStatus', [$partner, $user]) }}" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="dropdown-item" onclick="return confirm('Are you sure you want to {{ $user->is_active ? 'deactivate' : 'activate' }} this user?')">
                                                                    @if($user->is_active)
                                                                        <i class="ri-close-circle-line align-middle me-1"></i> Deactivate
                                                                    @else
                                                                        <i class="ri-checkbox-circle-line align-middle me-1"></i> Activate
                                                                    @endif
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- Password Reset Modal -->
                                    <div class="modal fade" id="passwordModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('partners.users.updatePassword', [$partner, $user]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Reset Password - {{ $user->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="password{{ $user->id }}" class="form-label">New Password <span class="text-danger">*</span></label>
                                                            <input type="password" class="form-control" id="password{{ $user->id }}" name="password" required minlength="8">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="password_confirmation{{ $user->id }}" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                                            <input type="password" class="form-control" id="password_confirmation{{ $user->id }}" name="password_confirmation" required minlength="8">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Reset Password</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="ri-user-line fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No partner users found.</p>
                        @if(auth()->user()->hasPermission('edit_partners'))
                            <a href="{{ route('partners.users.create', $partner) }}" class="btn btn-success btn-sm">
                                <i class="ri-add-line align-middle me-1"></i> Add First User
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
