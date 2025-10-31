@extends('layouts.app')

@section('title', 'Edit Driver | ' . config('app.name', 'Laravel'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Edit Driver</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('drivers.index') }}">Drivers</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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
                <h5 class="card-title mb-0">Driver Information</h5>
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

                <form method="POST" action="{{ route('drivers.update', $driver) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $driver->name) }}" placeholder="Enter driver name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="license_number" class="form-label">License Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('license_number') is-invalid @enderror" id="license_number" name="license_number" value="{{ old('license_number', $driver->license_number) }}" placeholder="Enter license number" required>
                                @error('license_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact" class="form-label">Contact <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('contact') is-invalid @enderror" id="contact" name="contact" value="{{ old('contact', $driver->contact) }}" placeholder="Enter contact number" required>
                                @error('contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="age" class="form-label">Age <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('age') is-invalid @enderror" id="age" name="age" value="{{ old('age', $driver->age) }}" placeholder="Enter age" min="18" max="100" required>
                                @error('age')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="vehicle_info" class="form-label">Vehicle Information <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('vehicle_info') is-invalid @enderror" id="vehicle_info" name="vehicle_info" rows="3" placeholder="Enter vehicle information" required>{{ old('vehicle_info', $driver->vehicle_info) }}</textarea>
                        @error('vehicle_info')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="photo" class="form-label">Photo</label>
                        @if($driver->photo)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $driver->photo) }}" alt="Current photo" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                                <p class="text-muted mt-2">Current photo</p>
                            </div>
                        @endif
                        <input type="file" class="form-control @error('photo') is-invalid @enderror" id="photo" name="photo" accept="image/*">
                        <small class="text-muted">Max file size: 2MB. Allowed types: JPEG, PNG, JPG, GIF. Leave blank to keep current photo.</small>
                        @error('photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="mt-2">
                            <img id="photo-preview" src="#" alt="Photo preview" style="display: none; max-width: 200px; max-height: 200px; border-radius: 8px;">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-success" type="submit">Update Driver</button>
                        <a href="{{ route('drivers.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Photo preview
    document.getElementById('photo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('photo-preview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endpush
