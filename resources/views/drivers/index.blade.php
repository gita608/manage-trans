@extends('layouts.app')

@section('title', 'Drivers | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Drivers</h4>

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

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">All Drivers</h5>
                    <a href="{{ route('drivers.create') }}" class="btn btn-success">
                        <i class="ri-add-line align-middle me-1"></i> Add New Driver
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Photo</th>
                                <th scope="col">Name</th>
                                <th scope="col">License Number</th>
                                <th scope="col">Contact</th>
                                <th scope="col">Age</th>
                                <th scope="col">Vehicle Info</th>
                                <th scope="col">Actions</th>
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
                                    <td>{{ $driver->license_number }}</td>
                                    <td>{{ $driver->contact }}</td>
                                    <td>{{ $driver->age }}</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $driver->vehicle_info }}">
                                            {{ $driver->vehicle_info }}
                                        </span>
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
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-muted mb-0">No drivers found. <a href="{{ route('drivers.create') }}">Create your first driver</a></p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($drivers->hasPages())
                    <div class="d-flex justify-content-end mt-3">
                        {{ $drivers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
