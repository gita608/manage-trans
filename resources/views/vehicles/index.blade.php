@extends('layouts.app')

@section('title', 'Vehicles | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Vehicles Management</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vehicles</li>
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
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0 me-3">
                            <span class="avatar-title bg-info-subtle text-info rounded fs-3">
                                <i class="ri-car-line"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">Add New Vehicle</h5>
                            <p class="text-muted mb-0 small">Register a new vehicle to the fleet</p>
                        </div>
                    </div>
                    <a href="{{ route('vehicles.create') }}" class="btn btn-primary">
                        <i class="ri-add-line me-1"></i> Add Vehicle
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vehicles List -->
<div class="row">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">All Vehicles</h5>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table id="vehicles-table" class="table table-nowrap align-middle mb-0 datatable">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Number</th>
                                <th scope="col">Brand</th>
                                <th scope="col">Created At</th>
                                <th scope="col" class="no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vehicles as $vehicle)
                                <tr>
                                    <td>{{ $vehicle->name }}</td>
                                    <td>{{ $vehicle->number ?? 'N/A' }}</td>
                                    <td>{{ $vehicle->brand ?? 'N/A' }}</td>
                                    <td>
                                        <div>{{ $vehicle->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $vehicle->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vehicle?');">
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
                                    <td colspan="5" class="text-center py-4">
                                        <p class="text-muted mb-0">No vehicles found. <a href="{{ route('vehicles.create') }}">Create your first vehicle</a></p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('partials.datatable', ['selector' => '#vehicles-table', 'order' => []])
            </div>
        </div>
    </div>
</div>
@endsection
