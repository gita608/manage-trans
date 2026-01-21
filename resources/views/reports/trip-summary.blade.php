@extends('layouts.app')

@section('title', 'Trip Summary Report | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Trip Summary Report</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Trip Summary</li>
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
                <form method="GET" action="{{ route('reports.trip-summary') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
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
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                @foreach(\App\Models\Trip::getStatuses() as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Driver Type</label>
                            <select name="driver_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="1" {{ request('driver_type') == '1' ? 'selected' : '' }}>Internal</option>
                                <option value="2" {{ request('driver_type') == '2' ? 'selected' : '' }}>Outsourcing</option>
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
                        <div class="col-md-10 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Apply Filters
                            </button>
                            <a href="{{ route('reports.trip-summary') }}" class="btn btn-secondary">
                                <i class="ri-refresh-line me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
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
                        <h3 class="mb-0 fw-bold">{{ $totalTrips }}</h3>
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
                            <i class="ri-task-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Assigned</p>
                        <h3 class="mb-0 fw-bold">{{ $assignedTrips }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">In Progress</p>
                        <h3 class="mb-0 fw-bold">{{ $inProgressTrips }}</h3>
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
                        <h3 class="mb-0 fw-bold">{{ $completedTrips }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Trips by Status</h6>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Trips by Date</h6>
            </div>
            <div class="card-body">
                <canvas id="dateChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Trip Details Table -->
<div class="row">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">Trip Details</h6>
                    <span class="badge bg-primary">{{ $crews->count() }} crews</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tripSummaryTable" class="table table-hover table-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Trip Title</th>
                                <th>Crew Name</th>
                                <th>Driver</th>
                                <th>Partner</th>
                                <th>Vessel</th>
                                <th>Pick-up Time</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Total Expenses</th>
                                <th>Status</th>
                                <th style="display:none;">Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($crews as $crew)
                            @php
                                $trip = $crew->trip;
                                $totalExpenses = $trip->tripExpenses->sum('amount') ?? 0;
                            @endphp
                            <tr>
                                <td>{{ $trip->trip_date->format('M d, Y') }}</td>
                                <td>{{ $trip->title ?? '-' }}</td>
                                <td>{{ $crew->name ?? '-' }}</td>
                                <td>{{ $trip->driver->name ?? '-' }}</td>
                                <td>{{ $trip->partner ? $trip->partner->title : '-' }}</td>
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
                                <td>{{ number_format($totalExpenses, 2) }}</td>
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
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($statusData)) !!},
            datasets: [{
                data: {!! json_encode(array_values($statusData)) !!},
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(13, 202, 240, 0.8)',
                    'rgba(25, 135, 84, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Date Chart
    const dateCtx = document.getElementById('dateChart').getContext('2d');
    const dateLabels = {!! json_encode(array_keys($tripsByDate->toArray())) !!};
    const dateData = {!! json_encode(array_values($tripsByDate->toArray())) !!};
    
    new Chart(dateCtx, {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [{
                label: 'Trips',
                data: dateData,
                borderColor: 'rgb(13, 202, 240)',
                backgroundColor: 'rgba(13, 202, 240, 0.1)',
                tension: 0.4,
                fill: true
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
        $('#tripSummaryTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="ri-file-excel-2-line me-1"></i> Export to Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Trip Summary Report',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                        modifier: {
                            page: 'all',
                            search: 'none'
                        },
                        format: {
                            body: function(data, row, column, node) {
                                // Helper function to strip HTML and extract text
                                function stripHtml(html) {
                                    if (!html) return '';
                                    
                                    // If it's already plain text (no HTML tags), return it
                                    if (typeof html === 'string' && !/<[^>]+>/.test(html)) {
                                        return html.trim();
                                    }
                                    
                                    // If we have a DOM node, extract text directly
                                    if (node && node.nodeType === 1) {
                                        return $(node).text().trim() || '';
                                    }
                                    
                                    // If it's a string with HTML, create a temporary element to extract text
                                    if (typeof html === 'string') {
                                        var $temp = $('<div>').html(html);
                                        var text = $temp.text().trim();
                                        return text || html.replace(/<[^>]+>/g, '').trim();
                                    }
                                    
                                    return '';
                                }
                                
                                return stripHtml(data);
                            }
                        }
                    },
                    filename: 'Trip_Summary_Report_' + new Date().toISOString().split('T')[0]
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="ri-file-pdf-line me-1"></i> Export to PDF',
                    className: 'btn btn-danger btn-sm',
                    title: 'Trip Summary Report',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                        modifier: {
                            page: 'all',
                            search: 'none'
                        },
                        format: {
                            body: function(data, row, column, node) {
                                // Helper function to strip HTML and extract text
                                function stripHtml(html) {
                                    if (!html) return '';
                                    
                                    // If it's already plain text (no HTML tags), return it
                                    if (typeof html === 'string' && !/<[^>]+>/.test(html)) {
                                        return html.trim();
                                    }
                                    
                                    // If we have a DOM node, extract text directly
                                    if (node && node.nodeType === 1) {
                                        return $(node).text().trim() || '';
                                    }
                                    
                                    // If it's a string with HTML, create a temporary element to extract text
                                    if (typeof html === 'string') {
                                        var $temp = $('<div>').html(html);
                                        var text = $temp.text().trim();
                                        return text || html.replace(/<[^>]+>/g, '').trim();
                                    }
                                    
                                    return '';
                                }
                                
                                return stripHtml(data);
                            }
                        }
                    },
                    filename: 'Trip_Summary_Report_' + new Date().toISOString().split('T')[0],
                    customize: function(doc) {
                        // Set document properties
                        doc.info = doc.info || {};
                        doc.info.title = 'Trip Summary Report';
                        doc.info.author = '{{ config("app.name") }}';
                        doc.info.subject = 'Trip Summary Report';
                        doc.info.keywords = 'trips, summary, report';
                        
                        // Add header with logo and company info
                        doc.header = function(currentPage, pageCount) {
                            return {
                                columns: [
                                    {
                                        text: '{{ config("app.name") }}',
                                        style: 'companyName',
                                        alignment: 'left',
                                        margin: [40, 20, 0, 0]
                                    },
                                    {
                                        text: 'Trip Summary Report',
                                        style: 'headerTitle',
                                        alignment: 'center',
                                        margin: [0, 20, 0, 0]
                                    },
                                    {
                                        text: 'Page ' + currentPage.toString() + ' of ' + pageCount,
                                        alignment: 'right',
                                        style: 'pageNumber',
                                        margin: [0, 20, 40, 0]
                                    }
                                ]
                            };
                        };
                        
                        // Add footer with generation date and time
                        doc.footer = function(currentPage, pageCount) {
                            return {
                                columns: [
                                    {
                                        text: 'Generated on: ' + new Date().toLocaleString('en-US', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }),
                                        alignment: 'left',
                                        style: 'footer',
                                        margin: [40, 0, 0, 20]
                                    },
                                    {
                                        text: '© {{ date("Y") }} {{ config("app.name") }}',
                                        alignment: 'right',
                                        style: 'footer',
                                        margin: [0, 0, 40, 20]
                                    }
                                ]
                            };
                        };
                        
                        // Add filter information before the table
                        var filterInfo = [];
                        var dateFrom = '{{ request("date_from") }}';
                        var dateTo = '{{ request("date_to") }}';
                        var driver = '{{ request("driver_id") ? ($drivers->firstWhere("id", request("driver_id"))->name ?? "All Drivers") : "All Drivers" }}';
                        var vessel = '{{ request("vessel_id") ? ($vessels->firstWhere("id", request("vessel_id"))->name ?? "All Vessels") : "All Vessels" }}';
                        var status = '{{ request("status") ? ucfirst(str_replace("_", " ", request("status"))) : "All Status" }}';
                        
                        if (dateFrom || dateTo || driver !== 'All Drivers' || vessel !== 'All Vessels' || status !== 'All Status') {
                            filterInfo.push({
                                text: 'Applied Filters:',
                                style: 'filterHeader',
                                margin: [0, 10, 0, 5]
                            });
                            
                            var filters = [];
                            if (dateFrom) filters.push('Date From: ' + dateFrom);
                            if (dateTo) filters.push('Date To: ' + dateTo);
                            if (driver !== 'All Drivers') filters.push('Driver: ' + driver);
                            if (vessel !== 'All Vessels') filters.push('Vessel: ' + vessel);
                            if (status !== 'All Status') filters.push('Status: ' + status);
                            
                            filterInfo.push({
                                text: filters.join(' | '),
                                style: 'filterText',
                                margin: [0, 0, 0, 10]
                            });
                        }
                        
                        // Insert filter info before the table
                        if (filterInfo.length > 0) {
                            doc.content.splice(1, 0, ...filterInfo);
                        }
                        
                        // Style the document
                        doc.styles.companyName = {
                            fontSize: 14,
                            bold: true,
                            color: '#1e3a8a'
                        };
                        
                        doc.styles.headerTitle = {
                            fontSize: 12,
                            bold: true,
                            color: '#374151'
                        };
                        
                        doc.styles.pageNumber = {
                            fontSize: 9,
                            color: '#6b7280'
                        };
                        
                        doc.styles.footer = {
                            fontSize: 8,
                            color: '#9ca3af'
                        };
                        
                        doc.styles.filterHeader = {
                            fontSize: 11,
                            bold: true,
                            color: '#374151'
                        };
                        
                        doc.styles.filterText = {
                            fontSize: 9,
                            color: '#6b7280',
                            italics: true
                        };
                        
                        // Style the table
                        doc.styles.tableHeader = {
                            fontSize: 10,
                            bold: true,
                            fillColor: '#1e3a8a',
                            color: '#ffffff',
                            alignment: 'left'
                        };
                        
                        doc.styles.tableBodyOdd = {
                            fontSize: 9,
                            fillColor: '#f9fafb'
                        };
                        
                        doc.styles.tableBodyEven = {
                            fontSize: 9,
                            fillColor: '#ffffff'
                        };
                        
                        // Apply styles to table
                        if (doc.content[doc.content.length - 1].table) {
                            var table = doc.content[doc.content.length - 1];
                            
                            // Style header row
                            table.table.headerRows = 1;
                            table.table.widths = ['auto', 'auto', '*', 'auto', 'auto', 'auto', 'auto', 'auto', 'auto', 'auto', 'auto'];
                            
                            // Add borders and styling
                            table.layout = {
                                hLineWidth: function(i, node) {
                                    return (i === 0 || i === 1 || i === node.table.body.length) ? 1 : 0.5;
                                },
                                vLineWidth: function(i, node) {
                                    return 0.5;
                                },
                                hLineColor: function(i, node) {
                                    return (i === 0 || i === 1 || i === node.table.body.length) ? '#1e3a8a' : '#e5e7eb';
                                },
                                vLineColor: function(i, node) {
                                    return '#e5e7eb';
                                },
                                paddingLeft: function(i, node) { return 8; },
                                paddingRight: function(i, node) { return 8; },
                                paddingTop: function(i, node) { return 6; },
                                paddingBottom: function(i, node) { return 6; }
                            };
                            
                            // Style rows
                            for (var i = 0; i < table.table.body.length; i++) {
                                for (var j = 0; j < table.table.body[i].length; j++) {
                                    if (i === 0) {
                                        // Header row
                                        table.table.body[i][j].fillColor = '#1e3a8a';
                                        table.table.body[i][j].color = '#ffffff';
                                        table.table.body[i][j].bold = true;
                                        table.table.body[i][j].fontSize = 10;
                                    } else {
                                        // Data rows - alternating colors
                                        table.table.body[i][j].fillColor = (i % 2 === 0) ? '#ffffff' : '#f9fafb';
                                        table.table.body[i][j].fontSize = 9;
                                    }
                                }
                            }
                        }
                        
                        // Add page margins
                        doc.pageMargins = [40, 60, 40, 50];
                    }
                }
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[11, 'desc']], // Sort by created_at (hidden column) descending
            columnDefs: [
                {
                    targets: 11,
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
            responsive: true,
            paging: true
        });
    });
</script>
@endpush
@endsection

