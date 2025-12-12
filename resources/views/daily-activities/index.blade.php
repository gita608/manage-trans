@extends('layouts.app')

@section('title', 'Daily Activities | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Daily Activities</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Daily Activities</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<!-- Statistics Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-2">Total Activities</p>
                        <h4 class="mb-0">{{ $totalActivities }}</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                                <i class="ri-calendar-check-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-2">Today's Activities</p>
                        <h4 class="mb-0">{{ $todayActivities }}</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                <i class="ri-calendar-todo-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-2">This Month</p>
                        <h4 class="mb-0">{{ $thisMonthActivities }}</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-info-subtle text-info rounded fs-3">
                                <i class="ri-calendar-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daily Activities List -->
<div class="row">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-0">Daily Activities</h5>
                        @if(!request()->filled('date_range') && !request()->filled('date_from') && !request()->filled('date_to'))
                            <small class="text-muted">
                                <i class="ri-information-line me-1"></i>Showing today's activities. Use filters to view previous data.
                            </small>
                        @else
                            <small class="text-muted">
                                <i class="ri-filter-line me-1"></i>Filtered results
                            </small>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-soft-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#filterSection">
                            <i class="ri-filter-3-line me-1"></i> Filters
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" action="{{ route('daily-activities.index') }}" class="collapse show mb-4" id="filterSection">
                    <div class="row g-3 p-3 bg-light rounded">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">
                                <i class="ri-user-line me-1"></i>Driver
                            </label>
                            <select name="driver_id" class="form-select">
                                <option value="">All Drivers</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">
                                <i class="ri-calendar-line me-1"></i>Date Range
                            </label>
                            <select name="date_range" class="form-select">
                                <option value="">View Previous Data</option>
                                <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ request('date_range') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                <option value="last_7_days" {{ request('date_range') == 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="this_month" {{ request('date_range') == 'this_month' ? 'selected' : '' }}>This Month</option>
                                <option value="last_month" {{ request('date_range') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="ri-calendar-line me-1"></i>Date From
                            </label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="ri-calendar-line me-1"></i>Date To
                            </label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="ri-file-text-line me-1"></i>Has Note
                            </label>
                            <select name="has_note" class="form-select">
                                <option value="">All</option>
                                <option value="yes" {{ request('has_note') == 'yes' ? 'selected' : '' }}>Yes</option>
                                <option value="no" {{ request('has_note') == 'no' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="ri-image-line me-1"></i>Has Image
                            </label>
                            <select name="has_image" class="form-select">
                                <option value="">All</option>
                                <option value="yes" {{ request('has_image') == 'yes' ? 'selected' : '' }}>Yes</option>
                                <option value="no" {{ request('has_image') == 'no' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                            <a href="{{ route('daily-activities.index') }}" class="btn btn-light">
                                <i class="ri-refresh-line me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Activities Table -->
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Driver</th>
                                <th>Activity Date</th>
                                <th>Note</th>
                                <th>Image</th>
                                <th>Created At</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $activity)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($activity->driver && $activity->driver->photo)
                                                <img src="{{ asset('storage/' . $activity->driver->photo) }}" alt="{{ $activity->driver->name }}" class="rounded-circle avatar-xs me-2">
                                            @else
                                                <div class="avatar-xs rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center me-2">
                                                    <span class="text-primary small">{{ $activity->driver ? substr($activity->driver->name, 0, 1) : '?' }}</span>
                                                </div>
                                            @endif
                                            <span class="fw-medium">{{ $activity->driver->name ?? 'Unknown Driver' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $activity->activity_date ? $activity->activity_date->format('M d, Y') : 'N/A' }}</div>
                                        <small class="text-muted">{{ $activity->activity_date ? $activity->activity_date->format('l') : '' }}</small>
                                    </td>
                                    <td>
                                        @if($activity->note)
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $activity->note }}">
                                                {{ Str::limit($activity->note, 50) }}
                                            </span>
                                        @else
                                            <span class="text-muted">No note</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($activity->image)
                                            <a href="{{ asset('storage/' . $activity->image) }}" target="_blank" class="btn btn-sm btn-soft-primary">
                                                <i class="ri-image-line me-1"></i>View
                                            </a>
                                        @else
                                            <span class="text-muted small">No image</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $activity->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $activity->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#activityModal{{ $activity->id }}">
                                            <i class="ri-eye-line"></i> View
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal for Activity Details -->
                                <div class="modal fade" id="activityModal{{ $activity->id }}" tabindex="-1" aria-labelledby="activityModalLabel{{ $activity->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="activityModalLabel{{ $activity->id }}">Daily Activity Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>Driver:</strong><br>
                                                        <div class="d-flex align-items-center mt-2">
                                                            @if($activity->driver && $activity->driver->photo)
                                                                <img src="{{ asset('storage/' . $activity->driver->photo) }}" alt="{{ $activity->driver->name }}" class="rounded-circle avatar-sm me-2">
                                                            @else
                                                                <div class="avatar-sm rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center me-2">
                                                                    <span class="text-primary small">{{ $activity->driver ? substr($activity->driver->name, 0, 1) : '?' }}</span>
                                                                </div>
                                                            @endif
                                                            <span>{{ $activity->driver->name ?? 'Unknown Driver' }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Activity Date:</strong><br>
                                                        <span class="mt-2 d-inline-block">{{ $activity->activity_date ? $activity->activity_date->format('F d, Y (l)') : 'N/A' }}</span>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>Created At:</strong><br>
                                                        <span class="mt-2 d-inline-block">{{ $activity->created_at->format('F d, Y h:i A') }}</span>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Updated At:</strong><br>
                                                        <span class="mt-2 d-inline-block">{{ $activity->updated_at->format('F d, Y h:i A') }}</span>
                                                    </div>
                                                </div>
                                                @if($activity->note)
                                                    <div class="mb-3">
                                                        <strong>Note:</strong><br>
                                                        <div class="mt-2 p-3 bg-light rounded">
                                                            {{ $activity->note }}
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($activity->image)
                                                    <div class="mb-3">
                                                        <strong>Image:</strong><br>
                                                        <div class="mt-2">
                                                            <a href="{{ asset('storage/' . $activity->image) }}" target="_blank">
                                                                <img src="{{ asset('storage/' . $activity->image) }}" alt="Activity Image" class="img-thumbnail" style="max-width: 100%; max-height: 400px;">
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="ri-inbox-line" style="font-size: 48px;"></i>
                                        <p class="mt-2 mb-0">No daily activities found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($activities->hasPages())
                    <div class="mt-3">
                        {{ $activities->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
