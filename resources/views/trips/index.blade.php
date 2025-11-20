@extends('layouts.app')

@section('title', 'Trips | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Trips Management</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Trips</li>
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

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ri-error-warning-line me-2 align-middle"></i><strong>Error!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                                    <i class="ri-add-circle-line"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-1">Add New Trip</h5>
                                <p class="text-muted mb-0 small">Create a trip manually with full details</p>
                            </div>
                        </div>
                        <a href="{{ route('trips.create') }}" class="btn btn-primary">
                            <i class="ri-add-line me-1"></i> Create Trip
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                    <i class="ri-magic-line"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-1">Smart Extract (AI)</h5>
                                <p class="text-muted mb-0 small">Upload an image and auto-create trips</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#extractSection">
                            <i class="ri-upload-cloud-2-line me-1"></i> Upload Image
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Collapsible Image Upload Section -->
<div class="collapse mb-4" id="extractSection">
    <div class="card border shadow-sm">
        <div class="card-header bg-light border-bottom">
            <h5 class="card-title mb-0">
                <i class="ri-file-upload-line me-2 text-primary"></i>AI-Powered Trip Extraction
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-primary alert-border-left" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="ri-information-line fs-5 me-3"></i>
                            <div>
                                <h6 class="alert-heading mb-1">How it works</h6>
                                <p class="mb-0">Upload a screenshot or image containing a table with trip data. Our AI will automatically extract the information and create trips for you. Drivers and vessels will be created if they don't exist.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <form action="{{ route('trips.extract-from-image') }}" method="POST" enctype="multipart/form-data" id="extract-form">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="image" class="form-label fw-medium">
                            <i class="ri-image-line me-1 text-muted"></i>Upload Table Image
                        </label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/jpeg,image/jpg,image/png" required>
                        <div class="form-text">Formats: JPEG, JPG, PNG (Max: 10MB)</div>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        
                        <!-- Image Preview -->
                        <div id="imagePreview" class="mt-3 d-none">
                            <label class="form-label fw-medium text-muted small">Preview</label>
                            <img id="previewImg" src="" alt="Preview" class="img-thumbnail d-block" style="max-height: 180px;">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="trip_date" class="form-label fw-medium">
                            <i class="ri-calendar-line me-1 text-muted"></i>Trip Date (Optional)
                        </label>
                        <input type="date" class="form-control" id="trip_date" name="trip_date" value="{{ old('trip_date', date('Y-m-d')) }}">
                        <div class="form-text">Leave empty to auto-detect from image</div>
                        
                        <div class="mt-4 pt-2">
                            <button type="submit" class="btn btn-primary w-100" id="extract-btn">
                                <i class="ri-magic-line me-2"></i>Extract Trips Now
                            </button>
                        </div>
                        
                        <div class="mt-3 p-3 bg-light rounded">
                            <h6 class="fs-13 mb-2 text-muted">Expected Table Format:</h6>
                            <small class="text-muted d-block">
                                Crew Name | Driver Name | Vessel Name | Pick-up Time | From | To | Follow Up
                            </small>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Trips List -->
<div class="row">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-header bg-light border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">All Trips</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-soft-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#filterSection">
                            <i class="ri-filter-3-line me-1"></i> Filters
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">

                <!-- Collapsible Filter Section -->
                <div class="collapse show" id="filterSection">
                    <div class="row g-3 mb-4 p-3 bg-light rounded">
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label fw-semibold">
                                <i class="ri-user-line me-1"></i>Driver
                            </label>
                            <select id="filter-driver" class="form-select">
                                <option value="">All Drivers</option>
                                @foreach($drivers as $d)
                                    <option value="{{ $d->name }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label fw-semibold">
                                <i class="ri-ship-line me-1"></i>Vessel
                            </label>
                            <select id="filter-vessel" class="form-select">
                                <option value="">All Vessels</option>
                                @foreach($vessels as $v)
                                    <option value="{{ $v->name }}">{{ $v->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label fw-semibold">
                                <i class="ri-calendar-line me-1"></i>Date
                            </label>
                            <input type="date" id="filter-date" class="form-control">
                        </div>
                        <div class="col-sm-6 col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="filter-apply" class="btn btn-primary flex-fill">
                                <i class="ri-search-line me-1"></i> Apply
                            </button>
                            <button type="button" id="filter-reset" class="btn btn-soft-secondary">
                                <i class="ri-refresh-line"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="trips-table" class="table table-nowrap align-middle mb-0 @if(!$trips->isEmpty()) datatable @endif">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Crew Name</th>
                                <th scope="col">Driver Name</th>
                                <th scope="col">Vessel Name</th>
                                <th scope="col">Pick-up Time</th>
                                <th scope="col">From</th>
                                <th scope="col">To</th>
                                <th scope="col">Crew Phone</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($trips as $trip)
                                <tr>
                                    <td>{{ $trip->trip_date->format('M d, Y') }}</td>
                                    <td>{{ $trip->crew_name }}</td>
                                    <td>{{ $trip->driver->name }}</td>
                                    <td>{{ $trip->vessel->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($trip->pick_up_time)->format('h:i A') }}</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $trip->from_location }}">
                                            {{ $trip->from_location }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $trip->to_location }}">
                                            {{ $trip->to_location }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($trip->crew_phone)
                                            {{ $trip->crew_phone }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $trip->getStatusBadgeClass() }}">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('trips.edit', $trip) }}" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this trip?');">
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
                                        <p class="text-muted mb-0">No trips found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(!$trips->isEmpty())
                    @include('partials.datatable', ['selector' => '#trips-table'])
                @endif

                @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var driverSel = document.getElementById('filter-driver');
                        var vesselSel = document.getElementById('filter-vessel');
                        var dateInp = document.getElementById('filter-date');
                        var applyBtn = document.getElementById('filter-apply');
                        var resetBtn = document.getElementById('filter-reset');

                        // Set initial values from query parameters
                        var urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('driver')) {
                            driverSel.value = urlParams.get('driver');
                        }
                        if (urlParams.has('vessel')) {
                            vesselSel.value = urlParams.get('vessel');
                        }
                        if (urlParams.has('date')) {
                            dateInp.value = urlParams.get('date');
                        }

                        function applyFilters() {
                            var params = new URLSearchParams();
                            if (driverSel.value) {
                                params.set('driver', driverSel.value);
                            }
                            if (vesselSel.value) {
                                params.set('vessel', vesselSel.value);
                            }
                            if (dateInp.value) {
                                params.set('date', dateInp.value);
                            }
                            window.location.href = '{{ route('trips.index') }}?' + params.toString();
                        }

                        applyBtn.addEventListener('click', applyFilters);

                        resetBtn.addEventListener('click', function () {
                            window.location.href = '{{ route('trips.index') }}';
                        });

                        // Handle image extraction form submission
                        var extractForm = document.getElementById('extract-form');
                        var extractBtn = document.getElementById('extract-btn');
                        
                        if (extractForm) {
                            extractForm.addEventListener('submit', function(e) {
                                extractBtn.disabled = true;
                                extractBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
                            });
                        }

                        // Image preview
                        var imageInput = document.getElementById('image');
                        var imagePreview = document.getElementById('imagePreview');
                        var previewImg = document.getElementById('previewImg');
                        
                        if (imageInput) {
                            imageInput.addEventListener('change', function(e) {
                                var file = e.target.files[0];
                                if (file) {
                                    var reader = new FileReader();
                                    reader.onload = function(e) {
                                        previewImg.src = e.target.result;
                                        imagePreview.classList.remove('d-none');
                                    };
                                    reader.readAsDataURL(file);
                                }
                            });
                        }
                    });
                </script>
                @endpush
            </div>
        </div>
    </div>
</div>
@endsection

