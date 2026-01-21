@extends('layouts.app')

@section('title', 'Daily/Weekly Report | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Daily/Weekly Report</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Daily/Weekly</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.daily-weekly') }}">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Report Type</label>
                            <select name="type" class="form-select">
                                <option value="daily" {{ $reportType == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ $reportType == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Driver</label>
                            <select name="driver_id" class="form-select">
                                <option value="">All Drivers</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Vessel</label>
                            <select name="vessel_id" class="form-select">
                                <option value="">All Vessels</option>
                                @foreach($vessels as $vessel)
                                    <option value="{{ $vessel->id }}" {{ request('vessel_id') == $vessel->id ? 'selected' : '' }}>
                                        {{ $vessel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Partner</label>
                            <select name="partner_id" class="form-select">
                                <option value="">All Partners</option>
                                @foreach($partners as $partner)
                                    <option value="{{ $partner->id }}" {{ request('partner_id') == $partner->id ? 'selected' : '' }}>
                                        {{ $partner->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Apply Filters
                            </button>
                            <a href="{{ route('reports.daily-weekly') }}" class="btn btn-secondary">
                                <i class="ri-refresh-line me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded">
                            <i class="ri-route-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Total Trips</p>
                        <h3 class="mb-0 fw-bold">{{ $uniqueTrips->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-success-subtle text-success rounded">
                            <i class="ri-checkbox-circle-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Completed</p>
                        <h3 class="mb-0 fw-bold">{{ $uniqueTrips->where('status', \App\Models\TripCrew::STATUS_COMPLETED)->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-info-subtle text-info rounded">
                            <i class="ri-time-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Avg Per Day</p>
                        <h3 class="mb-0 fw-bold">
                            @php
                                $days = $dateFrom->diffInDays($dateTo) + 1;
                                $avg = $days > 0 ? round($uniqueTrips->count() / $days, 1) : 0;
                            @endphp
                            {{ $avg }}
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-warning-subtle text-warning rounded">
                            <i class="ri-calendar-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Period</p>
                        <h6 class="mb-0 fw-bold">{{ $dateFrom->format('M d') }} - {{ $dateTo->format('M d, Y') }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Trips by {{ ucfirst($reportType) }}</h6>
            </div>
            <div class="card-body">
                <canvas id="tripsChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Peak Hours</h6>
            </div>
            <div class="card-body">
                <canvas id="peakHoursChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Busiest Days & Daily Stats -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Busiest Days</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th class="text-end">Trips</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dayOfWeek as $day => $count)
                            <tr>
                                <td>{{ $day }}</td>
                                <td class="text-end">
                                    <span class="badge bg-primary">{{ $count }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">{{ ucfirst($reportType) }} Statistics</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ $reportType == 'weekly' ? 'Week' : 'Date' }}</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyStats as $stat)
                            <tr>
                                <td>{{ $stat['date'] }}</td>
                                <td class="text-end">{{ $stat['total'] }}</td>
                                <td class="text-end">
                                    <span class="badge bg-success">{{ $stat['completed'] }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trip Details -->
<div class="row">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Trip Details</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="dailyWeeklyTable" class="table table-hover table-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Trip Title</th>
                                <th>Crew Name</th>
                                <th>Driver</th>
                                <th>Vessel</th>
                                <th>Pick-up Time</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Status</th>
                                <th style="display:none;">Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($crews as $crew)
                            @php
                                $trip = $crew->trip;
                            @endphp
                            <tr>
                                <td>{{ $trip->trip_date->format('M d, Y') }}</td>
                                <td>{{ $trip->title ?? '-' }}</td>
                                <td>{{ $crew->name ?? '-' }}</td>
                                <td>{{ $trip->driver->name ?? '-' }}</td>
                                <td>{{ $crew->vessel ? $crew->vessel->name : '-' }}</td>
                                <td>{{ $crew->pick_up_time ? \Carbon\Carbon::parse($crew->pick_up_time)->format('h:i A') : '-' }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 120px;" title="{{ $crew->from_location ?? '-' }}">
                                        {{ $crew->from_location ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 120px;" title="{{ $crew->to_location ?? '-' }}">
                                        {{ $crew->to_location ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $trip->getStatusBadgeClass() }}">
                                        {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                    </span>
                                </td>
                                <td style="display:none;" data-order="{{ $crew->created_at->timestamp }}">{{ $crew->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<style>
    .dt-buttons {
        margin-bottom: 1rem;
    }
    .dt-button {
        margin-left: 0.5rem !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    // Trips Chart
    const tripsCtx = document.getElementById('tripsChart').getContext('2d');
    const tripsLabels = {!! json_encode(array_column($dailyStats, 'date')) !!};
    const tripsData = {!! json_encode(array_column($dailyStats, 'total')) !!};
    
    new Chart(tripsCtx, {
        type: 'bar',
        data: {
            labels: tripsLabels,
            datasets: [{
                label: 'Trips',
                data: tripsData,
                backgroundColor: 'rgba(13, 202, 240, 0.8)',
                borderColor: 'rgb(13, 202, 240)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Peak Hours Chart
    const peakCtx = document.getElementById('peakHoursChart').getContext('2d');
    const peakLabels = {!! json_encode(array_keys($peakHours)) !!};
    const peakData = {!! json_encode(array_values($peakHours)) !!};
    
    new Chart(peakCtx, {
        type: 'bar',
        data: {
            labels: peakLabels,
            datasets: [{
                label: 'Trips',
                data: peakData,
                backgroundColor: 'rgba(25, 135, 84, 0.8)',
                borderColor: 'rgb(25, 135, 84)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Initialize DataTable with Export Buttons
    $(document).ready(function() {
        $('#dailyWeeklyTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="ri-file-excel-2-line me-1"></i> Export to Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Daily Weekly Report',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
                        modifier: {
                            page: 'all',
                            search: 'none'
                        },
                        format: {
                            body: function(data, row, column, node) {
                                var text = $(data).text().trim();
                                return text || data;
                            }
                        }
                    },
                    filename: 'Daily_Weekly_Report_' + new Date().toISOString().split('T')[0]
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="ri-file-pdf-line me-1"></i> Export to PDF',
                    className: 'btn btn-danger btn-sm',
                    title: 'Daily/Weekly Report',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
                        modifier: {
                            page: 'all',
                            search: 'none'
                        },
                        format: {
                            body: function(data, row, column, node) {
                                var text = $(data).text().trim();
                                return text || data;
                            }
                        }
                    },
                    filename: 'Daily_Weekly_Report_' + new Date().toISOString().split('T')[0],
                    customize: function(doc) {
                        doc.info = doc.info || {};
                        doc.info.title = 'Daily/Weekly Report';
                        doc.info.author = '{{ config("app.name") }}';
                        
                        doc.header = function(currentPage, pageCount) {
                            return {
                                columns: [
                                    { text: '{{ config("app.name") }}', style: 'companyName', alignment: 'left', margin: [40, 20, 0, 0] },
                                    { text: 'Daily/Weekly Report', style: 'headerTitle', alignment: 'center', margin: [0, 20, 0, 0] },
                                    { text: 'Page ' + currentPage + ' of ' + pageCount, alignment: 'right', style: 'pageNumber', margin: [0, 20, 40, 0] }
                                ]
                            };
                        };
                        
                        doc.footer = function(currentPage, pageCount) {
                            return {
                                columns: [
                                    { text: 'Generated on: ' + new Date().toLocaleString(), alignment: 'left', style: 'footer', margin: [40, 0, 0, 20] },
                                    { text: '© {{ date("Y") }} {{ config("app.name") }}', alignment: 'right', style: 'footer', margin: [0, 0, 40, 20] }
                                ]
                            };
                        };
                        
                        doc.styles.companyName = { fontSize: 14, bold: true, color: '#1e3a8a' };
                        doc.styles.headerTitle = { fontSize: 12, bold: true, color: '#374151' };
                        doc.styles.pageNumber = { fontSize: 9, color: '#6b7280' };
                        doc.styles.footer = { fontSize: 8, color: '#9ca3af' };
                        
                        if (doc.content[doc.content.length - 1].table) {
                            var table = doc.content[doc.content.length - 1];
                            table.table.headerRows = 1;
                            table.table.widths = ['auto', 'auto', '*', 'auto', 'auto', 'auto', 'auto', 'auto', 'auto'];
                            
                            table.layout = {
                                hLineWidth: function(i, node) { return (i === 0 || i === 1 || i === node.table.body.length) ? 1 : 0.5; },
                                vLineWidth: function(i) { return 0.5; },
                                hLineColor: function(i, node) { return (i === 0 || i === 1 || i === node.table.body.length) ? '#1e3a8a' : '#e5e7eb'; },
                                vLineColor: function() { return '#e5e7eb'; },
                                paddingLeft: function() { return 8; },
                                paddingRight: function() { return 8; },
                                paddingTop: function() { return 6; },
                                paddingBottom: function() { return 6; }
                            };
                            
                            for (var i = 0; i < table.table.body.length; i++) {
                                for (var j = 0; j < table.table.body[i].length; j++) {
                                    if (i === 0) {
                                        table.table.body[i][j].fillColor = '#1e3a8a';
                                        table.table.body[i][j].color = '#ffffff';
                                        table.table.body[i][j].bold = true;
                                        table.table.body[i][j].fontSize = 10;
                                    } else {
                                        table.table.body[i][j].fillColor = (i % 2 === 0) ? '#ffffff' : '#f9fafb';
                                        table.table.body[i][j].fontSize = 9;
                                    }
                                }
                            }
                        }
                        
                        doc.pageMargins = [40, 60, 40, 50];
                    }
                }
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[9, 'desc']], // Sort by created_at (hidden column) descending
            columnDefs: [
                {
                    targets: 9,
                    visible: false,
                    searchable: false
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search crews...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ crews",
                infoEmpty: "Showing 0 to 0 of 0 crews",
                infoFiltered: "(filtered from _MAX_ total crews)",
                zeroRecords: "No matching crews found",
                emptyTable: "No crews available"
            },
            responsive: true
        });
    });
</script>
@endpush
@endsection

