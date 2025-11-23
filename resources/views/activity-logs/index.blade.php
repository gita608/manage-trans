@extends('layouts.app')

@section('title', 'Activity Logs | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Activity Logs</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Activity Logs</li>
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
                <h5 class="card-title mb-0">System Activity Logs</h5>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" action="{{ route('activity-logs') }}" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">User</label>
                            <select name="user_id" class="form-select">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Model Type</label>
                            <select name="model_type" class="form-select">
                                <option value="">All Types</option>
                                @foreach($modelTypes as $type)
                                    <option value="{{ $type }}" {{ request('model_type') == $type ? 'selected' : '' }}>
                                        {{ class_basename($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                                <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                                <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                            <a href="{{ route('activity-logs') }}" class="btn btn-light">
                                <i class="ri-refresh-line me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Activity Logs Table -->
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Model Type</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th class="text-center">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>
                                        <div>{{ $log->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        @if($log->user)
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <strong>{{ $log->user->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $log->user->email }}</small>
                                                </div>
                                            </div>
                                        @elseif($log->driver)
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <strong>{{ $log->driver->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">Driver</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $actionColors = [
                                                'created' => 'success',
                                                'updated' => 'info',
                                                'deleted' => 'danger',
                                            ];
                                            $color = $actionColors[$log->action] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">
                                            {{ ucfirst($log->action) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ class_basename($log->loggable_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $log->description ?? 'N/A' }}
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $log->ip_address ?? 'N/A' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#logModal{{ $log->id }}">
                                            <i class="ri-eye-line"></i> View
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal for Log Details -->
                                <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1" aria-labelledby="logModalLabel{{ $log->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="logModalLabel{{ $log->id }}">Activity Log Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>Date & Time:</strong><br>
                                                        {{ $log->created_at->format('F d, Y h:i A') }}
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>User:</strong><br>
                                                        @if($log->user)
                                                            {{ $log->user->name }}
                                                        @elseif($log->driver)
                                                            {{ $log->driver->name }} <small class="text-muted">(Driver)</small>
                                                        @else
                                                            System
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>Action:</strong><br>
                                                        <span class="badge bg-{{ $color }}">{{ ucfirst($log->action) }}</span>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Model Type:</strong><br>
                                                        {{ class_basename($log->loggable_type) }}
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Description:</strong><br>
                                                    {{ $log->description ?? 'N/A' }}
                                                </div>
                                                @if($log->old_values || $log->new_values)
                                                    <div class="row">
                                                        @if($log->old_values)
                                                            <div class="col-md-6">
                                                                <strong>Old Values:</strong>
                                                                <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;"><code>{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</code></pre>
                                                            </div>
                                                        @endif
                                                        @if($log->new_values)
                                                            <div class="col-md-6">
                                                                <strong>New Values:</strong>
                                                                <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;"><code>{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</code></pre>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <strong>IP Address:</strong><br>
                                                        {{ $log->ip_address ?? 'N/A' }}
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>User Agent:</strong><br>
                                                        <small>{{ $log->user_agent ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="ri-inbox-line" style="font-size: 48px;"></i>
                                        <p class="mt-2 mb-0">No activity logs found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($logs->hasPages())
                    <div class="mt-3">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

