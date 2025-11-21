@extends('layouts.app')

@section('title', 'Staff | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Staff Management</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Staff</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<!-- Quick Action Card -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0 me-3">
                            <span class="avatar-title bg-warning-subtle text-warning rounded fs-3">
                                <i class="ri-user-add-line"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">Add New Staff</h5>
                            <p class="text-muted mb-0 small">Add a new staff member to the team</p>
                        </div>
                    </div>
                    <a href="{{ route('staff.create') }}" class="btn btn-primary">
                        <i class="ri-add-line me-1"></i> Add Staff
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Staff List -->
<div class="card border shadow-sm">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">All Staff Members</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="staff-table" class="table align-middle datatable">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Photo</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>
                                @if($user->photo)
                                    <img src="{{ asset('storage/' . $user->photo) }}" alt="photo" class="rounded" style="width:40px;height:40px;object-fit:cover;">
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('staff.edit', $user) }}" class="btn btn-sm btn-primary"><i class="ri-pencil-line"></i></a>
                                <form method="POST" action="{{ route('staff.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Delete this staff?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No staff found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.datatable', ['selector' => '#staff-table'])
    </div>
</div>
@endsection


