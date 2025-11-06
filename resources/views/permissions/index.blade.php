@extends('layouts.app')

@section('title', 'Permissions Management | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Permissions Management</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Permissions</li>
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
                <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#role-permissions" role="tab">
                            <span class="d-block d-sm-none"><i class="mdi mdi-shield-account"></i></span>
                            <span class="d-none d-sm-block">Role-Based Permissions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#user-permissions" role="tab">
                            <span class="d-block d-sm-none"><i class="mdi mdi-account"></i></span>
                            <span class="d-none d-sm-block">User-Specific Permissions</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Role-Based Permissions Tab -->
                    <div class="tab-pane fade show active" id="role-permissions" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Admin Role Permissions</h5>
                                <form method="POST" action="{{ route('permissions.updateRole') }}">
                                    @csrf
                                    <input type="hidden" name="role" value="1">
                                    
                                    @foreach($permissions as $category => $categoryPermissions)
                                        <div class="mb-4">
                                            <h6 class="text-uppercase text-muted mb-3">{{ ucfirst($category) }}</h6>
                                            <div class="list-group">
                                                @foreach($categoryPermissions as $permission)
                                                    <div class="list-group-item">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="permissions[]" 
                                                                   value="{{ $permission->id }}" 
                                                                   id="admin_perm_{{ $permission->id }}"
                                                                   {{ in_array($permission->id, $adminPermissions) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="admin_perm_{{ $permission->id }}">
                                                                <strong>{{ $permission->display_name }}</strong>
                                                                @if($permission->description)
                                                                    <br><small class="text-muted">{{ $permission->description }}</small>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-save-line me-1"></i> Update Admin Permissions
                                    </button>
                                </form>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-3">Staff Role Permissions</h5>
                                <form method="POST" action="{{ route('permissions.updateRole') }}">
                                    @csrf
                                    <input type="hidden" name="role" value="2">
                                    
                                    @foreach($permissions as $category => $categoryPermissions)
                                        <div class="mb-4">
                                            <h6 class="text-uppercase text-muted mb-3">{{ ucfirst($category) }}</h6>
                                            <div class="list-group">
                                                @foreach($categoryPermissions as $permission)
                                                    <div class="list-group-item">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="permissions[]" 
                                                                   value="{{ $permission->id }}" 
                                                                   id="staff_perm_{{ $permission->id }}"
                                                                   {{ in_array($permission->id, $staffPermissions) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="staff_perm_{{ $permission->id }}">
                                                                <strong>{{ $permission->display_name }}</strong>
                                                                @if($permission->description)
                                                                    <br><small class="text-muted">{{ $permission->description }}</small>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-save-line me-1"></i> Update Staff Permissions
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User-Specific Permissions Tab -->
                    <div class="tab-pane fade" id="user-permissions" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Permissions</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                @if($user->role == \App\Models\User::ROLE_ADMIN)
                                                    <span class="badge bg-danger">Admin</span>
                                                @else
                                                    <span class="badge bg-info">Staff</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $userPerms = $user->getAllPermissions();
                                                    $count = $userPerms->count();
                                                @endphp
                                                <span class="badge bg-primary">{{ $count }} permission{{ $count != 1 ? 's' : '' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('permissions.user', $user) }}" class="btn btn-sm btn-primary">
                                                    <i class="ri-edit-line me-1"></i> Manage Permissions
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

