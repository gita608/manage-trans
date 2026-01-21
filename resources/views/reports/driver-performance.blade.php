@extends('layouts.app')

@section('title', 'Driver Performance Report | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Driver Performance Report</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Driver Performance</li>
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
                <form method="GET" action="{{ route('reports.driver-performance') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}">
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
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Apply
                            </button>
                            <a href="{{ route('reports.driver-performance') }}" class="btn btn-secondary">
                                <i class="ri-refresh-line me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Comparison Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0">Internal Drivers</h6>
                    <span class="badge bg-info">{{ $internalDrivers }} drivers</span>
                </div>
                <h3 class="mb-0 fw-bold">{{ $internalTrips }}</h3>
                <p class="text-muted mb-0 small">Total trips</p>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0">Outsourcing Drivers</h6>
                    <span class="badge bg-warning">{{ $outsourcingDrivers }} drivers</span>
                </div>
                <h3 class="mb-0 fw-bold">{{ $outsourcingTrips }}</h3>
                <p class="text-muted mb-0 small">Total trips</p>
            </div>
        </div>
    </div>
</div>

<!-- Driver Performance Table -->
<div class="row">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Driver Performance Details</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="driverPerformanceTable" class="table table-hover table-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Driver Name</th>
                                <th>Type</th>
                                <th>Total Trips</th>
                                <th>Assigned</th>
                                <th>In Progress</th>
                                <th>Completed</th>
                                <th>Completion Rate</th>
                                <th style="display:none;">Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($driverStats as $index => $stat)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($stat['driver']->photo)
                                            <img src="{{ asset('storage/' . $stat['driver']->photo) }}" alt="{{ $stat['driver']->name }}" class="rounded-circle avatar-xs me-2">
                                        @else
                                            <div class="avatar-xs rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center me-2">
                                                <span class="text-primary small">{{ substr($stat['driver']->name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                        <span class="fw-medium">{{ $stat['driver']->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($stat['driver']->type == \App\Models\Driver::TYPE_INTERNAL)
                                        <span class="badge bg-info">Internal</span>
                                    @else
                                        <span class="badge bg-warning">Outsourcing</span>
                                    @endif
                                </td>
                                <td><strong>{{ $stat['total_trips'] }}</strong></td>
                                <td><span class="badge bg-warning">{{ $stat['assigned'] }}</span></td>
                                <td><span class="badge bg-info">{{ $stat['in_progress'] }}</span></td>
                                <td><span class="badge bg-success">{{ $stat['completed'] }}</span></td>
                                <td>
                                    @php
                                        $completionRate = $stat['total_trips'] > 0 
                                            ? round(($stat['completed'] / $stat['total_trips']) * 100, 1) 
                                            : 0;
                                    @endphp
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 me-2">
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $completionRate }}%"></div>
                                            </div>
                                        </div>
                                        <span class="fw-medium">{{ $completionRate }}%</span>
                                    </div>
                                </td>
                                <td style="display:none;" data-order="{{ $stat['driver']->created_at->timestamp }}">{{ $stat['driver']->created_at->format('Y-m-d H:i:s') }}</td>
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
    $(document).ready(function() {
        $('#driverPerformanceTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="ri-file-excel-2-line me-1"></i> Export to Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Driver Performance Report',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
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
                    filename: 'Driver_Performance_Report_' + new Date().toISOString().split('T')[0]
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="ri-file-pdf-line me-1"></i> Export to PDF',
                    className: 'btn btn-danger btn-sm',
                    title: 'Driver Performance Report',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
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
                    filename: 'Driver_Performance_Report_' + new Date().toISOString().split('T')[0],
                    customize: function(doc) {
                        doc.info = doc.info || {};
                        doc.info.title = 'Driver Performance Report';
                        doc.info.author = '{{ config("app.name") }}';
                        
                        doc.header = function(currentPage, pageCount) {
                            return {
                                columns: [
                                    { text: '{{ config("app.name") }}', style: 'companyName', alignment: 'left', margin: [40, 20, 0, 0] },
                                    { text: 'Driver Performance Report', style: 'headerTitle', alignment: 'center', margin: [0, 20, 0, 0] },
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
                            table.table.widths = ['auto', '*', 'auto', 'auto', 'auto', 'auto', 'auto', 'auto'];
                            
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
            order: [[8, 'desc']], // Sort by created_at (hidden column) descending
            columnDefs: [
                {
                    targets: 8,
                    visible: false,
                    searchable: false
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search drivers...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ drivers",
                infoEmpty: "Showing 0 to 0 of 0 drivers",
                infoFiltered: "(filtered from _MAX_ total drivers)",
                zeroRecords: "No matching drivers found",
                emptyTable: "No drivers available"
            },
            responsive: true
        });
    });
</script>
@endpush
@endsection

