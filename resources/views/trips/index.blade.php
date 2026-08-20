@extends('layouts.app')

@section('title', 'Trips | ' . config('app.name'))

@section('content')
@php
    $dateRange = request('date_range', 'today');
    $listTitle = match ($dateRange) {
        'today' => "Today's Trips",
        'yesterday' => "Yesterday's Trips",
        'tomorrow' => "Tomorrow's Trips",
        'last_2_days' => 'Trips — Last 2 Days',
        'last_7_days' => 'Trips — Last 7 Days',
        'this_month' => 'Trips — This Month',
        'custom' => 'Filtered Trips',
        default => 'Trips',
    };
@endphp

@include('partials.page-header', [
    'title' => 'Trips Management',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Trips'],
    ],
])

<!-- Statistics Overview Cards -->
<div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
    @include('partials.stat-card', [
        'label' => 'Total Trips',
        'value' => $stats['total_trips'],
        'icon' => 'ri-calendar-check-line',
        'color' => 'primary',
        'colClass' => 'col',
        'useCounter' => false,
    ])
    @include('partials.stat-card', [
        'label' => 'Total Crew',
        'value' => $stats['total_jobs'],
        'icon' => 'ri-briefcase-line',
        'color' => 'info',
        'colClass' => 'col',
        'useCounter' => false,
    ])
    @include('partials.stat-card', [
        'label' => 'In Progress',
        'value' => $stats['trips_in_progress'],
        'icon' => 'ri-time-line',
        'color' => 'warning',
        'colClass' => 'col',
        'useCounter' => false,
    ])
    @include('partials.stat-card', [
        'label' => 'Completed',
        'value' => $stats['trips_completed'],
        'icon' => 'ri-checkbox-circle-line',
        'color' => 'success',
        'colClass' => 'col',
        'useCounter' => false,
    ])
    @include('partials.stat-card', [
        'label' => 'Cancelled',
        'value' => $stats['trips_cancelled'] ?? 0,
        'icon' => 'ri-close-circle-line',
        'color' => 'danger',
        'colClass' => 'col',
        'useCounter' => false,
    ])
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body">
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
    
    <div class="col-md-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body">
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
                        <div id="extract-dropzone" class="extract-dropzone @error('image') is-invalid @enderror" role="button" tabindex="0" aria-label="Upload table image">
                            <input type="file" class="extract-dropzone-input @error('image') is-invalid @enderror" id="image" name="image" accept="image/jpeg,image/jpg,image/png" required>
                            <div class="extract-dropzone-content text-center">
                                <div class="avatar-sm mx-auto mb-2">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-4">
                                        <i class="ri-upload-cloud-2-line"></i>
                                    </span>
                                </div>
                                <p class="mb-1 fw-medium">Drag & drop image here</p>
                                <p class="mb-2 text-muted small">or click to browse</p>
                                <span id="extract-file-name" class="badge bg-light text-body border d-none"></span>
                            </div>
                        </div>
                        <div class="form-text">Formats: JPEG, JPG, PNG (Max: 10MB)</div>
                        @error('image')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
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
        <div class="card border shadow-sm trips-list-card">
            <div class="card-header border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="card-title mb-0">{{ $listTitle }}</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#filterSection">
                        <i class="ri-filter-3-line me-1"></i> Filters
                    </button>
                </div>
            </div>
            <div class="card-body py-4">

                <div class="collapse show" id="filterSection">
                    <div class="row g-3 mb-4 p-4 filter-bar">
                        <div class="col-sm-6 col-xxl-3">
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
                        <div class="col-sm-6 col-xxl-3">
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
                        <div class="col-sm-6 col-xxl-2">
                            <label class="form-label fw-semibold">
                                <i class="ri-checkbox-circle-line me-1"></i>Status
                            </label>
                            <select id="filter-status" class="form-select">
                                <option value="">All Status</option>
                                <option value="unassigned">Unassigned</option>
                                <option value="assigned">Assigned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-xxl-2">
                            <label class="form-label fw-semibold">
                                <i class="ri-hashtag me-1"></i>Reference
                            </label>
                            <input type="text" id="filter-search" class="form-control" placeholder="TRP or REQ" value="{{ request('search') }}">
                        </div>
                        <div class="col-sm-6 col-xxl-2">
                            <label class="form-label fw-semibold">
                                <i class="ri-calendar-line me-1"></i>Date Range
                            </label>
                            <select id="filter-date-range" class="form-select">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="tomorrow">Tomorrow</option>
                                <option value="last_2_days">Last 2 Days</option>
                                <option value="last_7_days">Last 7 Days</option>
                                <option value="this_month">This Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="col-12 col-xxl-2 d-flex align-items-end gap-2">
                            <button type="button" id="filter-apply" class="btn btn-primary flex-fill flex-sm-grow-0 flex-xxl-fill px-4">
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
                                    <div class="min-width-0">
                                        @if($trip->trip_reference)
                                            <div class="text-muted small">{{ $trip->trip_reference }}</div>
                                        @endif
                                        <h5 class="trip-name mb-0">{{ $trip->title ?? 'Untitled Trip' }}</h5>
                                        @if($trip->partnerRequest)
                                            <div class="text-muted small mt-1">
                                                Source:
                                                <a href="{{ route('partner-requests.show', $trip->partnerRequest) }}">
                                                    {{ $trip->partnerRequest->request_reference }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
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
                                @if(!$trip->isCancelled())
                                <form action="{{ route('trips.cancel', $trip->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this trip?');">
                                    @csrf
                                    <button type="submit" class="btn-action btn-action-cancel text-warning" data-bs-toggle="tooltip" title="Cancel Trip">
                                        <i class="ri-close-circle-line"></i>
                                    </button>
                                </form>
                                @endif
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
                                    <table class="table table-sm table-nowrap trip-crews-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Crew Name</th>
                                                <th>Contact</th>
                                                <th>Vessel</th>
                                                <th>Pick-up</th>
                                                <th class="d-none d-md-table-cell">Flight</th>
                                                <th>Route</th>
                                                <th class="d-none d-lg-table-cell">Remarks</th>
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
                                                    <td class="d-none d-md-table-cell">{{ $crew->flight_number ?? '—' }}</td>
                                                    <td class="route-cell">
                                                        <span class="route-from">{{ $crew->from_location }}</span>
                                                        <i class="ri-arrow-right-line route-arrow"></i>
                                                        <span class="route-to">{{ $crew->to_location }}</span>
                                                    </td>
                                                    <td class="d-none d-lg-table-cell">
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
                    @include('partials.empty-state', [
                        'icon' => 'ri-calendar-todo-line',
                        'title' => 'No trips found',
                        'hint' => 'Try adjusting your filters or create a new trip.',
                        'actionUrl' => route('trips.create'),
                        'actionLabel' => 'Create Trip',
                    ])
                @endforelse

                @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var driverSel = document.getElementById('filter-driver');
                        var vesselSel = document.getElementById('filter-vessel');
                        var statusSel = document.getElementById('filter-status');
                        var searchInp = document.getElementById('filter-search');
                        var dateRangeSel = document.getElementById('filter-date-range');
                        var dateFromInp = document.getElementById('filter-date-from');
                        var dateToInp = document.getElementById('filter-date-to');
                        var customDateRow = document.getElementById('custom-date-row');
                        var applyBtn = document.getElementById('filter-apply');
                        var resetBtn = document.getElementById('filter-reset');

                        var urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('driver')) driverSel.value = urlParams.get('driver');
                        if (urlParams.has('vessel')) vesselSel.value = urlParams.get('vessel');
                        if (urlParams.has('status')) statusSel.value = urlParams.get('status');
                        if (urlParams.has('search')) searchInp.value = urlParams.get('search');
                        
                        if (urlParams.has('date_range')) {
                            dateRangeSel.value = urlParams.get('date_range');
                            if (dateRangeSel.value === 'custom') {
                                customDateRow.style.display = 'block';
                            }
                        }
                        
                        if (urlParams.has('date_from')) dateFromInp.value = urlParams.get('date_from');
                        if (urlParams.has('date_to')) dateToInp.value = urlParams.get('date_to');

                        dateRangeSel.addEventListener('change', function() {
                            customDateRow.style.display = this.value === 'custom' ? 'block' : 'none';
                        });

                        function applyFilters() {
                            var params = new URLSearchParams();
                            if (driverSel.value) params.set('driver', driverSel.value);
                            if (vesselSel.value) params.set('vessel', vesselSel.value);
                            if (statusSel.value) params.set('status', statusSel.value);
                            if (searchInp.value) params.set('search', searchInp.value.trim());
                            
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

                        var extractForm = document.getElementById('extract-form');
                        var extractBtn = document.getElementById('extract-btn');
                        
                        if (extractForm) {
                            extractForm.addEventListener('submit', function() {
                                extractBtn.disabled = true;
                                extractBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
                            });
                        }

                        var imageInput = document.getElementById('image');
                        var imagePreview = document.getElementById('imagePreview');
                        var previewImg = document.getElementById('previewImg');
                        var dropzone = document.getElementById('extract-dropzone');
                        var fileNameBadge = document.getElementById('extract-file-name');
                        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                        var maxBytes = 10 * 1024 * 1024;

                        function showSelectedFile(file) {
                            if (!file) {
                                imagePreview.classList.add('d-none');
                                previewImg.src = '';
                                if (fileNameBadge) {
                                    fileNameBadge.textContent = '';
                                    fileNameBadge.classList.add('d-none');
                                }
                                return;
                            }

                            if (fileNameBadge) {
                                fileNameBadge.textContent = file.name;
                                fileNameBadge.classList.remove('d-none');
                            }

                            var reader = new FileReader();
                            reader.onload = function(e) {
                                previewImg.src = e.target.result;
                                imagePreview.classList.remove('d-none');
                            };
                            reader.readAsDataURL(file);
                        }

                        function assignFile(file) {
                            if (!file) {
                                return;
                            }

                            if (allowedTypes.indexOf(file.type) === -1) {
                                alert('Please upload a JPEG, JPG, or PNG image.');
                                return;
                            }

                            if (file.size > maxBytes) {
                                alert('Image must be 10MB or smaller.');
                                return;
                            }

                            var dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            imageInput.files = dataTransfer.files;
                            showSelectedFile(file);
                        }

                        if (imageInput) {
                            imageInput.addEventListener('change', function(e) {
                                showSelectedFile(e.target.files[0] || null);
                            });
                        }

                        if (dropzone && imageInput) {
                            dropzone.addEventListener('click', function(e) {
                                if (e.target === imageInput) {
                                    return;
                                }
                                imageInput.click();
                            });

                            dropzone.addEventListener('keydown', function(e) {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    imageInput.click();
                                }
                            });

                            ['dragenter', 'dragover'].forEach(function(eventName) {
                                dropzone.addEventListener(eventName, function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    dropzone.classList.add('is-dragover');
                                });
                            });

                            ['dragleave', 'dragend', 'drop'].forEach(function(eventName) {
                                dropzone.addEventListener(eventName, function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    dropzone.classList.remove('is-dragover');
                                });
                            });

                            dropzone.addEventListener('drop', function(e) {
                                var files = e.dataTransfer && e.dataTransfer.files;
                                if (files && files.length) {
                                    assignFile(files[0]);
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
                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                    });
                </script>
                @endpush
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .extract-dropzone {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 160px;
        padding: 1.25rem;
        border: 2px dashed var(--vz-border-color, #ced4da);
        border-radius: 0.5rem;
        background-color: var(--vz-light, #f8f9fa);
        cursor: pointer;
        transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }
    .extract-dropzone:hover,
    .extract-dropzone:focus {
        border-color: var(--vz-primary, #405189);
        background-color: var(--vz-primary-bg-subtle, #eef2ff);
        outline: none;
    }
    .extract-dropzone.is-dragover {
        border-color: var(--vz-primary, #405189);
        background-color: var(--vz-primary-bg-subtle, #eef2ff);
        box-shadow: 0 0 0 0.2rem rgba(64, 81, 137, 0.15);
    }
    .extract-dropzone.is-invalid {
        border-color: var(--vz-danger, #f06548);
    }
    .extract-dropzone-input {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
        opacity: 0;
    }
    .extract-dropzone-content {
        pointer-events: none;
    }
</style>
@endpush

