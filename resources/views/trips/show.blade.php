@extends('layouts.app')

@section('title', 'Trip Details | ' . config('app.name', 'Laravel'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Trip Details</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trips</a></li>
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
                    <h5 class="card-title mb-0">Trip Details</h5>
                    <div>
                        <a href="{{ route('trips.edit', $trip) }}" class="btn btn-primary">
                            <i class="ri-pencil-line align-middle me-1"></i> Edit
                        </a>
                        <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this trip?');">
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
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tripInfo" role="tab" aria-selected="true">
                            <i class="ri-information-line me-1 align-middle"></i> Trip Information
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#activityLogs" role="tab" aria-selected="false">
                            <i class="ri-file-list-line me-1 align-middle"></i> Activity Logs
                            @if($trip->activityLogs->count() > 0)
                                <span class="badge bg-primary rounded-pill ms-1">{{ $trip->activityLogs->count() }}</span>
                            @endif
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Trip Information Tab -->
                    <div class="tab-pane fade show active" id="tripInfo" role="tabpanel">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th width="200">Trip Date:</th>
                                    <td>{{ $trip->trip_date->format('l, F d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Crew Name:</th>
                                    <td>{{ $trip->crew_name }}</td>
                                </tr>
                                <tr>
                                    <th>Driver Name:</th>
                                    <td>{{ $trip->driver->name }}</td>
                                </tr>
                                <tr>
                                    <th>Vessel Name:</th>
                                    <td>{{ $trip->vessel->name }}</td>
                                </tr>
                                <tr>
                                    <th>Pick-up Time:</th>
                                    <td>{{ \Carbon\Carbon::parse($trip->pick_up_time)->format('h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>From:</th>
                                    <td>{{ $trip->from_location }}</td>
                                </tr>
                                <tr>
                                    <th>To:</th>
                                    <td>{{ $trip->to_location }}</td>
                                </tr>
                                <tr>
                                    <th>Crew Phone:</th>
                                    <td>{{ $trip->crew_phone ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Crew Address:</th>
                                    <td>{{ $trip->crew_address ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge {{ $trip->getStatusBadgeClass() }}">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created At:</th>
                                    <td>{{ $trip->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated:</th>
                                    <td>{{ $trip->updated_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Activity Logs Tab -->
                    <div class="tab-pane fade" id="activityLogs" role="tabpanel">
                        @if($trip->activityLogs->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Date & Time</th>
                                            <th scope="col">Action</th>
                                            <th scope="col">Description</th>
                                            <th scope="col">User</th>
                                            <th scope="col">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($trip->activityLogs as $log)
                                            <tr>
                                                <td>
                                                    <div>{{ $log->created_at->format('M d, Y') }}</div>
                                                    <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                                                </td>
                                                <td>
                                                    @php
                                                        $badgeClass = match($log->action) {
                                                            'created' => 'bg-success',
                                                            'updated' => 'bg-info',
                                                            'deleted' => 'bg-danger',
                                                            default => 'bg-secondary',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($log->action) }}</span>
                                                </td>
                                                <td>{{ $log->description }}</td>
                                                <td>{{ $log->user->name ?? 'System' }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-link p-0" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}">
                                                        <i class="ri-eye-line"></i> View Details
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No activity logs found for this trip.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('trips.index') }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line align-middle me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modals -->
@foreach($trip->activityLogs as $log)
    @php
        $badgeClass = match($log->action) {
            'created' => 'bg-success',
            'updated' => 'bg-info',
            'deleted' => 'bg-danger',
            default => 'bg-secondary',
        };
    @endphp
    <!-- Log Details Modal -->
    <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Activity Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th width="200">Action:</th>
                                <td><span class="badge {{ $badgeClass }}">{{ ucfirst($log->action) }}</span></td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td>{{ $log->description }}</td>
                            </tr>
                            <tr>
                                <th>User:</th>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                            </tr>
                            <tr>
                                <th>Date & Time:</th>
                                <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                            @if($log->old_values && count($log->old_values) > 0)
                                <tr>
                                    <th>Old Values:</th>
                                    <td>
                                        <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                    </td>
                                </tr>
                            @endif
                            @if($log->new_values && count($log->new_values) > 0)
                                <tr>
                                    <th>New Values:</th>
                                    <td>
                                        <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                    </td>
                                </tr>
                            @endif
                            @if($log->ip_address)
                                <tr>
                                    <th>IP Address:</th>
                                    <td>{{ $log->ip_address }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

