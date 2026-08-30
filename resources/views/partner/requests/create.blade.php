@extends('layouts.partner')

@section('title', 'Create New Request - Partner Portal')

@section('content')
@include('partner.partials.page-header', [
    'title' => 'Create New Request',
    'subtitle' => 'Enter crew transportation details manually.',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('partner.dashboard')],
        ['label' => 'New Request', 'url' => route('partner.requests.new')],
        ['label' => 'Manual Entry']
    ]
])

<form action="{{ route('partner.requests.store') }}" method="POST" id="requestForm">
    @csrf

    <input type="hidden" name="entry_mode" id="entry_mode" value="individual">

    <div class="row">
        <div class="col-lg-12">
            <!-- Mode Selector Card -->
            <div class="card partner-page-card mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3 fs-15">Entry Mode</h5>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <div class="mode-selector-card">
                                <input id="modeIndividual" name="mode_selector" type="radio" class="mode-selector-input" value="individual" checked>
                                <label class="mode-selector-label" for="modeIndividual">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="fs-14 fw-semibold text-body">Individual Entry</span>
                                        <i class="ri-checkbox-circle-fill mode-check-icon fs-18 text-primary"></i>
                                    </div>
                                    <span class="text-muted fs-12 text-wrap">Use when crew members have different dates or locations.</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mode-selector-card">
                                <input id="modeGroup" name="mode_selector" type="radio" class="mode-selector-input" value="group">
                                <label class="mode-selector-label" for="modeGroup">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="fs-14 fw-semibold text-body">Group / Bulk Entry</span>
                                        <i class="ri-checkbox-circle-fill mode-check-icon fs-18 text-primary"></i>
                                    </div>
                                    <span class="text-muted fs-12 text-wrap">Use when multiple crew members share the same date and route.</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Group Mode Common Details -->
            <div class="card partner-page-card mb-3 d-none" id="groupCommonDetailsCard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Common Details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="common-trip-date">
                                Trip Date <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   class="form-control"
                                   id="common-trip-date"
                                   name="_common[trip_date]">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="common-vessel">
                                Vessel
                            </label>
                            <select class="form-select vessel-select2" id="common-vessel" name="_common[vessel_id]" data-placeholder="Select vessel (optional)">
                                <option value="">Select vessel (optional)</option>
                                @foreach($vessels as $vessel)
                                    <option value="{{ $vessel->id }}">{{ $vessel->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="common-from">
                                From Location <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="common-from"
                                   name="_common[from_location]"
                                   placeholder="Pickup location">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="common-to">
                                To Location <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="common-to"
                                   name="_common[to_location]"
                                   placeholder="Drop-off location">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card partner-page-card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="partner-card-header-icon"><i class="ri-ship-line fs-5" aria-hidden="true"></i></span>
                        <div class="flex-grow-1 min-w-0">
                            <h5 class="card-title mb-0">Crew Transportation Details</h5>
                            <p class="text-muted mb-0 mt-1 small">
                                Add one or more crew members. Fields marked with
                                <span class="text-danger">*</span> are required.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Individual Mode Container -->
                    <div id="crew-items-container">
                        <!-- Crew items will be added here -->
                    </div>

                    <!-- Group Mode Container -->
                    <div id="group-crew-items-container" class="d-none">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" id="group-crew-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Crew Member Name <span class="text-danger">*</span></th>
                                        <th>Phone Number</th>
                                        <th style="width: 100px;">Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Group crew items will be added here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4" id="individual-actions">
                        <button type="button" class="btn btn-success btn-touch" id="addCrewBtn">
                            <i class="ri-add-line align-middle me-1"></i> Add Another Crew Member
                        </button>
                    </div>

                    <div class="mt-4 d-none gap-2 flex-wrap" id="group-actions">
                        <button type="button" class="btn btn-success btn-touch" id="addGroupRowBtn">
                            <i class="ri-add-line align-middle me-1"></i> Add Row
                        </button>
                        <button type="button" class="btn btn-outline-success btn-touch" id="addGroup5RowsBtn">
                            <i class="ri-add-line align-middle me-1"></i> Add 5 Rows
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="card partner-page-card partner-form-actions">
                <div class="card-body">
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                <a href="{{ route('partner.requests.new') }}" class="btn btn-light btn-touch">
                    <i class="ri-close-line align-middle me-1"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-touch" id="submitBtn">
                    <i class="ri-send-plane-fill align-middle me-1"></i> Submit Request
                </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Crew Item Template (Individual Mode) -->
<template id="crew-item-template">
    <div class="crew-item border rounded p-4 mb-4" data-index="0">
        <div class="crew-item-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 d-flex align-items-center">
                <i class="ri-user-3-line me-2 text-primary" aria-hidden="true"></i>
                Crew #<span class="crew-number">1</span>
            </h6>
            <button type="button" class="btn btn-sm btn-danger remove-crew-btn" aria-label="Remove this crew member">
                <i class="ri-delete-bin-line me-1"></i> Remove
            </button>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="crew-trip-date-0">
                    Trip Date <span class="text-danger">*</span>
                </label>
                <input type="date"
                       class="form-control"
                       id="crew-trip-date-0"
                       name="items[0][trip_date]"
                       aria-required="true">
                <small class="form-text-helper">When does this crew member need transportation?</small>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="crew-name-0">
                    Crew Member Name <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="crew-name-0"
                       name="items[0][name]"
                       placeholder="Full name"
                       aria-required="true">
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="crew-phone-0">
                    Phone Number
                </label>
                <input type="text"
                       class="form-control"
                       id="crew-phone-0"
                       name="items[0][phone]"
                       placeholder="+123 456 7890">
                <small class="form-text-helper">Contact number for this crew member</small>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="crew-vessel-0">
                    Vessel
                </label>
                <select class="form-select vessel-select2" id="crew-vessel-0" name="items[0][vessel_id]" data-placeholder="Select vessel (optional)">
                    <option value="">Select vessel (optional)</option>
                    @foreach($vessels as $vessel)
                        <option value="{{ $vessel->id }}">{{ $vessel->name }}</option>
                    @endforeach
                </select>
                <small class="form-text-helper">Leave blank if unsure</small>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="crew-from-0">
                    From Location <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="crew-from-0"
                       name="items[0][from_location]"
                       placeholder="Pickup location"
                       aria-required="true">
                <small class="form-text-helper">Where should we pick up?</small>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="crew-to-0">
                    To Location <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="crew-to-0"
                       name="items[0][to_location]"
                       placeholder="Drop-off location"
                       aria-required="true">
                <small class="form-text-helper">Where should we drop off?</small>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>
</template>

<!-- Group Crew Item Template -->
<template id="group-crew-item-template">
    <tr class="group-crew-row" data-index="0">
        <td class="align-middle fw-medium group-crew-number text-center">1</td>
        <td>
            <input type="text" class="form-control" id="group-name-0" name="items[0][name]" placeholder="Full name" aria-required="true">
            <div class="invalid-feedback"></div>
        </td>
        <td>
            <input type="text" class="form-control" id="group-phone-0" name="items[0][phone]" placeholder="+123 456 7890">
            <div class="invalid-feedback"></div>
        </td>
        <td class="align-middle text-center">
            <button type="button" class="btn btn-sm btn-danger remove-group-crew-btn" aria-label="Remove this crew member">
                <i class="ri-delete-bin-line"></i> Remove
            </button>
        </td>
    </tr>
</template>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Custom styling for Entry Mode Selector Cards */
.mode-selector-card {
    position: relative;
    height: 100%;
    width: 100%;
}
.mode-selector-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}
.mode-selector-label {
    display: flex;
    flex-direction: column;
    padding: 1rem;
    border: 2px solid var(--vz-border-color);
    border-radius: 0.5rem;
    cursor: pointer;
    width: 100%;
    height: 100%;
    margin-bottom: 0;
    background-color: var(--vz-card-bg);
    transition: all 0.2s ease;
    white-space: normal !important;
    word-break: break-word;
    box-sizing: border-box;
}
.mode-selector-label:hover {
    border-color: var(--vz-primary);
}
.mode-selector-label .mode-check-icon {
    opacity: 0;
    transform: scale(0.6);
    transition: all 0.2s ease;
    flex-shrink: 0;
}
.mode-selector-input:checked + .mode-selector-label {
    border-color: var(--vz-primary);
    background-color: rgba(var(--vz-primary-rgb), 0.05);
}
.mode-selector-input:checked + .mode-selector-label .mode-check-icon {
    opacity: 1;
    transform: scale(1);
}
.mode-selector-input:focus-visible + .mode-selector-label {
    box-shadow: 0 0 0 0.25rem rgba(var(--vz-primary-rgb), 0.25);
}

.vessel-select2 + .select2-container {
    width: 100% !important;
}
.vessel-select2 + .select2-container .select2-selection--single {
    height: calc(1.5em + 0.94rem + 2px);
    padding: 0.47rem 0.75rem;
    border: 1px solid var(--vz-input-border, #ced4da);
    border-radius: var(--vz-border-radius, 0.25rem);
    background-color: var(--vz-input-bg, #fff);
}
.vessel-select2 + .select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 1.5;
    padding-left: 0;
    color: var(--vz-body-color, #212529);
}
.vessel-select2 + .select2-container .select2-selection--single .select2-selection__arrow {
    height: 100%;
    right: 0.5rem;
}
.vessel-select2 + .select2-container .select2-selection--single .select2-selection__placeholder {
    color: var(--vz-secondary-color, #878a99);
}
.vessel-select2 + .select2-container.select2-container--open .select2-selection--single,
.vessel-select2 + .select2-container.select2-container--focus .select2-selection--single {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--vz-primary, #405189);
}
.select2-dropdown {
    border-color: var(--vz-border-color, #e9ebec);
    background-color: var(--vz-secondary-bg, #fff);
    z-index: 1056;
}

/* Make table responsive on mobile, converting to stacked cards */
@media (max-width: 767.98px) {
    #group-crew-table thead {
        display: none;
    }
    #group-crew-table,
    #group-crew-table tbody,
    #group-crew-table tr,
    #group-crew-table td {
        display: block;
        width: 100% !important;
        box-sizing: border-box;
    }
    #group-crew-table tr {
        margin-bottom: 1rem;
        border: 1px solid var(--vz-border-color);
        border-radius: 0.5rem;
        padding: 0.75rem;
        background-color: var(--vz-card-bg);
    }
    #group-crew-table td {
        border: none !important;
        padding: 0.375rem 0 !important;
        white-space: normal !important;
    }
    #group-crew-table td.group-crew-number {
        text-align: left !important;
        font-size: 0.95rem;
        font-weight: 600;
        border-bottom: 1px solid var(--vz-border-color) !important;
        padding-bottom: 0.5rem !important;
        margin-bottom: 0.5rem;
    }
    #group-crew-table td.group-crew-number::before {
        content: 'Crew #';
    }
    #group-crew-table td:last-child {
        text-align: left !important;
        border-top: 1px dashed var(--vz-border-color) !important;
        margin-top: 0.5rem;
        padding-top: 0.75rem !important;
    }
    /* Add labels on mobile */
    #group-crew-table td:nth-child(2)::before {
        content: 'Crew Member Name *';
        display: block;
        font-weight: 600;
        margin-bottom: 0.25rem;
        font-size: 0.8125rem;
        color: var(--vz-body-color);
    }
    #group-crew-table td:nth-child(3)::before {
        content: 'Phone Number';
        display: block;
        font-weight: 600;
        margin-bottom: 0.25rem;
        font-size: 0.8125rem;
        color: var(--vz-body-color);
    }
}
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Shared State
    let currentMode = 'individual'; // 'individual' or 'group'
    const entryModeInput = document.getElementById('entry_mode');

    // Individual State
    let crewIndex = 0;
    const container = document.getElementById('crew-items-container');
    const template = document.getElementById('crew-item-template');
    const addBtn = document.getElementById('addCrewBtn');

    // Group State
    let groupCrewIndex = 0;
    const groupContainer = document.querySelector('#group-crew-table tbody');
    const groupTemplate = document.getElementById('group-crew-item-template');
    const addGroupRowBtn = document.getElementById('addGroupRowBtn');
    const addGroup5RowsBtn = document.getElementById('addGroup5RowsBtn');

    // Elements to Toggle
    const commonDetailsCard = document.getElementById('groupCommonDetailsCard');
    const groupCrewItemsContainer = document.getElementById('group-crew-items-container');
    const individualActions = document.getElementById('individual-actions');
    const groupActions = document.getElementById('group-actions');
    const modeSelectors = document.querySelectorAll('input[name="mode_selector"]');

    // Common fields
    const commonTripDate = document.getElementById('common-trip-date');
    const commonVessel = document.getElementById('common-vessel');
    const commonFrom = document.getElementById('common-from');
    const commonTo = document.getElementById('common-to');

    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('requestForm');

    function bindVesselSelect2(selectEl) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2 || !selectEl) {
            return;
        }
        const $select = jQuery(selectEl);
        if ($select.hasClass('select2-hidden-accessible')) {
            return;
        }
        $select.select2({
            placeholder: $select.data('placeholder') || 'Select vessel (optional)',
            allowClear: true,
            width: '100%',
        });
    }

    function destroyVesselSelect2(scope) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
            return;
        }
        jQuery(scope).find('.vessel-select2').addBack('.vessel-select2').each(function() {
            const $select = jQuery(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
        });
    }

    function setVesselSelectValue(selectEl, value) {
        if (!selectEl) {
            return;
        }
        selectEl.value = value || '';
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery(selectEl).hasClass('select2-hidden-accessible')) {
            jQuery(selectEl).val(value || null).trigger('change');
        }
    }

    // Initialize Mode based on old input if exists
    @if(old('entry_mode') === 'group')
        setMode('group');
        document.getElementById('modeGroup').checked = true;
    @else
        setMode('individual');
    @endif

    bindVesselSelect2(commonVessel);

    // Add first crew items on load
    addCrewItem();
    addGroupCrewItem();

    // Event Listeners for Mode Switching
    modeSelectors.forEach(radio => {
        radio.addEventListener('change', function(e) {
            const newMode = e.target.value;
            if (newMode !== currentMode) {
                if (hasDataEntered(currentMode)) {
                    if (confirm("Switching entry mode will clear the crew details entered in this form. Continue?")) {
                        setMode(newMode);
                        clearData(currentMode); // Clear the mode we are leaving
                    } else {
                        // Revert radio selection
                        document.querySelector(`input[name="mode_selector"][value="${currentMode}"]`).checked = true;
                    }
                } else {
                    setMode(newMode);
                }
            }
        });
    });

    function setMode(mode) {
        currentMode = mode;
        entryModeInput.value = mode;

        if (mode === 'individual') {
            commonDetailsCard.classList.add('d-none');
            groupCrewItemsContainer.classList.add('d-none');
            groupActions.classList.add('d-none');
            groupActions.classList.remove('d-flex');

            container.classList.remove('d-none');
            individualActions.classList.remove('d-none');

            enableRequiredFields('individual');
        } else {
            container.classList.add('d-none');
            individualActions.classList.add('d-none');

            commonDetailsCard.classList.remove('d-none');
            groupCrewItemsContainer.classList.remove('d-none');
            groupActions.classList.remove('d-none');
            groupActions.classList.add('d-flex');

            enableRequiredFields('group');
            bindVesselSelect2(commonVessel);
        }
    }

    function hasDataEntered(mode) {
        let hasData = false;
        if (mode === 'individual') {
            const items = container.querySelectorAll('.crew-item');
            if (items.length > 1) return true;

            const firstItem = items[0];
            if (firstItem) {
                const inputs = firstItem.querySelectorAll('input:not([type="hidden"]), select');
                inputs.forEach(input => {
                    if (input.value.trim() !== '') {
                        hasData = true;
                    }
                });
            }
        } else {
            const commonInputs = [commonTripDate, commonVessel, commonFrom, commonTo];
            commonInputs.forEach(input => {
                if (input.value.trim() !== '') hasData = true;
            });

            const rows = groupContainer.querySelectorAll('.group-crew-row');
            if (rows.length > 1) return true;

            const firstRow = rows[0];
            if (firstRow) {
                const inputs = firstRow.querySelectorAll('input');
                inputs.forEach(input => {
                    if (input.value.trim() !== '') hasData = true;
                });
            }
        }
        return hasData;
    }

    function clearData(modeToClear) {
        if (modeToClear === 'individual') {
            destroyVesselSelect2(container);
            container.innerHTML = '';
            crewIndex = 0;
            addCrewItem();
        } else {
            commonTripDate.value = '';
            setVesselSelectValue(commonVessel, '');
            commonFrom.value = '';
            commonTo.value = '';

            groupContainer.innerHTML = '';
            groupCrewIndex = 0;
            addGroupCrewItem();
        }
    }

    function enableRequiredFields(mode) {
        // Reset all native required attributes
        container.querySelectorAll('input, select').forEach(el => el.required = false);
        commonTripDate.required = false;
        commonFrom.required = false;
        commonTo.required = false;
        groupContainer.querySelectorAll('input').forEach(el => el.required = false);

        if (mode === 'individual') {
            container.querySelectorAll('input[name$="[trip_date]"], input[name$="[name]"], input[name$="[from_location]"], input[name$="[to_location]"]')
                     .forEach(el => el.required = true);
        } else {
            commonTripDate.required = true;
            commonFrom.required = true;
            commonTo.required = true;
            groupContainer.querySelectorAll('input[name$="[name]"]').forEach(el => el.required = true);
        }
    }

    // Individual Mode Logic
    addBtn.addEventListener('click', addCrewItem);

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-crew-btn')) {
            const crewItem = e.target.closest('.crew-item');
            if (container.querySelectorAll('.crew-item').length > 1) {
                destroyVesselSelect2(crewItem);
                crewItem.remove();
                updateCrewNumbers();
            } else {
                alert('At least one crew member is required.');
            }
        }
    });

    function addCrewItem() {
        const clone = template.content.cloneNode(true);
        const crewDiv = clone.querySelector('.crew-item');

        crewDiv.setAttribute('data-index', crewIndex);

        const inputs = crewDiv.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name) {
                input.name = input.name.replace('[0]', `[${crewIndex}]`);
            }
            if (input.id) {
                const newId = input.id.replace('-0', `-${crewIndex}`);
                input.id = newId;

                const label = crewDiv.querySelector(`label[for="${input.id.replace(`-${crewIndex}`, '-0')}"]`);
                if (label) {
                    label.setAttribute('for', newId);
                }
            }
            if (currentMode === 'individual' && ['trip_date', 'name', 'from_location', 'to_location'].some(field => input.name.includes(`[${field}]`))) {
                input.required = true;
            }
        });

        container.appendChild(clone);
        const addedItem = container.querySelector(`[data-index="${crewIndex}"]`);
        if (addedItem) {
            bindVesselSelect2(addedItem.querySelector('.vessel-select2'));
        }
        crewIndex++;
        updateCrewNumbers();

        if (currentMode === 'individual') {
            const firstInput = container.querySelector(`[data-index="${crewIndex - 1}"] input:not([type="hidden"])`);
            if (firstInput) firstInput.focus();
        }
    }

    function updateCrewNumbers() {
        const crewItems = container.querySelectorAll('.crew-item');
        crewItems.forEach((item, index) => {
            item.querySelector('.crew-number').textContent = index + 1;
        });
    }

    // Group Mode Logic
    addGroupRowBtn.addEventListener('click', () => addGroupCrewItem());
    addGroup5RowsBtn.addEventListener('click', () => {
        for(let i=0; i<5; i++) addGroupCrewItem();
    });

    groupContainer.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-group-crew-btn');
        if (removeBtn) {
            const row = removeBtn.closest('.group-crew-row');
            if (groupContainer.querySelectorAll('.group-crew-row').length > 1) {
                row.remove();
                updateGroupCrewNumbers();
            } else {
                alert('At least one crew member is required.');
            }
        }
    });

    function addGroupCrewItem() {
        const clone = groupTemplate.content.cloneNode(true);
        const row = clone.querySelector('.group-crew-row');

        row.setAttribute('data-index', groupCrewIndex);

        const inputs = row.querySelectorAll('input');
        inputs.forEach(input => {
            if (input.name) {
                input.name = input.name.replace('[0]', `[${groupCrewIndex}]`);
            }
            if (input.id) {
                input.id = input.id.replace('-0', `-${groupCrewIndex}`);
            }
            if (currentMode === 'group' && input.name.includes('[name]')) {
                input.required = true;
            }
        });

        groupContainer.appendChild(clone);
        groupCrewIndex++;
        updateGroupCrewNumbers();

        if (currentMode === 'group') {
            const firstInput = groupContainer.querySelector(`[data-index="${groupCrewIndex - 1}"] input`);
            if (firstInput) firstInput.focus();
        }
    }

    function updateGroupCrewNumbers() {
        const rows = groupContainer.querySelectorAll('.group-crew-row');
        rows.forEach((row, index) => {
            row.querySelector('.group-crew-number').textContent = index + 1;
        });
    }

    // Form Submission & Serialization
    form.addEventListener('submit', function(e) {
        if (submitBtn.disabled) {
            e.preventDefault();
            return false;
        }

        // Before submit, if in Group mode, serialize common fields into items
        if (currentMode === 'group') {
            if (!form.checkValidity()) {
                return; // Let native browser validation show
            }

            const rows = groupContainer.querySelectorAll('.group-crew-row');
            rows.forEach(row => {
                const index = row.getAttribute('data-index');

                ['trip_date', 'vessel_id', 'from_location', 'to_location'].forEach(field => {
                    let hiddenInput = row.querySelector(`input[name="items[${index}][${field}]"]`);
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = `items[${index}][${field}]`;
                        row.appendChild(hiddenInput);
                    }

                    const commonInput = document.querySelector(`[name="_common[${field}]"]`);
                    if (commonInput) {
                        hiddenInput.value = commonInput.value;
                    }
                });
            });

            // Disable individual mode inputs so they aren't submitted
            container.querySelectorAll('input, select').forEach(el => el.disabled = true);
        } else {
            // Disable group mode inputs
            document.querySelectorAll('#groupCommonDetailsCard input, #groupCommonDetailsCard select').forEach(el => el.disabled = true);
            groupContainer.querySelectorAll('input').forEach(el => el.disabled = true);
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';
    });

    // Preserve values on validation error
    @if(old('items') || old('_common'))
        const oldItems = @json(old('items', []));
        const oldCommon = @json(old('_common', []));
        const errors = @json($errors->messages());
        const oldMode = "{{ old('entry_mode', 'individual') }}";

        if (oldMode === 'individual') {
            destroyVesselSelect2(container);
            container.innerHTML = '';
            crewIndex = 0;

            if (Object.keys(oldItems).length > 0) {
                Object.keys(oldItems).forEach(idxStr => {
                    const item = oldItems[idxStr];
                    const index = parseInt(idxStr);
                    crewIndex = index;
                    addCrewItem();
                    const crewDiv = container.querySelector(`[data-index="${index}"]`);
                    if(crewDiv) {
                        Object.keys(item).forEach(key => {
                            const input = crewDiv.querySelector(`[name="items[${index}][${key}]"]`);
                            if (input && item[key] !== null) {
                                if (key === 'vessel_id') {
                                    setVesselSelectValue(input, item[key]);
                                } else {
                                    input.value = item[key];
                                }
                            }
                        });
                    }
                });
                crewIndex++; // move past the last index
            } else {
                addCrewItem();
            }
        } else {
            // Group Mode Restoration
            if (oldCommon.trip_date) commonTripDate.value = oldCommon.trip_date;
            if (oldCommon.vessel_id) setVesselSelectValue(commonVessel, oldCommon.vessel_id);
            if (oldCommon.from_location) commonFrom.value = oldCommon.from_location;
            if (oldCommon.to_location) commonTo.value = oldCommon.to_location;

            groupContainer.innerHTML = '';
            groupCrewIndex = 0;

            if (Object.keys(oldItems).length > 0) {
                Object.keys(oldItems).forEach(idxStr => {
                    const item = oldItems[idxStr];
                    const index = parseInt(idxStr);
                    groupCrewIndex = index;
                    addGroupCrewItem();

                    const row = groupContainer.querySelector(`[data-index="${index}"]`);
                    if(row) {
                        if (item.name) {
                            const nameInput = row.querySelector(`[name="items[${index}][name]"]`);
                            if (nameInput) nameInput.value = item.name;
                        }
                        if (item.phone) {
                            const phoneInput = row.querySelector(`[name="items[${index}][phone]"]`);
                            if (phoneInput) phoneInput.value = item.phone;
                        }
                    }
                });
                groupCrewIndex++;
            } else {
                addGroupCrewItem();
            }
        }

        // Map server validation errors to correct fields
        Object.keys(errors).forEach(key => {
            const match = key.match(/^items\.(\d+)\.(.+)$/);
            if (match) {
                const idx = parseInt(match[1]);
                const field = match[2];

                if (oldMode === 'individual') {
                    const input = container.querySelector(`[name="items[${idx}][${field}]"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.parentElement.querySelector('.invalid-feedback');
                        if (feedback) {
                            feedback.textContent = errors[key][0];
                            feedback.style.display = 'block';
                        }
                    }
                } else {
                    const commonFields = ['trip_date', 'vessel_id', 'from_location', 'to_location'];
                    if (commonFields.includes(field)) {
                        let inputId = '';
                        if (field === 'trip_date') inputId = 'common-trip-date';
                        else if (field === 'vessel_id') inputId = 'common-vessel';
                        else if (field === 'from_location') inputId = 'common-from';
                        else if (field === 'to_location') inputId = 'common-to';

                        const input = document.getElementById(inputId);
                        if (input) {
                            input.classList.add('is-invalid');
                            const feedback = input.parentElement.querySelector('.invalid-feedback');
                            if (feedback) {
                                feedback.textContent = errors[key][0];
                                feedback.style.display = 'block';
                            }
                        }
                    } else {
                        const input = groupContainer.querySelector(`[name="items[${idx}][${field}]"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                            const feedback = input.parentElement.querySelector('.invalid-feedback');
                            if (feedback) {
                                feedback.textContent = errors[key][0];
                                feedback.style.display = 'block';
                            }
                        }
                    }
                }
            }
        });
    @endif
});
</script>
@endpush