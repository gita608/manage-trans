@extends('layouts.app')

@section('title', 'Driver Details | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Driver Details</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('drivers.index') }}">Drivers</a></li>
                    <li class="breadcrumb-item active">Details</li>
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
                    <h5 class="card-title mb-0">Driver Information</h5>
                    <div>
                        <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-primary">
                            <i class="ri-pencil-line align-middle me-1"></i> Edit
                        </a>
                        <form action="{{ route('drivers.destroy', $driver) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this driver?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="ri-delete-bin-line align-middle me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-4">
                        @if($driver->photo)
                            <img src="{{ asset('storage/' . $driver->photo) }}" alt="{{ $driver->name }}" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                        @else
                            <div class="bg-primary-subtle d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 300px; height: 300px;">
                                <span class="text-primary display-1">{{ substr($driver->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th width="200">Name:</th>
                                    <td>{{ $driver->name }}</td>
                                </tr>
                                <tr>
                                    <th>Type:</th>
                                    <td>
                                        <span class="badge bg-{{ $driver->type == 1 ? 'primary' : 'info' }}">
                                            {{ $driver->getTypeLabel() }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>
                                        @if($driver->email)
                                            {{ $driver->email }}
                                            <span class="badge bg-success ms-2">API Enabled</span>
                                        @else
                                            <span class="text-muted">Not set</span>
                                            <span class="badge bg-warning ms-2">API Disabled</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>License Number:</th>
                                    <td>{{ $driver->license_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Contact:</th>
                                    <td>{{ $driver->contact ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Age:</th>
                                    <td>{{ $driver->age ? $driver->age . ' years' : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Vehicle Name:</th>
                                    <td>{{ $driver->vehicle_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Vehicle Brand:</th>
                                    <td>{{ $driver->vehicle_brand ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Vehicle Information:</th>
                                    <td>{{ $driver->vehicle_info ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At:</th>
                                    <td>{{ $driver->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated:</th>
                                    <td>{{ $driver->updated_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Location Section -->
                @if($driver->latestLocation)
                    <div class="border-top pt-4 mt-4">
                        <h5 class="card-title mb-3">
                            <i class="ri-map-pin-line align-middle me-1"></i> Current Location
                        </h5>
                        <div class="row">
                            <div class="col-md-12">
                                <div id="driverMap" style="height: 400px; width: 100%; border-radius: 8px;"></div>
                                <div class="mt-3">
                                    <p class="text-muted mb-1">
                                        <strong>Last Updated:</strong> {{ $driver->latestLocation->updated_at->format('M d, Y h:i A') }}
                                        <span class="badge bg-{{ $driver->latestLocation->updated_at->diffInMinutes(now()) < 5 ? 'success' : 'warning' }} ms-2">
                                            {{ $driver->latestLocation->updated_at->diffForHumans() }}
                                        </span>
                                    </p>
                                    <p class="text-muted mb-0">
                                        <strong>Coordinates:</strong> {{ number_format($driver->latestLocation->latitude, 6) }}, {{ number_format($driver->latestLocation->longitude, 6) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="border-top pt-4 mt-4">
                        <h5 class="card-title mb-3">
                            <i class="ri-map-pin-line align-middle me-1"></i> Current Location
                        </h5>
                        <div class="alert alert-info mb-0">
                            <i class="ri-information-line align-middle me-1"></i>
                            No location data available for this driver yet.
                        </div>
                    </div>
                @endif

                <!-- Documents Section -->
                @if($driver->documents->count() > 0)
                    <div class="border-top pt-4 mt-4">
                        <h5 class="card-title mb-3">Documents</h5>
                        <div class="row">
                            @foreach($driver->documents as $document)
                                <div class="col-md-3 mb-3">
                                    <div class="card border shadow-none mb-0 h-100">
                                        <div class="card-body p-3 text-center">
                                            <div class="mb-3">
                                                @if(str_starts_with($document->mime_type, 'image/'))
                                                    <img src="{{ asset('storage/' . $document->file_path) }}" alt="{{ $document->original_name }}" class="img-fluid rounded" style="max-height: 100px;">
                                                @else
                                                    <i class="ri-file-pdf-line display-4 text-danger"></i>
                                                @endif
                                            </div>
                                            <h6 class="fs-14 mb-1 text-truncate" title="{{ $document->original_name }}">
                                                {{ $document->original_name }}
                                            </h6>
                                            <p class="text-muted fs-12 mb-3">{{ round($document->file_size / 1024, 2) }} KB</p>
                                            <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="btn btn-sm btn-soft-primary w-100">
                                                <i class="ri-download-line align-middle me-1"></i> View / Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('drivers.index') }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line align-middle me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

@if($driver->latestLocation)
@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""/>
@endpush

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $driver->latestLocation->latitude }};
        const lng = {{ $driver->latestLocation->longitude }};
        
        // Initialize map
        const map = L.map('driverMap').setView([lat, lng], 13);

        // Add default OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Create custom icon
        const markerColor = {{ $driver->type }} === 1 ? '#0d6efd' : '#0dcaf0';
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="
                background-color: ${markerColor};
                width: 30px;
                height: 30px;
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                border: 3px solid white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            "></div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 30],
        });

        // Add marker
        const marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
        
        // Add popup
        marker.bindPopup(`
            <div>
                <h6>{{ $driver->name }}</h6>
                <p class="mb-1"><strong>Type:</strong> {{ $driver->getTypeLabel() }}</p>
                <p class="mb-0"><strong>Last Updated:</strong> {{ $driver->latestLocation->updated_at->format('M d, Y h:i A') }}</p>
            </div>
        `).openPopup();
    });
</script>
@endpush
@endif
@endsection
