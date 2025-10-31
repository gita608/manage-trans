@extends('layouts.app')

@section('title', 'Trip Details | ' . config('app.name', 'Laravel'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Trip Details</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trips</a></li>
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
                    <h5 class="card-title mb-0">Trip Information</h5>
                    <div>
                        <a href="{{ route('trips.edit', $trip) }}" class="btn btn-primary">
                            <i class="ri-pencil-line align-middle me-1"></i> Edit
                        </a>
                        <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this trip?');">
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
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th width="200">Trip Date:</th>
                            <td>{{ $trip->trip_date->format('l, F d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>Crew Name:</th>
                            <td>{{ $trip->crew_name }}</td>
                        </tr>
                        <tr>
                            <th>Driver Name:</th>
                            <td>{{ $trip->driver->name }}</td>
                        </tr>
                        <tr>
                            <th>Vessel Name:</th>
                            <td>{{ $trip->vessel->name }}</td>
                        </tr>
                        <tr>
                            <th>Pick-up Time:</th>
                            <td>{{ \Carbon\Carbon::parse($trip->pick_up_time)->format('h:i A') }}</td>
                        </tr>
                        <tr>
                            <th>From:</th>
                            <td>{{ $trip->from_location }}</td>
                        </tr>
                        <tr>
                            <th>To:</th>
                            <td>{{ $trip->to_location }}</td>
                        </tr>
                        <tr>
                            <th>Follow Up / Confirm Action:</th>
                            <td>{{ $trip->follow_up_action ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td>{{ $trip->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td>{{ $trip->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="{{ route('trips.index') }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line align-middle me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

