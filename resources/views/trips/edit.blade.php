@extends('layouts.app')

@section('title', 'Edit Trip | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Edit Trip</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trips</a></li>
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

                <form method="POST" action="{{ route('trips.update', $trip) }}">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="partner_id" class="form-label fw-semibold">Partner</label>
                                <select class="form-select @error('partner_id') is-invalid @enderror" id="partner_id" name="partner_id">
                                    <option value="">Select Partner</option>
                                    @foreach($partners as $partner)
                                        <option value="{{ $partner->id }}" {{ old('partner_id', $trip->partner_id) == $partner->id ? 'selected' : '' }}>
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
                            $tripDateDefault = $trip->trip_date->format('Y-m-d');
                            $crews = old('crews', $trip->crews->toArray());
                            if (empty($crews)) {
                                        $crews = [['name' => '', 'driver_id' => '', 'trip_date' => $tripDateDefault, 'vessel_id' => '', 'pick_up_time' => '', 'from_location' => '', 'to_location' => '', 'remarks' => '', 'sub_remark' => '', 'phone' => '', 'phone_2' => '', 'address' => '']];
                            }
                        @endphp
                        @foreach($crews as $index => $crew)
                                    <tr class="crew-row" data-index="{{ $index }}">
                                        <td class="text-center fw-semibold">{{ $index + 1 }}</td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm @error('crews.'.$index.'.trip_date') is-invalid @enderror"
                                                   name="crews[{{ $index }}][trip_date]"
                                                   value="{{ is_array($crew) ? ($crew['trip_date'] ?? $tripDateDefault) : ($crew->trip_date ?? $tripDateDefault) }}"
                                                   required>
                                            @error('crews.'.$index.'.trip_date')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="crews[{{ $index }}][driver_id]">
                                                <option value="">Assign Later</option>
                                                @foreach($drivers as $driver)
                                                    <option value="{{ $driver->id }}" 
                                                        {{ (is_array($crew) ? ($crew['driver_id'] ?? $trip->driver_id) : ($crew->driver_id ?? $trip->driver_id)) == $driver->id ? 'selected' : '' }}>
                                                        {{ $driver->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm @error('crews.'.$index.'.vessel_id') is-invalid @enderror" 
                                                    name="crews[{{ $index }}][vessel_id]" required>
                                                <option value="">Select</option>
                                                @foreach($vessels as $vessel)
                                                    <option value="{{ $vessel->id }}" 
                                                        {{ (is_array($crew) ? ($crew['vessel_id'] ?? '') : $crew->vessel_id) == $vessel->id ? 'selected' : '' }}>
                                                        {{ $vessel->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('crews.'.$index.'.vessel_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="time" class="form-control form-control-sm @error('crews.'.$index.'.pick_up_time') is-invalid @enderror" 
                                                   name="crews[{{ $index }}][pick_up_time]" 
                                                   value="{{ is_array($crew) ? ($crew['pick_up_time'] ?? '') : $crew->pick_up_time }}" required>
                                            @error('crews.'.$index.'.pick_up_time')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm @error('crews.'.$index.'.flight_number') is-invalid @enderror" 
                                                   name="crews[{{ $index }}][flight_number]" 
                                                   value="{{ is_array($crew) ? ($crew['flight_number'] ?? '') : ($crew->flight_number ?? '') }}" 
                                                   placeholder="Flight number">
                                            @error('crews.'.$index.'.flight_number')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm @error('crews.'.$index.'.name') is-invalid @enderror" 
                                                   name="crews[{{ $index }}][name]" 
                                                   value="{{ is_array($crew) ? ($crew['name'] ?? '') : $crew->name }}" 
                                                   placeholder="Enter name" required>
                                            @error('crews.'.$index.'.name')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm @error('crews.'.$index.'.phone') is-invalid @enderror" 
                                                   name="crews[{{ $index }}][phone]" 
                                                   value="{{ is_array($crew) ? ($crew['phone'] ?? '') : $crew->phone }}" 
                                                   placeholder="Contact number">
                                            @error('crews.'.$index.'.phone')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm @error('crews.'.$index.'.phone_2') is-invalid @enderror" 
                                                   name="crews[{{ $index }}][phone_2]" 
                                                   value="{{ is_array($crew) ? ($crew['phone_2'] ?? '') : ($crew->phone_2 ?? '') }}" 
                                                   placeholder="Contact number">
                                            @error('crews.'.$index.'.phone_2')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm @error('crews.'.$index.'.from_location') is-invalid @enderror" 
                                                   name="crews[{{ $index }}][from_location]" 
                                                   value="{{ is_array($crew) ? ($crew['from_location'] ?? '') : $crew->from_location }}" 
                                                   placeholder="From" required>
                                            @error('crews.'.$index.'.from_location')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm @error('crews.'.$index.'.to_location') is-invalid @enderror" 
                                                   name="crews[{{ $index }}][to_location]" 
                                                   value="{{ is_array($crew) ? ($crew['to_location'] ?? '') : $crew->to_location }}" 
                                                   placeholder="To" required>
                                            @error('crews.'.$index.'.to_location')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                             <input type="text" class="form-control form-control-sm @error('crews.'.$index.'.remarks') is-invalid @enderror" 
                                                    name="crews[{{ $index }}][remarks]" 
                                                    value="{{ is_array($crew) ? ($crew['remarks'] ?? '') : ($crew->remarks ?? '') }}" 
                                                    placeholder="Remarks">
                                             @error('crews.'.$index.'.remarks')
                                                 <div class="invalid-feedback d-block">{{ $message }}</div>
                                             @enderror
                                         </td>
                                         <td>
                                             <input type="text" class="form-control form-control-sm @error('crews.'.$index.'.sub_remark') is-invalid @enderror" 
                                                    name="crews[{{ $index }}][sub_remark]" 
                                                    value="{{ is_array($crew) ? ($crew['sub_remark'] ?? '') : ($crew->sub_remark ?? '') }}" 
                                                    placeholder="Sub Remark">
                                             @error('crews.'.$index.'.sub_remark')
                                                 <div class="invalid-feedback d-block">{{ $message }}</div>
                                             @enderror
                                         </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger remove-row-btn" title="Remove row">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                        @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-success" type="submit">Update Trip</button>
                        <a href="{{ route('trips.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addCrewBtn = document.getElementById('add-crew-row-btn');
        const crewsContainer = document.getElementById('crews-container');
        const defaultTripDate = '{{ $trip->trip_date->format('Y-m-d') }}';
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
                    <input type="date" class="form-control form-control-sm" name="crews[${index}][trip_date]" value="${defaultTripDate}" required>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="crews[${index}][driver_id]">
                        ${driverOptions}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="crews[${index}][vessel_id]" required>
                        ${vesselOptions}
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
            return row;
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

        // Handle remove row button
        crewsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-row-btn')) {
                const btn = e.target.closest('.remove-row-btn');
                if (!btn.disabled) {
                    const row = btn.closest('.crew-row');
                    const currentCount = crewsContainer.querySelectorAll('.crew-row').length;
                    if (currentCount > 1) {
                        row.remove();
                        updateRowNumbers();
                    updateRemoveButtons();
                    }
                }
            }
        });

        // Initialize
        updateRemoveButtons();
    });
</script>
@endpush

