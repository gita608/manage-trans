@extends('layouts.app')

@section('title', 'Trips | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Trips</h4>

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

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">All Trips</h5>
                    <a href="{{ route('trips.create') }}" class="btn btn-success">
                        <i class="ri-add-line align-middle me-1"></i> Add New Trip
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row g-2 mb-3">
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label">Driver</label>
                        <select id="filter-driver" class="form-select">
                            <option value="">All Drivers</option>
                            @foreach($drivers as $d)
                                <option value="{{ $d->name }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label">Vessel</label>
                        <select id="filter-vessel" class="form-select">
                            <option value="">All Vessels</option>
                            @foreach($vessels as $v)
                                <option value="{{ $v->name }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" id="filter-date" class="form-control">
                    </div>
                    <div class="col-auto align-self-end d-flex gap-2">
                        <button type="button" id="filter-apply" class="btn btn-primary"><i class="ri-search-line me-1"></i> Apply</button>
                        <button type="button" id="filter-reset" class="btn btn-light">Reset</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="trips-table" class="table table-nowrap align-middle mb-0 @if(!$trips->isEmpty()) datatable @endif">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Crew Name</th>
                                <th scope="col">Driver Name</th>
                                <th scope="col">Vessel Name</th>
                                <th scope="col">Pick-up Time</th>
                                <th scope="col">From</th>
                                <th scope="col">To</th>
                                <th scope="col">Crew Phone</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($trips as $trip)
                                <tr>
                                    <td>{{ $trip->trip_date->format('M d, Y') }}</td>
                                    <td>{{ $trip->crew_name }}</td>
                                    <td>{{ $trip->driver->name }}</td>
                                    <td>{{ $trip->vessel->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($trip->pick_up_time)->format('h:i A') }}</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $trip->from_location }}">
                                            {{ $trip->from_location }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $trip->to_location }}">
                                            {{ $trip->to_location }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($trip->crew_phone)
                                            {{ $trip->crew_phone }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $trip->getStatusBadgeClass() }}">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('trips.edit', $trip) }}" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this trip?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <p class="text-muted mb-0">No trips found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(!$trips->isEmpty())
                    @include('partials.datatable', ['selector' => '#trips-table'])
                @endif

                @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var driverSel = document.getElementById('filter-driver');
                        var vesselSel = document.getElementById('filter-vessel');
                        var dateInp = document.getElementById('filter-date');
                        var applyBtn = document.getElementById('filter-apply');
                        var resetBtn = document.getElementById('filter-reset');

                        // Set initial values from query parameters
                        var urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('driver')) {
                            driverSel.value = urlParams.get('driver');
                        }
                        if (urlParams.has('vessel')) {
                            vesselSel.value = urlParams.get('vessel');
                        }
                        if (urlParams.has('date')) {
                            dateInp.value = urlParams.get('date');
                        }

                        function applyFilters() {
                            var params = new URLSearchParams();
                            if (driverSel.value) {
                                params.set('driver', driverSel.value);
                            }
                            if (vesselSel.value) {
                                params.set('vessel', vesselSel.value);
                            }
                            if (dateInp.value) {
                                params.set('date', dateInp.value);
                            }
                            window.location.href = '{{ route('trips.index') }}?' + params.toString();
                        }

                        applyBtn.addEventListener('click', applyFilters);

                        resetBtn.addEventListener('click', function () {
                            window.location.href = '{{ route('trips.index') }}';
                        });
                    });
                </script>
                @endpush
            </div>
        </div>
    </div>
</div>
@endsection

