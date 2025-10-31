@extends('layouts.app')

@section('title', 'Add Trip | ' . config('app.name', 'Laravel'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Add New Trip</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trips</a></li>
                    <li class="breadcrumb-item active">Add New</li>
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
                <h5 class="card-title mb-0">Trip Information</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('trips.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="crew_name" class="form-label">Crew Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('crew_name') is-invalid @enderror" id="crew_name" name="crew_name" value="{{ old('crew_name') }}" placeholder="Enter crew name" required>
                                @error('crew_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trip_date" class="form-label">Trip Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('trip_date') is-invalid @enderror" id="trip_date" name="trip_date" value="{{ old('trip_date', date('Y-m-d')) }}" required>
                                @error('trip_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="driver_id" class="form-label">Driver <span class="text-danger">*</span></label>
                                <select class="form-select @error('driver_id') is-invalid @enderror" id="driver_id" name="driver_id" required>
                                    <option value="">Select Driver</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                                @error('driver_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vessel_id" class="form-label">Vessel <span class="text-danger">*</span></label>
                                <select class="form-select @error('vessel_id') is-invalid @enderror" id="vessel_id" name="vessel_id" required>
                                    <option value="">Select Vessel</option>
                                    @foreach($vessels as $vessel)
                                        <option value="{{ $vessel->id }}" {{ old('vessel_id') == $vessel->id ? 'selected' : '' }}>{{ $vessel->name }}</option>
                                    @endforeach
                                </select>
                                @error('vessel_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pick_up_time" class="form-label">Pick-up Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control @error('pick_up_time') is-invalid @enderror" id="pick_up_time" name="pick_up_time" value="{{ old('pick_up_time') }}" required>
                                @error('pick_up_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="from_location" class="form-label">From <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('from_location') is-invalid @enderror" id="from_location" name="from_location" value="{{ old('from_location') }}" placeholder="Enter pick-up location" required>
                                @error('from_location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="to_location" class="form-label">To <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('to_location') is-invalid @enderror" id="to_location" name="to_location" value="{{ old('to_location') }}" placeholder="Enter destination location" required>
                                @error('to_location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="follow_up_action" class="form-label">Follow Up / Confirm Action</label>
                        <textarea class="form-control @error('follow_up_action') is-invalid @enderror" id="follow_up_action" name="follow_up_action" rows="3" placeholder="Enter follow-up action or confirmation details">{{ old('follow_up_action') }}</textarea>
                        @error('follow_up_action')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-success" type="submit">Create Trip</button>
                        <a href="{{ route('trips.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

