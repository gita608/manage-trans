@extends('layouts.partner')

@section('title', 'Edit Request - Partner Portal')

@section('content')
@include('partner.partials.page-header', [
    'title' => 'Edit Request',
    'subtitle' => 'Update crew transportation details for ' . $partnerRequest->request_reference . '.',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('partner.dashboard')],
        ['label' => 'My Requests', 'url' => route('partner.requests.index')],
        ['label' => $partnerRequest->request_reference, 'url' => route('partner.requests.show', $partnerRequest)],
        ['label' => 'Edit']
    ]
])

<form action="{{ route('partner.requests.update', $partnerRequest) }}" method="POST" id="requestForm">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-12">
            <div class="card partner-page-card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-start gap-2 flex-grow-1 flex-text-safe">
                            <span class="partner-card-header-icon"><i class="ri-ship-line fs-5" aria-hidden="true"></i></span>
                            <div class="min-width-0">
                                <h5 class="card-title mb-0 text-break-safe">{{ $partnerRequest->request_reference }}</h5>
                                <p class="text-muted mb-0 mt-1 small">
                                    Fields marked with <span class="text-danger">*</span> are required.
                                </p>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            @include('partner.partials.status-badge', ['status' => 'pending', 'withIcon' => false])
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="crew-items-container">
                        <!-- Crew items will be populated here -->
                    </div>

                    <div class="mt-4">
                        <button type="button" class="btn btn-success btn-touch" id="addCrewBtn">
                            <i class="ri-add-line align-middle me-1"></i> Add Another Crew Member
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
                <a href="{{ route('partner.requests.show', $partnerRequest) }}" class="btn btn-light btn-touch">
                    <i class="ri-close-line align-middle me-1"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-touch" id="submitBtn">
                    <i class="ri-save-line align-middle me-1"></i> Save Changes
                </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Crew Item Template -->
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

        <input type="hidden" name="items[0][id]" value="">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="crew-trip-date-0">
                    Trip Date <span class="text-danger">*</span>
                </label>
                <input type="date"
                       class="form-control"
                       id="crew-trip-date-0"
                       name="items[0][trip_date]"
                       required
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
                       required
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
                       required
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
                       required
                       aria-required="true">
                <small class="form-text-helper">Where should we drop off?</small>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>
</template>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
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
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let crewIndex = 0;
    const container = document.getElementById('crew-items-container');
    const template = document.getElementById('crew-item-template');
    const addBtn = document.getElementById('addCrewBtn');
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
        jQuery(scope).find('.vessel-select2').each(function() {
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

    // Load existing items
    const existingItems = @json($partnerRequest->items);

    if (existingItems.length > 0) {
        existingItems.forEach(item => {
            addCrewItem(item);
        });
    } else {
        addCrewItem();
    }

    // Add crew button
    addBtn.addEventListener('click', () => addCrewItem());

    // Delegate remove button clicks
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

    // Prevent double submission
    form.addEventListener('submit', function(e) {
        if (submitBtn.disabled) {
            e.preventDefault();
            return false;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';
    });

    function addCrewItem(itemData = null) {
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
        });

        if (itemData) {
            const idInput = crewDiv.querySelector('input[name$="[id]"]');
            if (idInput) {
                idInput.value = itemData.id || '';
            }

            const fields = ['trip_date', 'name', 'phone', 'from_location', 'to_location', 'vessel_id'];

            fields.forEach(field => {
                const input = crewDiv.querySelector(`[name$="[${field}]"]`);
                if (input && itemData[field] !== null && itemData[field] !== undefined) {
                    input.value = itemData[field];
                }
            });
        }

        container.appendChild(clone);
        const addedItem = container.querySelector(`[data-index="${crewIndex}"]`);
        if (addedItem) {
            const vesselSelect = addedItem.querySelector('.vessel-select2');
            bindVesselSelect2(vesselSelect);
            if (itemData && itemData.vessel_id) {
                setVesselSelectValue(vesselSelect, itemData.vessel_id);
            }
        }
        crewIndex++;
        updateCrewNumbers();
    }

    function updateCrewNumbers() {
        const crewItems = container.querySelectorAll('.crew-item');
        crewItems.forEach((item, index) => {
            item.querySelector('.crew-number').textContent = index + 1;
        });
    }

    // Preserve values on validation error
    @if(old('items'))
        const oldItems = @json(old('items'));
        const errors = @json($errors->messages());

        destroyVesselSelect2(container);
        container.innerHTML = '';
        crewIndex = 0;

        oldItems.forEach((item, index) => {
            addCrewItem(item);
        });

        Object.keys(errors).forEach(key => {
            const match = key.match(/^items\.(\d+)\.(.+)$/);
            if (match) {
                const idx = parseInt(match[1]);
                const field = match[2];
                const input = container.querySelector(`[name="items[${idx}][${field}]"]`);
                if (input) {
                    input.classList.add('is-invalid');
                    const feedback = input.parentElement.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = errors[key][0];
                        feedback.style.display = 'block';
                    }
                }
            }
        });
    @endif
});
</script>
@endpush
