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

<!-- Statistics Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-2">Today's Trips</p>
                        <h4 class="mb-0">{{ $stats['total_trips'] }}</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                                <i class="ri-calendar-check-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-2">Total Crew</p>
                        <h4 class="mb-0">{{ $stats['total_jobs'] }}</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-info-subtle text-info rounded fs-3">
                                <i class="ri-briefcase-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-2">In Progress</p>
                        <h4 class="mb-0">{{ $stats['trips_in_progress'] }}</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-warning-subtle text-warning rounded fs-3">
                                <i class="ri-time-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-2">Completed Trips</p>
                        <h4 class="mb-0">{{ $stats['trips_completed'] }}</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                <i class="ri-checkbox-circle-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
        <div class="card-header border-bottom">
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
                        
                        <label for="partner_id" class="form-label fw-medium mt-3">
                            <i class="ri-group-line me-1 text-muted"></i>Partner
                        </label>
                        <select class="form-select @error('partner_id') is-invalid @enderror" id="partner_id" name="partner_id">
                            <option value="">Select Partner</option>
                            @php
                                $partners = \App\Models\Partner::orderBy('is_default', 'desc')->orderBy('title')->get();
                                $defaultPartner = \App\Models\Partner::where('is_default', true)->first();
                            @endphp
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ old('partner_id', $defaultPartner->id ?? '') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->title }}
                                    @if($partner->is_default)
                                        (Default)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Default: {{ $defaultPartner->title ?? 'None' }}</div>
                        
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
        <div class="card border-0 shadow-sm trips-list-card">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 fw-600">Today's Trips</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#filterSection">
                        <i class="ri-filter-3-line me-1"></i> Filters
                    </button>
                </div>
            </div>
            <div class="card-body py-4">

                <div class="collapse show" id="filterSection">
                    <div class="row g-3 mb-4 p-4 filter-bar">
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
                        <div class="col-sm-6 col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="ri-checkbox-circle-line me-1"></i>Status
                            </label>
                            <select id="filter-status" class="form-select">
                                <option value="">All Status</option>
                                <option value="unassigned">Unassigned</option>
                                <option value="assigned">Assigned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="ri-calendar-line me-1"></i>Date Range
                            </label>
                            <select id="filter-date-range" class="form-select">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="tomorrow">Tomorrow</option>
                                <option value="last_7_days">Last 7 Days</option>
                                <option value="this_month">This Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-2 d-flex align-items-end gap-2">
                            <button type="button" id="filter-apply" class="btn btn-primary flex-fill">
                                <i class="ri-search-line me-1"></i> Apply
                            </button>
                            <button type="button" id="filter-reset" class="btn btn-soft-secondary">
                                <i class="ri-refresh-line"></i>
                            </button>
                        </div>
                        <div class="col-12" id="custom-date-row" style="display: none;">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label small text-muted">From</label>
                                    <input type="date" id="filter-date-from" class="form-control">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small text-muted">To</label>
                                    <input type="date" id="filter-date-to" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @forelse($trips as $trip)
                    @php
                        $totalJobs = $trip->tripStatus['totalJobs'];
                        $statusBadge = $trip->tripStatus['statusBadge'];
                        $statusText = $trip->tripStatus['statusText'];
                    @endphp
                    <div class="trip-card mb-4 trip-status-{{ $statusBadge }}">
                        <div class="trip-card-header">
                            <div class="trip-card-main">
                                <div class="trip-card-title">
                                    <span class="trip-number">#{{ $loop->iteration }}</span>
                                    <h5 class="trip-name mb-0">{{ $trip->title ?? 'Untitled Trip' }}</h5>
                                    <span class="status-pill status-{{ $statusBadge }}">{{ $statusText }}</span>
                                </div>
                                <div class="trip-card-meta">
                                    <span class="meta-pill">
                                        <i class="ri-calendar-line"></i>
                                        {{ $trip->trip_date instanceof \Carbon\Carbon ? $trip->trip_date->format('M d, Y') : \Carbon\Carbon::parse($trip->trip_date)->format('M d, Y') }}
                                    </span>
                                    <span class="meta-pill" id="trip-{{ $trip->id }}-driver">
                                        @if($trip->driver)
                                            <i class="ri-user-line"></i>{{ $trip->driver->name }}
                                        @else
                                            <span class="assign-driver-inline" data-trip-id="{{ $trip->id }}">
                                                <select class="form-select form-select-sm assign-driver-select">
                                                    <option value="">Select driver</option>
                                                    @foreach($drivers as $d)
                                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="btn btn-sm btn-primary assign-driver-btn">Assign</button>
                                            </span>
                                        @endif
                                    </span>
                                    <span class="meta-pill meta-crew">
                                        <i class="ri-group-line"></i>
                                        {{ $totalJobs }} crew{{ $totalJobs !== 1 ? 's' : '' }}
                                    </span>
                                </div>
                            </div>
                            <div class="trip-card-actions">
                                <a href="{{ route('trips.show', $trip->id) }}" class="btn-action btn-action-view" data-bs-toggle="tooltip" title="View Details">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('trips.edit', $trip->id) }}" class="btn-action btn-action-edit" data-bs-toggle="tooltip" title="Edit Trip">
                                    <i class="ri-pencil-line"></i>
                                </a>
                                <form action="{{ route('trips.destroy', $trip->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this trip?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-action-delete" data-bs-toggle="tooltip" title="Delete Trip">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="trip-card-crews">
                            <div class="crews-section-header">
                                <span class="crews-label"><i class="ri-user-3-line"></i> Crew Members</span>
                            </div>
                            @if($trip->crews->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm trip-crews-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Crew Name</th>
                                                <th>Contact</th>
                                                <th>Vessel</th>
                                                <th>Pick-up</th>
                                                <th>Flight</th>
                                                <th>Route</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($trip->crews as $crewIndex => $crew)
                                                <tr>
                                                    <td class="crew-index">{{ $crewIndex + 1 }}</td>
                                                    <td class="crew-name">
                                                        <span class="crew-avatar">{{ strtoupper(substr($crew->name, 0, 2)) }}</span>
                                                        {{ $crew->name }}
                                                    </td>
                                                    <td>
                                                        @if($crew->phone)
                                                            <a href="tel:{{ $crew->phone }}" class="text-body">{{ $crew->phone }}</a>
                                                            @if($crew->phone_2)<br><small class="text-muted">{{ $crew->phone_2 }}</small>@endif
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $crew->vessel->name ?? '—' }}</td>
                                                    <td>{{ $crew->pick_up_time ? \Carbon\Carbon::parse($crew->pick_up_time)->format('h:i A') : '—' }}</td>
                                                    <td>{{ $crew->flight_number ?? '—' }}</td>
                                                    <td class="route-cell">
                                                        <span class="route-from">{{ $crew->from_location }}</span>
                                                        <i class="ri-arrow-right-line route-arrow"></i>
                                                        <span class="route-to">{{ $crew->to_location }}</span>
                                                    </td>
                                                    <td>
                                                        @if($crew->remarks)
                                                            <span class="badge bg-light text-dark me-1" title="Remarks: {{ $crew->remarks }}"><i class="ri-chat-1-line me-1"></i>{{ \Illuminate\Support\Str::limit($crew->remarks, 15) }}</span>
                                                        @endif
                                                        @if($crew->sub_remark)
                                                            <span class="badge bg-light text-info" title="Sub Remark: {{ $crew->sub_remark }}"><i class="ri-chat-3-line me-1"></i>{{ \Illuminate\Support\Str::limit($crew->sub_remark, 15) }}</span>
                                                        @endif
                                                        @if(!$crew->remarks && !$crew->sub_remark)
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="crews-empty">
                                    <i class="ri-user-follow-line"></i>
                                    <span>No crew assigned to this trip</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="trip-empty-state">
                        <div class="trip-empty-icon">
                            <i class="ri-calendar-todo-line"></i>
                        </div>
                        <h5 class="mb-2">No trips found</h5>
                        <p class="text-muted mb-0">Try adjusting your filters or create a new trip.</p>
                    </div>
                @endforelse



                @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var driverSel = document.getElementById('filter-driver');
                        var vesselSel = document.getElementById('filter-vessel');
                        var statusSel = document.getElementById('filter-status');
                        var dateRangeSel = document.getElementById('filter-date-range');
                        var dateFromInp = document.getElementById('filter-date-from');
                        var dateToInp = document.getElementById('filter-date-to');
                        var customDateRow = document.getElementById('custom-date-row');
                        var applyBtn = document.getElementById('filter-apply');
                        var resetBtn = document.getElementById('filter-reset');

                        // Set initial values from query parameters
                        var urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('driver')) driverSel.value = urlParams.get('driver');
                        if (urlParams.has('vessel')) vesselSel.value = urlParams.get('vessel');
                        if (urlParams.has('status')) statusSel.value = urlParams.get('status');
                        
                        if (urlParams.has('date_range')) {
                            dateRangeSel.value = urlParams.get('date_range');
                            if (dateRangeSel.value === 'custom') {
                                customDateRow.style.display = 'block';
                            }
                        }
                        
                        if (urlParams.has('date_from')) dateFromInp.value = urlParams.get('date_from');
                        if (urlParams.has('date_to')) dateToInp.value = urlParams.get('date_to');

                        // Toggle custom date row
                        dateRangeSel.addEventListener('change', function() {
                            if (this.value === 'custom') {
                                customDateRow.style.display = 'block';
                            } else {
                                customDateRow.style.display = 'none';
                            }
                        });

                        function applyFilters() {
                            var params = new URLSearchParams();
                            if (driverSel.value) params.set('driver', driverSel.value);
                            if (vesselSel.value) params.set('vessel', vesselSel.value);
                            if (statusSel.value) params.set('status', statusSel.value);
                            
                            if (dateRangeSel.value) {
                                params.set('date_range', dateRangeSel.value);
                                if (dateRangeSel.value === 'custom') {
                                    if (dateFromInp.value) params.set('date_from', dateFromInp.value);
                                    if (dateToInp.value) params.set('date_to', dateToInp.value);
                                }
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

                        document.addEventListener('click', function(e) {
                            var btn = e.target.closest('.assign-driver-btn');
                            if (!btn) return;
                            var block = btn.closest('.assign-driver-inline');
                            var tripId = block ? block.getAttribute('data-trip-id') : null;
                            var select = block ? block.querySelector('.assign-driver-select') : null;
                            if (!tripId || !select || !select.value) {
                                if (select && !select.value) alert('Please select a driver.');
                                return;
                            }
                            btn.disabled = true;
                            btn.textContent = '...';
                            fetch('{{ url("/trips") }}/' + tripId + '/assign-driver', {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({ driver_id: parseInt(select.value) })
                            })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (data.success) {
                                    var cell = document.getElementById('trip-' + tripId + '-driver');
                                    if (cell) {
                                        cell.innerHTML = '<i class="ri-user-line"></i>' + data.driver_name;
                                        var card = cell.closest('.trip-card');
                                        if (card) {
                                            card.classList.remove('trip-status-secondary');
                                            card.classList.add('trip-status-warning');
                                            var statusPill = card.querySelector('.status-pill');
                                            if (statusPill) {
                                                statusPill.textContent = 'Assigned';
                                                statusPill.className = 'status-pill status-warning';
                                            }
                                        }
                                    }
                                } else {
                                    alert(data.message || 'Failed to assign driver.');
                                    btn.disabled = false;
                                    btn.textContent = 'Assign';
                                }
                            })
                            .catch(function() {
                                alert('Failed to assign driver.');
                                btn.disabled = false;
                                btn.textContent = 'Assign';
                            });
                        });

                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                    });
                </script>
                @endpush
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .trip-card {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .trip-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.06), 0 2px 6px rgba(0,0,0,0.04);
    }
    .trip-card.trip-status-warning { border-left: 4px solid #f59e0b; }
    .trip-card.trip-status-secondary { border-left: 4px solid #64748b; }
    .trip-card.trip-status-info { border-left: 4px solid #06b6d4; }
    .trip-card.trip-status-success { border-left: 4px solid #10b981; }
    
    .trip-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.5rem;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .trip-card-main { flex: 1; min-width: 0; }
    
    .trip-card-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
    .trip-number {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        background: #e2e8f0;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        letter-spacing: 0.02em;
    }
    .trip-name {
        font-size: 1.125rem;
        font-weight: 600;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .status-pill {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
    }
    .status-pill.status-warning { background: #fef3c7; color: #b45309; }
    .status-pill.status-secondary { background: #f1f5f9; color: #475569; }
    .status-pill.status-info { background: #cffafe; color: #0891b2; }
    .status-pill.status-success { background: #d1fae5; color: #047857; }
    
    .trip-card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8125rem;
        color: #475569;
        background: #fff;
        padding: 0.35rem 0.75rem;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        font-weight: 500;
    }
    .meta-pill i {
        font-size: 0.9rem;
        color: #94a3b8;
    }
    .meta-pill.meta-crew {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-color: #bfdbfe;
        color: #1d4ed8;
    }
    .meta-pill.meta-crew i { color: #3b82f6; }
    
    .trip-card-actions {
        display: flex;
        gap: 0.25rem;
        flex-shrink: 0;
    }
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        transition: all 0.2s ease;
    }
    .btn-action:hover {
        background: #f8fafc;
        color: #334155;
        border-color: #cbd5e1;
    }
    .btn-action-edit {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }
    .btn-action-edit:hover {
        background: #0b5ed7;
        border-color: #0b5ed7;
        color: #fff;
    }
    .btn-action-delete:hover {
        background: #fef2f2;
        border-color: #fecaca;
        color: #dc2626;
    }
    
    .trip-card-crews {
        padding: 1.25rem 1.5rem;
    }
    .crews-section-header {
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .crews-label {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #94a3b8;
    }
    .crews-label i {
        margin-right: 0.4rem;
        opacity: 0.9;
    }
    
    .trip-crews-table {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
    }
    .trip-crews-table thead th {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        padding: 0.65rem 1rem;
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    .trip-crews-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }
    .trip-crews-table tbody tr:last-child { border-bottom: none; }
    .trip-crews-table tbody tr:nth-child(even) { background: #fafbfc; }
    .trip-crews-table tbody tr:hover { background: #f1f5f9 !important; }
    .trip-crews-table tbody td {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        vertical-align: middle;
        color: #334155;
    }
    .crew-index {
        color: #94a3b8;
        font-weight: 600;
        width: 2.5rem;
        font-size: 0.8125rem;
    }
    .crew-name {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        color: #0f172a;
    }
    .crew-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        color: #4338ca;
        font-size: 0.6875rem;
        font-weight: 700;
    }
    .route-cell { font-size: 0.8125rem; }
    .route-from, .route-to { color: #475569; }
    .route-arrow {
        color: #cbd5e1;
        margin: 0 0.35rem;
        font-size: 0.7rem;
    }
    
    .crews-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 2rem;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px dashed #e2e8f0;
        color: #64748b;
        font-size: 0.875rem;
        font-weight: 500;
    }
    .crews-empty i {
        font-size: 1.5rem;
        opacity: 0.5;
    }
    
    .trip-empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: #f8fafc;
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
    }
    .trip-empty-icon {
        width: 5rem;
        height: 5rem;
        margin: 0 auto 1.25rem;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.25rem;
        color: #94a3b8;
    }
    .trip-empty-state h5 { color: #334155; font-weight: 600; }
    
    .assign-driver-inline {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .assign-driver-inline .assign-driver-select {
        font-size: 0.8125rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        border-color: #cbd5e1;
    }
    .assign-driver-inline .assign-driver-btn {
        white-space: nowrap;
    }
    
    .trips-list-card {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.06);
    }
    .trips-list-card .card-body {
        background: #f8fafc;
    }
    .filter-bar {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }
</style>
@endpush
@endsection

