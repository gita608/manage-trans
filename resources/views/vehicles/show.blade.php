@extends('layouts.app')

@section('title', 'Vehicle Details | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Vehicle Details</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicles</a></li>
                    <li class="breadcrumb-item active">Details</li>
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
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="card-title mb-0">Vehicle Information</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-primary">
                            <i class="ri-pencil-line align-middle me-1"></i> Edit
                        </a>
                        <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this vehicle?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="ri-delete-bin-line align-middle me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-borderless table-details">
                    <tbody>
                        <tr>
                            <th>ID:</th>
                            <td>{{ $vehicle->id }}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $vehicle->name }}</td>
                        </tr>
                        <tr>
                            <th>Number:</th>
                            <td>{{ $vehicle->number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Brand:</th>
                            <td>{{ $vehicle->brand ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Information:</th>
                            <td>{{ $vehicle->info ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td>{{ $vehicle->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td>{{ $vehicle->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line align-middle me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
