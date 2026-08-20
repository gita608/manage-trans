@extends('layouts.partner')

@section('title', 'Edit Request - Partner Portal')

@section('content')
<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Edit Request</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('partner.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('partner.requests.index') }}">My Requests</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('partner.requests.show', $partnerRequest) }}">{{ $partnerRequest->request_reference }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('partner.requests.update', $partnerRequest) }}" method="POST" id="requestForm">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ $partnerRequest->request_reference }}</h5>
                        <span class="badge bg-warning-subtle text-warning">Editing</span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="crew-items-container">
                        <!-- Crew items will be populated here -->
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-success" id="addCrewBtn">
                            <i class="ri-add-line align-middle me-1"></i> Add Crew
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="text-end">
                <a href="{{ route('partner.requests.show', $partnerRequest) }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="ri-save-line align-middle me-1"></i> Update Request
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Crew Item Template -->
<template id="crew-item-template">
    <div class="crew-item border rounded p-3 mb-3" data-index="0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Crew #<span class="crew-number">1</span></h6>
            <button type="button" class="btn btn-sm btn-danger remove-crew-btn">
                <i class="ri-delete-bin-line"></i> Remove
            </button>
        </div>

        <input type="hidden" name="items[0][id]" value="">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Trip Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="items[0][trip_date]" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="items[0][name]" placeholder="Crew member name" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="items[0][phone]" placeholder="Phone number">
            </div>

            <div class="col-md-6">
                <label class="form-label">Vessel</label>
                <select class="form-select" name="items[0][vessel_id]">
                    <option value="">Not sure / Manage Trans will assign</option>
                    @foreach($vessels as $vessel)
                        <option value="{{ $vessel->id }}">{{ $vessel->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">From Location <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="items[0][from_location]" placeholder="Pickup location" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">To Location <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="items[0][to_location]" placeholder="Drop-off location" required>
            </div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let crewIndex = 0;
    const container = document.getElementById('crew-items-container');
    const template = document.getElementById('crew-item-template');
    const addBtn = document.getElementById('addCrewBtn');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('requestForm');

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
                crewItem.remove();
                updateCrewNumbers();
            } else {
                alert('At least one crew member is required.');
            }
        }
    });

    // Disable submit button after submission
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';
    });

    function addCrewItem(itemData = null) {
        const clone = template.content.cloneNode(true);
        const crewDiv = clone.querySelector('.crew-item');
        
        // Update index
        crewDiv.setAttribute('data-index', crewIndex);
        
        // Update all input/select names
        const inputs = crewDiv.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name) {
                input.name = input.name.replace('[0]', `[${crewIndex}]`);
            }
        });

        // Populate data if editing existing item
        if (itemData) {
            // Set hidden ID field
            const idInput = crewDiv.querySelector('input[name$="[id]"]');
            if (idInput) {
                idInput.value = itemData.id || '';
            }

            // Populate Partner-editable fields only
            const fields = ['trip_date', 'name', 'phone', 'from_location', 'to_location', 'vessel_id'];
            
            fields.forEach(field => {
                const input = crewDiv.querySelector(`[name$="[${field}]"]`);
                if (input && itemData[field] !== null && itemData[field] !== undefined) {
                    input.value = itemData[field];
                }
            });
        }

        container.appendChild(clone);
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
        // Clear default items
        container.innerHTML = '';
        crewIndex = 0;
        
        // Add items from old input
        oldItems.forEach((item, index) => {
            addCrewItem(item);
        });
    @endif
});
</script>
@endpush
