@extends('layouts.app')

@section('title', 'Edit Driver | ' . config('app.name'))

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
                                <label for="type" class="form-label">Driver Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    @foreach(\App\Models\Driver::getTypes() as $key => $label)
                                        <option value="{{ $key }}" {{ old('type', $driver->type ?? 1) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $driver->email) }}" placeholder="Enter email address">
                                <small class="text-muted">Optional - Required for driver to login via mobile app</small>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter new password">
                                <small class="text-muted">Leave blank to keep current password</small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="license_number" class="form-label">License Number</label>
                                <input type="text" class="form-control @error('license_number') is-invalid @enderror" id="license_number" name="license_number" value="{{ old('license_number', $driver->license_number) }}" placeholder="Enter license number">
                                @error('license_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact" class="form-label">Contact</label>
                                <input type="text" class="form-control @error('contact') is-invalid @enderror" id="contact" name="contact" value="{{ old('contact', $driver->contact) }}" placeholder="Enter contact number">
                                @error('contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="age" class="form-label">Age</label>
                                <input type="number" class="form-control @error('age') is-invalid @enderror" id="age" name="age" value="{{ old('age', $driver->age) }}" placeholder="Enter age" min="18" max="100">
                                @error('age')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vehicle_name" class="form-label">Vehicle Name</label>
                                <input type="text" class="form-control @error('vehicle_name') is-invalid @enderror" id="vehicle_name" name="vehicle_name" value="{{ old('vehicle_name', $driver->vehicle_name) }}" placeholder="Enter vehicle name">
                                @error('vehicle_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vehicle_brand" class="form-label">Vehicle Brand</label>
                                <input type="text" class="form-control @error('vehicle_brand') is-invalid @enderror" id="vehicle_brand" name="vehicle_brand" value="{{ old('vehicle_brand', $driver->vehicle_brand) }}" placeholder="Enter vehicle brand">
                                @error('vehicle_brand')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="vehicle_info" class="form-label">Vehicle Information</label>
                        <textarea class="form-control @error('vehicle_info') is-invalid @enderror" id="vehicle_info" name="vehicle_info" rows="3" placeholder="Enter vehicle information">{{ old('vehicle_info', $driver->vehicle_info) }}</textarea>
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
                    <div class="mb-3">
                        <label for="documents" class="form-label">Documents</label>
                        
                        <!-- Existing Documents -->
                        @if($driver->documents->count() > 0)
                            <div class="row mb-3">
                                @foreach($driver->documents as $document)
                                    <div class="col-md-4 mb-2">
                                        <div class="card border shadow-none mb-0">
                                            <div class="card-body p-2 d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center overflow-hidden">
                                                    <div class="flex-shrink-0 me-2">
                                                        @if(str_starts_with($document->mime_type, 'image/'))
                                                            <img src="{{ asset('storage/' . $document->file_path) }}" alt="{{ $document->original_name }}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                                        @else
                                                            <i class="ri-file-pdf-line fs-20 text-danger"></i>
                                                        @endif
                                                    </div>
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <h6 class="fs-13 mb-0 text-truncate" title="{{ $document->original_name }}">
                                                            <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="text-body">{{ $document->original_name }}</a>
                                                        </h6>
                                                        <small class="text-muted">{{ round($document->file_size / 1024, 2) }} KB</small>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-ghost-danger btn-sm btn-icon" onclick="if(confirm('Delete this document?')) document.getElementById('delete-doc-{{ $document->id }}').submit();">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Upload New Documents -->
                        <input type="file" class="form-control @error('documents') is-invalid @enderror" id="documents" name="documents[]" multiple accept="image/*,application/pdf">
                        <small class="text-muted">Allowed types: Images, PDF. Max size: 5MB</small>
                        @error('documents')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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

@foreach($driver->documents as $document)
    <form id="delete-doc-{{ $document->id }}" action="{{ route('drivers.delete-document', $document->id) }}" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endforeach

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
