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
                    @if(isset($sourcePartnerRequest))
                        <input type="hidden" name="partner_request_id" value="{{ $sourcePartnerRequest->id }}">
                    @endif

                    @if(isset($sourcePartnerRequest))
                        <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4 d-flex flex-wrap align-items-start justify-content-between gap-3" role="status">
                            <div class="d-flex align-items-start gap-3">
                                <i class="ri-file-list-3-line fs-22 text-info mt-1"></i>
                                <div>
                                    <h6 class="alert-heading fw-bold mb-1">Source Request: {{ $sourcePartnerRequest->request_reference }}</h6>
                                    <p class="mb-0 small text-muted">Partner is locked to this request. Complete operational details below using the normal trip workflow.</p>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('partner-requests.show', $sourcePartnerRequest) }}" class="btn btn-sm btn-soft-primary">
                                    <i class="ri-arrow-left-line me-1"></i> Back to Request
                                </a>
                                @if($sourcePartnerRequest->isImage())
                                    <a href="{{ route('partner-requests.image', $sourcePartnerRequest) }}" target="_blank" class="btn btn-sm btn-soft-secondary">
                                        <i class="ri-image-line me-1"></i> View Source Schedule
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="partner_id" class="form-label fw-semibold">Partner</label>
                                @if(isset($sourcePartnerRequest))
                                    <input type="hidden" name="partner_id" value="{{ $sourcePartnerRequest->partner_id }}">
                                    <input type="text" class="form-control" value="{{ $sourcePartnerRequest->partner->title }}" readonly>
                                @else
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
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4 d-flex align-items-start" role="alert">
                        <div class="flex-shrink-0 me-3">
                            <i class="ri-information-fill fs-22 text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading fw-bold mb-1"><i class="ri-route-line me-1"></i> Driver Assignment & Automatic Trip Splitting</h6>
                            <small class="text-muted d-block">
                                Rows with the same <strong>Driver</strong> and <strong>Date</strong> are grouped into one Trip. Different drivers or dates automatically create separate Trips. Leaving a row as <strong>"Assign Later"</strong> creates an unassigned trip dispatch.
                            </small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <h5 class="mb-0">Crew Details</h5>
                        <button type="button" class="btn btn-primary btn-sm" id="add-crew-row-btn">
                            <i class="ri-add-line align-middle me-1"></i> Add Crew Row
                        </button>
                    </div>
                    <p class="table-scroll-hint">
                        <i class="ri-arrow-left-right-line"></i> Scroll sideways to reach every crew field.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-crews" id="crews-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="min-width: 140px; width: 140px;">Date <span class="text-danger">*</span></th>
                                    <th style="min-width: 160px;">Driver Name <i class="ri-information-line text-info ms-1" data-bs-toggle="tooltip" title="Rows with the same Driver and Date are grouped into one Trip."></i></th>
                                    <th>Vessel Name <span class="text-danger">*</span></th>
                                    <th>Pick-up Time <span class="text-danger">*</span></th>
                                    <th>Flight Number</th>
                                    <th>Crew Name <span class="text-danger">*</span></th>
                                    <th>Crew Contact No</th>
                                    <th>Crew Contact No 2</th>
                                    <th>From <span class="text-danger">*</span></th>
                                    <th>To <span class="text-danger">*</span></th>
                                    <th>Remarks</th>
                                    <th>Sub Remark</th>
                                    <th style="width: 50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="crews-container">
                                @php
                                    $crews = old('crews', []);
                                    if (empty($crews)) {
                                        $crews = $prefillCrews ?? [[
                                            'name' => '',
                                            'driver_id' => '',
                                            'trip_date' => date('Y-m-d'),
                                            'vessel_id' => '',
                                            'pick_up_time' => '',
                                            'from_location' => '',
                                            'to_location' => '',
                                            'phone' => '',
                                            'phone_2' => '',
                                            'remarks' => '',
                                            'sub_remark' => '',
                                            'address' => '',
                                            'flight_number' => '',
                                        ]];
                                    }
                                @endphp
                                @foreach($crews as $index => $crew)
                                    <tr class="crew-row" data-index="{{ $index }}">
                                        <td class="text-center fw-semibold">{{ $index + 1 }}</td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm" name="crews[{{ $index }}][trip_date]" value="{{ $crew['trip_date'] ?? date('Y-m-d') }}" required>
                                        </td>
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
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="crews[{{ $index }}][sub_remark]" value="{{ $crew['sub_remark'] ?? '' }}" placeholder="Sub Remark">
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

                    <div class="form-check form-switch mt-4 mb-3">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            role="switch"
                            id="create_return_trip"
                            name="create_return_trip"
                            value="1"
                            {{ old('create_return_trip') ? 'checked' : '' }}
                        >
                        <label class="form-check-label fw-semibold" for="create_return_trip">Return Trip</label>
                        <div class="form-text">Create a separate unassigned trip with From and To reversed.</div>
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
        background-color: var(--vz-light, #f8f9fa);
        font-weight: 600;
        white-space: nowrap;
        vertical-align: middle;
    }
    #crews-table tbody tr {
        transition: background-color 0.2s;
    }
    #crews-table tbody tr:hover {
        background-color: var(--vz-light, #f8f9fa);
    }
    #crews-table tbody td {
        vertical-align: middle;
        background-color: var(--vz-secondary-bg, #fff);
    }
    #crews-table .form-control-sm,
    #crews-table .form-select-sm {
        border: 1px solid var(--vz-input-border-custom, #dee2e6);
        background-color: var(--vz-input-bg-custom, #fff);
        color: var(--vz-body-color, inherit);
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
        border: 1px solid var(--vz-input-border-custom, #dee2e6);
        border-radius: 0.25rem;
        background-color: var(--vz-input-bg-custom, #fff);
    }
    .vessel-select2 + .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: calc(1.5em + 0.5rem);
        padding-left: 0.5rem;
        padding-right: 20px;
        font-size: 0.875rem;
        color: var(--vz-body-color, inherit);
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
        border-bottom: 1px solid var(--vz-border-color, #dee2e6);
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
        const todayDate = '{{ date('Y-m-d') }}';
        const driverOptions = `
            <option value="">Assign Later</option>
            @foreach($drivers as $driver)
                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
            @endforeach
        `;
        // Session vessel list (server vessels + vessels created during this form session)
        const sessionVessels = [
            @foreach($vessels as $vessel)
                { id: '{{ $vessel->id }}', name: @json($vessel->name) },
            @endforeach
        ];

        function escapeHtml(text) {
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function buildVesselOptionsHtml() {
            let html = '<option value="">Select</option>';
            html += '<option value="__create_new__" class="create-new-vessel-option">Create New</option>';
            sessionVessels.forEach(function(vessel) {
                html += `<option value="${vessel.id}">${escapeHtml(vessel.name)}</option>`;
            });
            return html;
        }

        function rememberPreviousVessel($select) {
            const current = $select.val();
            if (current && current !== '__create_new__') {
                $select.data('previous-vessel-id', current);
            } else if (!current) {
                $select.data('previous-vessel-id', '');
            }
        }

        function restorePreviousVessel($select) {
            let previous = $select.data('previous-vessel-id');
            if (previous === undefined || previous === null || previous === '__create_new__') {
                previous = '';
            }
            $select.val(previous).trigger('change');
        }

        function ensureCreateNewOption($select) {
            if ($select.find('option[value="__create_new__"]').length === 0) {
                const $placeholder = $select.find('option[value=""]').first();
                if ($placeholder.length) {
                    $placeholder.after('<option value="__create_new__" class="create-new-vessel-option">Create New</option>');
                } else {
                    $select.prepend('<option value="__create_new__" class="create-new-vessel-option">Create New</option>');
                }
            }
        }

        function addVesselToSession(id, name) {
            const vesselId = String(id);
            if (!sessionVessels.some(function(v) { return String(v.id) === vesselId; })) {
                sessionVessels.push({ id: vesselId, name: name });
            }
        }

        function addVesselOptionToAllSelects(newVesselId, newVesselName, $triggerSelect) {
            const vesselId = String(newVesselId);
            addVesselToSession(vesselId, newVesselName);

            jQuery('.vessel-select2').each(function() {
                const $currentSelect = jQuery(this);
                const isTriggeringSelect = this === $triggerSelect[0];

                const alreadyExists = $currentSelect.find('option').filter(function() {
                    return String(this.value) === vesselId;
                }).length > 0;

                if (!alreadyExists) {
                    // Available everywhere; selected nowhere by default
                    $currentSelect.append(new Option(newVesselName, vesselId, false, false));
                }

                ensureCreateNewOption($currentSelect);

                if (isTriggeringSelect) {
                    $currentSelect.val(vesselId).trigger('change');
                    $currentSelect.data('previous-vessel-id', vesselId);
                }
                // Non-triggering rows: leave current selection untouched
            });
        }

        function bindVesselSelect2($select) {
            $select.select2({
                placeholder: 'Select Vessel',
                allowClear: true,
                width: '100%',
                templateResult: function(data) {
                    if (!data.id) {
                        return data.text;
                    }
                    if (data.id === '__create_new__') {
                        return jQuery('<span style="background-color: #e7f3ff; color: #0d6efd; font-weight: 500; font-size: 0.8125rem; padding: 4px 8px; border-radius: 4px; display: inline-block; width: 100%;">' + data.text + '</span>');
                    }
                    return data.text;
                }
            }).on('select2:opening', function() {
                rememberPreviousVessel(jQuery(this));
            }).on('select2:selecting', function() {
                rememberPreviousVessel(jQuery(this));
            }).on('select2:select', function(e) {
                const data = e.params.data;
                if (data.id === '__create_new__') {
                    e.preventDefault();
                    createNewVessel(jQuery(this));
                } else {
                    rememberPreviousVessel(jQuery(this));
                }
            });

            // Seed previous value from the initial selection
            rememberPreviousVessel($select);
        }

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
                    <input type="date" class="form-control form-control-sm" name="crews[${index}][trip_date]" value="${todayDate}" required>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="crews[${index}][driver_id]">
                        ${driverOptions}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm vessel-select2" name="crews[${index}][vessel_id]" required>
                        ${buildVesselOptionsHtml()}
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
                <td>
                    <input type="text" class="form-control form-control-sm" name="crews[${index}][sub_remark]" placeholder="Sub Remark">
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
                    bindVesselSelect2(jQuery(select));
                }
            }, 100);
            
            return row;
        }
        
        // Initialize Select2 for existing vessel selects
        function initSelect2() {
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                jQuery('.vessel-select2').each(function() {
                    bindVesselSelect2(jQuery(this));
                });
            }
        }
        
        // Function to create a new vessel
        function createNewVessel($select) {
            // Capture previous valid vessel before prompt (never treat Create New as a vessel)
            rememberPreviousVessel($select);
            const previousVesselId = $select.data('previous-vessel-id');

            const vesselName = prompt('Enter the new vessel name:');
            if (!vesselName || vesselName.trim() === '') {
                restorePreviousVessel($select);
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
                        addVesselOptionToAllSelects(response.vessel.id, response.vessel.name, $select);
                    } else {
                        $select.data('previous-vessel-id', previousVesselId);
                        restorePreviousVessel($select);
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
                    $select.data('previous-vessel-id', previousVesselId);
                    restorePreviousVessel($select);
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

