@php
    $item = $item ?? null;
    $fieldPrefix = "items[{$index}]";
    $disabled = empty($canEdit) ? 'disabled' : '';
@endphp
<div class="crew-item border rounded p-3 mb-3" data-index="{{ $index }}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Crew #<span class="crew-number">{{ is_numeric($index) ? ((int) $index + 1) : 1 }}</span></h6>
        @if($canEdit)
            <button type="button" class="btn btn-sm btn-danger remove-crew-btn">
                <i class="ri-delete-bin-line"></i> Remove
            </button>
        @endif
    </div>

    @if($item?->id)
        <input type="hidden" name="{{ $fieldPrefix }}[id]" value="{{ $item->id }}" {{ $disabled }}>
    @endif

    <div class="row g-3">
        <div class="col-md-4 col-lg-3">
            <label class="form-label">Trip Date</label>
            <input type="date" class="form-control" name="{{ $fieldPrefix }}[trip_date]"
                   value="{{ old($fieldPrefix . '.trip_date', $item?->trip_date?->format('Y-m-d')) }}" {{ $disabled }}>
        </div>
        <div class="col-md-4 col-lg-3">
            <label class="form-label">Pickup Time</label>
            <input type="time" class="form-control" name="{{ $fieldPrefix }}[pick_up_time]"
                   value="{{ old($fieldPrefix . '.pick_up_time', $item?->pick_up_time ? \Carbon\Carbon::parse($item->pick_up_time)->format('H:i') : '') }}" {{ $disabled }}>
        </div>
        <div class="col-md-4 col-lg-3">
            <label class="form-label">Crew Name</label>
            <input type="text" class="form-control" name="{{ $fieldPrefix }}[name]"
                   value="{{ old($fieldPrefix . '.name', $item?->name) }}" {{ $disabled }}>
        </div>
        <div class="col-md-4 col-lg-3">
            <label class="form-label">Driver</label>
            <select class="form-select" name="{{ $fieldPrefix }}[driver_id]" {{ $disabled }}>
                <option value="">Unassigned</option>
                @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" @selected((string) old($fieldPrefix . '.driver_id', $item?->driver_id) === (string) $driver->id)>{{ $driver->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 col-lg-3">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="{{ $fieldPrefix }}[phone]"
                   value="{{ old($fieldPrefix . '.phone', $item?->phone) }}" {{ $disabled }}>
        </div>
        <div class="col-md-4 col-lg-3">
            <label class="form-label">Phone 2</label>
            <input type="text" class="form-control" name="{{ $fieldPrefix }}[phone_2]"
                   value="{{ old($fieldPrefix . '.phone_2', $item?->phone_2) }}" {{ $disabled }}>
        </div>
        <div class="col-md-4 col-lg-3">
            <label class="form-label">Address</label>
            <input type="text" class="form-control" name="{{ $fieldPrefix }}[address]"
                   value="{{ old($fieldPrefix . '.address', $item?->address) }}" {{ $disabled }}>
        </div>
        <div class="col-md-4 col-lg-3">
            <label class="form-label">Flight Number</label>
            <input type="text" class="form-control" name="{{ $fieldPrefix }}[flight_number]"
                   value="{{ old($fieldPrefix . '.flight_number', $item?->flight_number) }}" {{ $disabled }}>
        </div>
        <div class="col-md-6">
            <label class="form-label">From Location</label>
            <input type="text" class="form-control" name="{{ $fieldPrefix }}[from_location]"
                   value="{{ old($fieldPrefix . '.from_location', $item?->from_location) }}" {{ $disabled }}>
        </div>
        <div class="col-md-6">
            <label class="form-label">To Location</label>
            <input type="text" class="form-control" name="{{ $fieldPrefix }}[to_location]"
                   value="{{ old($fieldPrefix . '.to_location', $item?->to_location) }}" {{ $disabled }}>
        </div>
        @if($isImage && $item?->vessel_name_raw)
            <div class="col-md-6">
                <label class="form-label">OCR Vessel Text</label>
                <input type="text" class="form-control" value="{{ $item->vessel_name_raw }}" readonly>
            </div>
        @endif
        <div class="col-md-6">
            <label class="form-label">Vessel</label>
            <select class="form-select" name="{{ $fieldPrefix }}[vessel_id]" {{ $disabled }}>
                <option value="">Select Vessel</option>
                @foreach($vessels as $vessel)
                    <option value="{{ $vessel->id }}" @selected((string) old($fieldPrefix . '.vessel_id', $item?->vessel_id) === (string) $vessel->id)>{{ $vessel->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Remarks</label>
            <textarea class="form-control" name="{{ $fieldPrefix }}[remarks]" rows="2" {{ $disabled }}>{{ old($fieldPrefix . '.remarks', $item?->remarks) }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Sub Remark</label>
            <input type="text" class="form-control" name="{{ $fieldPrefix }}[sub_remark]"
                   value="{{ old($fieldPrefix . '.sub_remark', $item?->sub_remark) }}" {{ $disabled }}>
        </div>
    </div>
</div>
