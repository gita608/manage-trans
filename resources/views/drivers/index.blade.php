@extends('layouts.app')

@section('title', 'Drivers | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Drivers Management</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Drivers</li>
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
                                <i class="ri-user-add-line"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">Add New Driver</h5>
                            <p class="text-muted mb-0 small">Register a new driver to the system</p>
                        </div>
                    </div>
                    <a href="{{ route('drivers.create') }}" class="btn btn-primary">
                        <i class="ri-add-line me-1"></i> Add Driver
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Drivers List -->
<div class="row">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">All Drivers</h5>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table id="drivers-table" class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Photo</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Type</th>
                                <th scope="col">License Number</th>
                                <th scope="col">Contact</th>
                                <th scope="col">Age</th>
                                <th scope="col">Vehicle Info</th>
                                <th scope="col">Created At</th>
                                <th scope="col" class="no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($drivers as $driver)
                                <tr>
                                    <td>
                                        @if($driver->photo)
                                            <img src="{{ asset('storage/' . $driver->photo) }}" alt="{{ $driver->name }}" class="rounded-circle avatar-sm">
                                        @else
                                            <div class="avatar-sm rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center">
                                                <span class="text-primary">{{ substr($driver->name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $driver->name }}</td>
                                    <td>
                                        @if($driver->email)
                                            {{ $driver->email }}
                                            <span class="badge bg-success-subtle text-success ms-1" title="API Access Enabled">
                                                <i class="ri-smartphone-line"></i>
                                            </span>
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($driver->type == \App\Models\Driver::TYPE_INTERNAL)
                                            <span class="badge bg-info">Internal</span>
                                        @else
                                            <span class="badge bg-warning">Outsourcing</span>
                                        @endif
                                    </td>
                                    <td>{{ $driver->license_number ?? '-' }}</td>
                                    <td>{{ $driver->contact ?? '-' }}</td>
                                    <td>{{ $driver->age ?? '-' }}</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $driver->vehicle_info }}">
                                            {{ $driver->vehicle_info ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>{{ $driver->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $driver->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('drivers.show', $driver) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form action="{{ route('drivers.destroy', $driver) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this driver?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <p class="text-muted mb-0">No drivers found. <a href="{{ route('drivers.create') }}">Create your first driver</a></p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('partials.datatable', ['selector' => '#drivers-table', 'order' => [[8, 'desc']]])
            </div>
        </div>
    </div>
</div>
@endsection
