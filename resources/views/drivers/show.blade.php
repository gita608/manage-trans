@extends('layouts.app')

@section('title', 'Driver Details | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Driver Details</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('drivers.index') }}">Drivers</a></li>
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
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Driver Information</h5>
                    <div>
                        <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-primary">
                            <i class="ri-pencil-line align-middle me-1"></i> Edit
                        </a>
                        <form action="{{ route('drivers.destroy', $driver) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this driver?');">
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
                <div class="row">
                    <div class="col-md-4 text-center mb-4">
                        @if($driver->photo)
                            <img src="{{ asset('storage/' . $driver->photo) }}" alt="{{ $driver->name }}" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                        @else
                            <div class="bg-primary-subtle d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 300px; height: 300px;">
                                <span class="text-primary display-1">{{ substr($driver->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th width="200">Name:</th>
                                    <td>{{ $driver->name }}</td>
                                </tr>
                                <tr>
                                    <th>License Number:</th>
                                    <td>{{ $driver->license_number }}</td>
                                </tr>
                                <tr>
                                    <th>Contact:</th>
                                    <td>{{ $driver->contact }}</td>
                                </tr>
                                <tr>
                                    <th>Age:</th>
                                    <td>{{ $driver->age }} years</td>
                                </tr>
                                <tr>
                                    <th>Vehicle Information:</th>
                                    <td>{{ $driver->vehicle_info }}</td>
                                </tr>
                                <tr>
                                    <th>Created At:</th>
                                    <td>{{ $driver->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated:</th>
                                    <td>{{ $driver->updated_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('drivers.index') }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line align-middle me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
