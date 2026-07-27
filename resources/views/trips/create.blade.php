@extends('layouts.app')

@section('title', 'Add Trip | ' . config('app.name'))

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

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trip_date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('trip_date') is-invalid @enderror" id="trip_date" name="trip_date" value="{{ old('trip_date', date('Y-m-d')) }}" required>
                                @error('trip_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="partner_id" class="form-label fw-semibold">Partner</label>
                                <select class="form-select @error('partner_id') is-invalid @enderror" id="partner_id" name="partner_id">
                                    <option value="">Select Partner</option>
                                    @foreach($partners as $partner)
                                        <option value="{{ $partner->id }}" {{ old('partner_id', $defaultPartner->id ?? '') == $partner->id ? 'selected' : '' }}>
                                            {{ $partner->title }}
                                            @if($partner->is_default)
                                                (Default)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('partner_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0">Crew Details</h5>
                        <button type="button" class="btn btn-primary btn-sm" id="add-crew-row-btn">
                            <i class="ri-add-line align-middle me-1"></i> Add Crew Row
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="crews-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="min-width: 150px;">Driver Name</th>
                                    <th>Vessel Name <span class="text-danger">*</span></th>
                                    <th>Pick-up Time <span class="text-danger">*</span></th>
                                    <th>Flight Number</th>
                                    <th>Crew Name <span class="text-danger">*</span></th>
                                    <th>Crew Contact No</th>
                                    <th>Crew Contact No 2</th>
                                    <th>From <span class="text-danger">*</span></th>
                                    <th>To <span class="text-danger">*</span></th>
                                    <th>Remarks</th>
                                    <th style="width: 50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="crews-container">
                                @php
                                    $crews = old('crews', []);
                                    if (empty($crews)) {
                                        $crews = [['name' => '', 'driver_id' => '', 'vessel_id' => '', 'pick_up_time' => '', 'from_location' => '', 'to_location' => '', 'phone' => '', 'phone_2' => '', 'remarks' => '', 'address' => '']];
                                    }
                                @endphp
                                @foreach($crews as $index => $crew)
                                    <tr class="crew-row" data-index="{{ $index }}">
                                        <td class="text-center fw-semibold">{{ $index + 1 }}</td>
                                        <td>
                                            <select class="form-select form-select-sm" name="crews[{{ $index }}][driver_id]">
                                                <option value="">Assign Later</option>
                                                @foreach($drivers as $driver)
                                                    <option value="{{ $driver->id }}" {{ ($crew['driver_id'] ?? '') == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm vessel-select2" name="crews[{{ $index }}][vessel_id]" required>
                                                <option value="">Select</option>
                                                <option value="__create_new__" class="create-new-vessel-option">Create New</option>
                                                @foreach($vessels as $vessel)
                                                    <option value="{{ $vessel->id }}" {{ ($crew['vessel_id'] ?? '') == $vessel->id ? 'selected' : '' }}>{{ $vessel->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="time" class="form-control form-control-sm" name="crews[{{ $index }}][pick_up_time]" value="{{ $crew['pick_up_time'] ?? '' }}" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="crews[{{ $index }}][flight_number]" value="{{ $crew['flight_number'] ?? '' }}" placeholder="Flight number">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="crews[{{ $index }}][name]" value="{{ $crew['name'] ?? '' }}" placeholder="Enter name" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="crews[{{ $index }}][phone]" value="{{ $crew['phone'] ?? '' }}" placeholder="Contact number">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="crews[{{ $index }}][phone_2]" value="{{ $crew['phone_2'] ?? '' }}" placeholder="Contact number">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="crews[{{ $index }}][from_location]" value="{{ $crew['from_location'] ?? '' }}" placeholder="From" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="crews[{{ $index }}][to_location]" value="{{ $crew['to_location'] ?? '' }}" placeholder="To" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="crews[{{ $index }}][remarks]" value="{{ $crew['remarks'] ?? '' }}" placeholder="Remarks">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger remove-row-btn" {{ count($crews) == 1 ? 'disabled' : '' }} title="Remove row">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-success" type="submit">Create Trip</button>
                        <a href="{{ route('trips.index') }}" class="btn btn-secondary" id="cancel-trip-btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    #crews-table {
        margin-bottom: 0;
    }
    #crews-table thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        white-space: nowrap;
        vertical-align: middle;
    }
    #crews-table tbody tr {
        transition: background-color 0.2s;
    }
    #crews-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    #crews-table tbody td {
        vertical-align: middle;
    }
    #crews-table .form-control-sm,
    #crews-table .form-select-sm {
        border: 1px solid #dee2e6;
    }
    #crews-table .form-control-sm:focus,
    #crews-table .form-select-sm:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    /* Select2 styling for small selects */
    .vessel-select2 + .select2-container {
        width: 100% !important;
    }
    .vessel-select2 + .select2-container .select2-selection--single {
        height: calc(1.5em + 0.5rem + 2px);
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }
    .vessel-select2 + .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: calc(1.5em + 0.5rem);
        padding-left: 0.5rem;
        padding-right: 20px;
        font-size: 0.875rem;
    }
    .vessel-select2 + .select2-container .select2-selection--single .select2-selection__arrow {
        height: calc(1.5em + 0.5rem);
        right: 1px;
    }
    /* Style for Create New Vessel option in dropdown */
    .select2-results__option[data-select2-id*="__create_new__"],
    .select2-results__option:has([value="__create_new__"]) {
        background-color: #e7f3ff !important;
        color: #0d6efd !important;
        font-weight: 500 !important;
        font-size: 0.8125rem !important;
        border-top: 1px solid #0d6efd;
        border-bottom: 1px solid #dee2e6;
        padding: 6px 12px;
    }
    .select2-results__option[data-select2-id*="__create_new__"]:hover,
    .select2-results__option:has([value="__create_new__"]):hover {
        background-color: #cfe2ff !important;
    }
    .remove-row-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    .crew-row {
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
</style>
@endpush

@push('scripts')
<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addCrewBtn = document.getElementById('add-crew-row-btn');
        const crewsContainer = document.getElementById('crews-container');
        const driverOptions = `
            <option value="">Assign Later</option>
            @foreach($drivers as $driver)
                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
            @endforeach
        `;
        const vesselOptions = `
            <option value="">Select</option>
            @foreach($vessels as $vessel)
                <option value="{{ $vessel->id }}">{{ $vessel->name }}</option>
            @endforeach
        `;

        function updateRowNumbers() {
            const rows = crewsContainer.querySelectorAll('.crew-row');
            rows.forEach((row, index) => {
                const numberCell = row.querySelector('td:first-child');
                if (numberCell) {
                    numberCell.textContent = index + 1;
                }
                // Update data-index
                row.setAttribute('data-index', index);
                // Update input names
                const inputs = row.querySelectorAll('input, select');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    if (name) {
                        const fieldName = name.match(/\[([^\]]+)\]$/)[1];
                        input.setAttribute('name', `crews[${index}][${fieldName}]`);
                    }
                });
            });
        }

        function updateRemoveButtons() {
            const rows = crewsContainer.querySelectorAll('.crew-row');
            const removeButtons = crewsContainer.querySelectorAll('.remove-row-btn');
            removeButtons.forEach(btn => {
                btn.disabled = rows.length <= 1;
            });
        }

        function createCrewRow(index) {
            const row = document.createElement('tr');
            row.className = 'crew-row';
            row.setAttribute('data-index', index);
            row.innerHTML = `
                <td class="text-center fw-semibold">${index + 1}</td>
                <td>
                    <select class="form-select form-select-sm" name="crews[${index}][driver_id]">
                        ${driverOptions}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm vessel-select2" name="crews[${index}][vessel_id]" required>
                        <option value="">Select</option>
                        <option value="__create_new__" class="create-new-vessel-option">Create New </option>
                        ${vesselOptions.replace('<option value="">Select</option>', '')}
                    </select>
                </td>
                <td>
                    <input type="time" class="form-control form-control-sm" name="crews[${index}][pick_up_time]" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="crews[${index}][flight_number]" placeholder="Flight number">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="crews[${index}][name]" placeholder="Enter name" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="crews[${index}][phone]" placeholder="Contact number">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="crews[${index}][phone_2]" placeholder="Contact number">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="crews[${index}][from_location]" placeholder="From" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="crews[${index}][to_location]" placeholder="To" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="crews[${index}][remarks]" placeholder="Remarks">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row-btn" title="Remove row">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </td>
            `;
            
            // Initialize Select2 for the new vessel select
            setTimeout(() => {
                const select = row.querySelector('.vessel-select2');
                if (select && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    jQuery(select).select2({
                        placeholder: 'Select Vessel',
                        allowClear: true,
                        width: '100%',
                        templateResult: function(data) {
                            if (!data.id) {
                                return data.text;
                            }
                            if (data.id === '__create_new__') {
                                var $result = jQuery('<span style="background-color: #e7f3ff; color: #0d6efd; font-weight: 500; font-size: 0.8125rem; padding: 4px 8px; border-radius: 4px; display: inline-block; width: 100%;">' + data.text + '</span>');
                                return $result;
                            }
                            return data.text;
                        }
                    }).on('select2:select', function(e) {
                        const data = e.params.data;
                        if (data.id === '__create_new__') {
                            e.preventDefault();
                            createNewVessel(jQuery(this));
                        }
                    });
                }
            }, 100);
            
            return row;
        }
        
        // Initialize Select2 for existing vessel selects
        function initSelect2() {
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                jQuery('.vessel-select2').select2({
                    placeholder: 'Select Vessel',
                    allowClear: true,
                    width: '100%',
                    templateResult: function(data) {
                        if (!data.id) {
                            return data.text;
                        }
                        if (data.id === '__create_new__') {
                            var $result = jQuery('<span style="background-color: #e7f3ff; color: #0d6efd; font-weight: 500; font-size: 0.8125rem; padding: 4px 8px; border-radius: 4px; display: inline-block; width: 100%;">' + data.text + '</span>');
                            return $result;
                        }
                        return data.text;
                    }
                }).on('select2:select', function(e) {
                    const data = e.params.data;
                    if (data.id === '__create_new__') {
                        e.preventDefault();
                        createNewVessel(jQuery(this));
                    }
                });
            }
        }
        
        // Function to create a new vessel
        function createNewVessel($select) {
            const vesselName = prompt('Enter the new vessel name:');
            if (!vesselName || vesselName.trim() === '') {
                $select.val('').trigger('change');
                return;
            }
            
            // Show loading state
            const originalHtml = $select.next('.select2-container').find('.select2-selection__rendered').html();
            $select.next('.select2-container').find('.select2-selection__rendered').html('<span class="spinner-border spinner-border-sm me-2"></span>Creating...');
            $select.prop('disabled', true);
            
            // Create vessel via AJAX
            jQuery.ajax({
                url: '{{ route("vessels.store") }}',
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                data: {
                    _token: '{{ csrf_token() }}',
                    name: vesselName.trim()
                },
                success: function(response) {
                    if (response.success && response.vessel) {
                        const newVesselId = response.vessel.id;
                        const newVesselName = response.vessel.name;
                        jQuery('.vessel-select2').each(function() {
                            const isTriggeringSelect = this === $select[0];
                            jQuery(this).find('option[value="__create_new__"]').remove();
                            const opt = new Option(newVesselName, newVesselId, isTriggeringSelect, isTriggeringSelect);
                            jQuery(this).append(opt);
                            if (isTriggeringSelect) {
                                jQuery(this).val(newVesselId).trigger('change');
                            }
                        });
                        jQuery('.vessel-select2').each(function() {
                            if (jQuery(this).find('option[value="__create_new__"]').length === 0) {
                                jQuery(this).find('option:first').after('<option value="__create_new__" class="create-new-vessel-option">Create New</option>');
                            }
                        });
                    }
                    $select.prop('disabled', false);
                },
                error: function(xhr) {
                    let errorMsg = 'Failed to create vessel.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMsg = Object.values(xhr.responseJSON.errors).flat().join('\\n');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 422) {
                        errorMsg = 'Vessel name already exists or is invalid.';
                    }
                    alert(errorMsg);
                    $select.val('').trigger('change');
                    $select.prop('disabled', false);
                    $select.next('.select2-container').find('.select2-selection__rendered').html(originalHtml);
                }
            });
        }

        // Handle add crew row button
        if (addCrewBtn) {
            addCrewBtn.addEventListener('click', function() {
                const currentRows = crewsContainer.querySelectorAll('.crew-row');
                const newIndex = currentRows.length;
                const newRow = createCrewRow(newIndex);
                crewsContainer.appendChild(newRow);
                updateRowNumbers();
                updateRemoveButtons();
            });
        }
        
        // Initialize Select2 on page load
        if (typeof jQuery !== 'undefined') {
            if (jQuery.fn.select2) {
                initSelect2();
            } else {
                // Wait for Select2 to load
                const checkSelect2 = setInterval(function() {
                    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                        initSelect2();
                        clearInterval(checkSelect2);
                    }
                }, 100);
            }
        }

        // Handle remove row button
        crewsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-row-btn')) {
                const btn = e.target.closest('.remove-row-btn');
                if (!btn.disabled) {
                    const row = btn.closest('.crew-row');
                    const currentCount = crewsContainer.querySelectorAll('.crew-row').length;
                    if (currentCount > 1) {
                        // Destroy Select2 before removing row
                        const select = row.querySelector('.vessel-select2');
                        if (select && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                            jQuery(select).select2('destroy');
                        }
                        row.remove();
                        updateRowNumbers();
                        updateRemoveButtons();
                    }
                }
            }
        });

        // Initialize
        updateRemoveButtons();

        // Auto-update trip title when driver or date changes
        const driverSelect = document.getElementById('driver_id');
        const dateInput = document.getElementById('trip_date');
        const titleInput = document.getElementById('title');

        function updateTripTitle() {
            const driverId = driverSelect.value;
            const tripDate = dateInput.value;
            
            if (tripDate) {
                let url = `{{ route('trips.generate-title') }}?trip_date=${tripDate}`;
                if (driverId) {
                    url += `&driver_id=${driverId}`;
                }
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.title) {
                        titleInput.value = data.title;
                    }
                })
                .catch(error => {
                    console.error('Error generating title:', error);
                });
            }
        }

        if (driverSelect && dateInput) {
            driverSelect.addEventListener('change', updateTripTitle);
            dateInput.addEventListener('change', updateTripTitle);
        }

        const cancelBtn = document.getElementById('cancel-trip-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
                    e.preventDefault();
                }
            });
        }
    });
</script>
@endpush

