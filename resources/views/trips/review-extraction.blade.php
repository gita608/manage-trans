@extends('layouts.app')

@section('title', 'Review Extracted Trips | ' . config('app.name'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-transparent shadow-none">
            <h4 class="mb-sm-0 text-primary fw-bold"><i class="ri-magic-line me-2"></i>Review Extracted Trips</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trips</a></li>
                    <li class="breadcrumb-item active">Review Extraction</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-xl-12">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-primary-subtle border-bottom border-primary-subtle p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1 text-primary"><i class="ri-file-list-3-line me-2"></i>Verify Data Before Saving</h5>
                        <p class="text-muted mb-0 fs-13">Please review the extracted data below. You can edit any field before saving.</p>
                    </div>
                    <div>
                        <span class="badge bg-primary fs-12 rounded-pill px-3 py-2 shadow-sm">
                            <i class="ri-stack-line me-1"></i> {{ count($parsedData) }} trips found
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <form action="{{ route('trips.store-bulk') }}" method="POST" id="review-form">
                    @csrf
                    
                    <div class="p-4 bg-light-subtle border-bottom">
                        <div class="alert alert-info border-0 shadow-sm rounded-3 mb-0 d-flex align-items-start" role="alert">
                            <div class="flex-shrink-0 me-3">
                                <i class="ri-information-fill fs-24 text-info"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading fw-bold mb-1">How Grouping Works</h6>
                                <p class="mb-0 text-muted">Rows with the same <strong>Driver</strong> and <strong>Date</strong> will be automatically grouped into a single Trip. Uncheck any rows you wish to discard.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0 custom-table">
                            <thead class="bg-light text-muted text-uppercase fs-11">
                                <tr>
                                    <th scope="col" class="ps-4" style="width: 50px;">
                                        <div class="form-check form-check-primary">
                                            <input class="form-check-input" type="checkbox" id="checkAll" checked>
                                        </div>
                                    </th>
                                    <th scope="col" style="width: 220px;">Driver</th>
                                    <th scope="col" style="width: 220px;">Vessel</th>
                                    <th scope="col" style="width: 150px;">Date</th>
                                    <th scope="col" style="width: 130px;">Pick-up</th>
                                    <th scope="col" style="width: 120px;">Flight No</th>
                                    <th scope="col" style="width: 300px;">Crew Name</th>
                                    <th scope="col" style="width: 220px;">Route</th>
                                    <th scope="col">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach($parsedData as $index => $row)
                                    <tr class="transition-hover">
                                        <td class="ps-4">
                                            <div class="form-check form-check-primary">
                                                <input class="form-check-input row-check" type="checkbox" name="trips[{{ $index }}][selected]" value="1" checked>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm input-group-flat">
                                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-user-line"></i></span>
                                                <select name="trips[{{ $index }}][driver_id]" class="form-select form-select-sm border-start-0 ps-0 driver-select" required>
                                                    <option value="">Select Driver</option>
                                                    @foreach($drivers as $driver)
                                                        <option value="{{ $driver->id }}">
                                                            {{ $driver->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm input-group-flat">
                                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-ship-line"></i></span>
                                                <select name="trips[{{ $index }}][vessel_id]" class="form-select form-select-sm border-start-0 ps-0 vessel-select" required>
                                                    <option value="">Select Vessel</option>
                                                    @php $vesselFound = false; @endphp
                                                    @foreach($vessels as $vessel)
                                                        <option value="{{ $vessel->id }}" {{ $row['vessel_id'] == $vessel->id ? 'selected' : '' }}>
                                                            {{ $vessel->name }}
                                                        </option>
                                                        @if($row['vessel_id'] == $vessel->id) @php $vesselFound = true; @endphp @endif
                                                    @endforeach

                                                    @if(!$vesselFound && !empty($row['vessel_name']))
                                                        <option value="new:{{ $row['vessel_name'] }}" selected class="text-primary fw-bold">
                                                            + Create: {{ $row['vessel_name'] }}
                                                        </option>
                                                    @endif
                                                </select>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="date" name="trips[{{ $index }}][trip_date]" class="form-control form-control-sm border-light bg-light-subtle" value="{{ $row['trip_date'] }}" required>
                                        </td>
                                        <td>
                                            <input type="time" name="trips[{{ $index }}][pick_up_time]" class="form-control form-control-sm border-light bg-light-subtle" value="{{ $row['pick_up_time'] }}" required>
                                        </td>
                                        <td>
                                            <input type="text" name="trips[{{ $index }}][flight_number]" class="form-control form-control-sm border-light bg-light-subtle" value="" placeholder="Flight No">
                                        </td>
                                        <td>
                                            <textarea name="trips[{{ $index }}][crew_name]" class="form-control form-control-sm border-light bg-light-subtle" rows="2" required style="resize: vertical; min-height: 38px; font-size: 13px;">{{ $row['crew_name'] }}</textarea>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <div class="input-group input-group-sm input-group-flat">
                                                    <span class="input-group-text bg-light border-end-0 text-muted px-2"><i class="ri-map-pin-line fs-10"></i></span>
                                                    <input type="text" name="trips[{{ $index }}][from_location]" class="form-control form-control-sm border-start-0 ps-1" value="{{ $row['from_location'] }}" placeholder="From" required>
                                                </div>
                                                <div class="text-center text-muted" style="line-height: 0.5;"><i class="ri-arrow-down-s-line fs-12"></i></div>
                                                <div class="input-group input-group-sm input-group-flat">
                                                    <span class="input-group-text bg-light border-end-0 text-muted px-2"><i class="ri-map-pin-range-line fs-10"></i></span>
                                                    <input type="text" name="trips[{{ $index }}][to_location]" class="form-control form-control-sm border-start-0 ps-1" value="{{ $row['to_location'] }}" placeholder="To" required>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="trips[{{ $index }}][remarks]" class="form-control form-control-sm border-light bg-light-subtle" value="{{ $row['remarks'] }}" placeholder="Remarks">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer bg-white border-top p-4">
                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('trips.index') }}" class="btn btn-soft-secondary px-4">
                                <i class="ri-close-line me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success px-4 shadow-sm">
                                <i class="ri-save-line me-1"></i> Save Selected Trips
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .input-group-flat .form-control:focus, 
    .input-group-flat .form-select:focus {
        box-shadow: none;
        border-color: var(--vz-primary);
    }
    .transition-hover {
        transition: all 0.2s ease;
    }
    .transition-hover:hover {
        background-color: var(--vz-light) !important;
    }
    .form-control-sm, .form-select-sm {
        font-size: 0.8125rem;
    }
    /* Custom scrollbar for textarea */
    textarea::-webkit-scrollbar {
        width: 6px;
    }
    textarea::-webkit-scrollbar-track {
        background: #f1f1f1; 
    }
    textarea::-webkit-scrollbar-thumb {
        background: #d1d5db; 
        border-radius: 3px;
    }
    textarea::-webkit-scrollbar-thumb:hover {
        background: #9ca3af; 
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check all functionality
        const checkAll = document.getElementById('checkAll');
        const rowChecks = document.querySelectorAll('.row-check');

        checkAll.addEventListener('change', function() {
            rowChecks.forEach(check => {
                check.checked = this.checked;
                // Optional: Highlight row when checked
                const row = check.closest('tr');
                if (this.checked) {
                    row.classList.add('table-active');
                } else {
                    row.classList.remove('table-active');
                }
            });
        });

        // Update "Check All" state when individual rows change
        rowChecks.forEach(check => {
            check.addEventListener('change', function() {
                const row = this.closest('tr');
                if (this.checked) {
                    row.classList.add('table-active');
                } else {
                    row.classList.remove('table-active');
                }

                if (!this.checked) {
                    checkAll.checked = false;
                } else {
                    const allChecked = Array.from(rowChecks).every(c => c.checked);
                    checkAll.checked = allChecked;
                }
            });
        });
    });
</script>
@endpush
@endsection
