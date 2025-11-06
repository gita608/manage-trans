@extends('layouts.app')

@section('title', 'User Permissions | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Manage Permissions for {{ $user->name }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-all me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        User Information
                    </h5>
                    <a href="{{ route('permissions.index') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Back to Permissions
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> {{ $user->name }}</p>
                        <p><strong>Email:</strong> {{ $user->email }}</p>
                        <p><strong>Role:</strong> 
                            @if($user->role == \App\Models\User::ROLE_ADMIN)
                                <span class="badge bg-danger">Admin</span>
                            @else
                                <span class="badge bg-info">Staff</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i>
                            <strong>Note:</strong> User-specific permissions override role-based permissions. 
                            @if($user->role == \App\Models\User::ROLE_ADMIN)
                                <br><small>Admin users have all permissions by default.</small>
                            @endif
                        </div>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">User-Specific Permissions</h5>
                <p class="text-muted mb-4">
                    Select permissions to grant or revoke for this user. These settings will override the role-based permissions.
                </p>

                <form method="POST" action="{{ route('permissions.updateUser') }}">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    
                    @foreach($allPermissions as $category => $categoryPermissions)
                        <div class="mb-4">
                            <h6 class="text-uppercase text-muted mb-3">{{ ucfirst($category) }}</h6>
                            <div class="list-group">
                                @foreach($categoryPermissions as $permission)
                                    @php
                                        $userPerm = $userSpecificPermissions->firstWhere('permission_id', $permission->id);
                                        $hasRolePerm = in_array($permission->id, $rolePermissions);
                                        $hasUserOverride = $userPerm !== null;
                                        $isCurrentlyGranted = $hasUserOverride ? $userPerm->granted : ($hasRolePerm ? true : false);
                                    @endphp
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="permissions[]" 
                                                       value="{{ $permission->id }}" 
                                                       id="user_perm_{{ $permission->id }}"
                                                       {{ $hasUserOverride ? 'checked' : '' }}>
                                                <label class="form-check-label" for="user_perm_{{ $permission->id }}">
                                                    <strong>{{ $permission->display_name }}</strong>
                                                    @if($permission->description)
                                                        <br><small class="text-muted">{{ $permission->description }}</small>
                                                    @endif
                                                    @if(!$hasUserOverride && $hasRolePerm)
                                                        <br><small class="text-success"><i class="ri-check-line"></i> Currently granted from role</small>
                                                    @endif
                                                </label>
                                            </div>
                                            <div>
                                                @if($hasUserOverride)
                                                    <select name="permission_status[{{ $permission->id }}]" 
                                                            class="form-select form-select-sm" 
                                                            style="width: 120px;">
                                                        <option value="granted" {{ $userPerm->granted ? 'selected' : '' }}>Granted</option>
                                                        <option value="revoked" {{ !$userPerm->granted ? 'selected' : '' }}>Revoked</option>
                                                    </select>
                                                @else
                                                    <span class="badge bg-secondary">From Role</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Save User Permissions
                        </button>
                        <a href="{{ route('permissions.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

