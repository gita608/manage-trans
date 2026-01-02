@extends('layouts.app')

@section('title', 'Trip Expenses Report | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Trip Expenses Report</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Trip Expenses</li>
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
                <form method="GET" action="{{ route('reports.trip-expenses') }}" id="filterForm">
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
                            <label class="form-label">Expense Type</label>
                            <select name="expense_type_id" class="form-select">
                                <option value="">All Types</option>
                                @foreach($expenseTypes as $type)
                                    <option value="{{ $type->id }}" {{ request('expense_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Apply Filters
                            </button>
                            <a href="{{ route('reports.trip-expenses') }}" class="btn btn-secondary">
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
    <div class="col-xl-6 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded">
                            <i class="ri-money-dollar-circle-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Total Expenses</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($totalExpenses, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-info-subtle text-info rounded">
                            <i class="ri-file-list-3-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Total Transactions</p>
                        <h3 class="mb-0 fw-bold">{{ $expenses->count() }}</h3>
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
                <h6 class="card-title mb-0">Expenses by Type</h6>
            </div>
            <div class="card-body">
                <canvas id="typeChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Expenses by Date</h6>
            </div>
            <div class="card-body">
                <canvas id="dateChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Expense Details Table -->
<div class="row">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">Expense Details</h6>
                    <span class="badge bg-primary">{{ $expenses->count() }} records</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tripExpensesTable" class="table table-hover table-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Trip Date</th>
                                <th>Expense Type</th>
                                <th>Amount</th>
                                <th>Submitted By</th>
                                <th>Vessel</th>
                                <th>Receipt</th>
                                <th style="display:none;">Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenses as $expense)
                            @php
                                $firstCrew = $expense->trip ? $expense->trip->crews->first() : null;
                            @endphp
                            <tr>
                                <td>{{ $expense->trip ? $expense->trip->trip_date->format('M d, Y') : '-' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $expense->expenseType->title ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="fw-bold">{{ number_format($expense->amount, 2) }}</td>
                                <td>{{ $expense->driver->name ?? 'Unknown' }}</td>
                                <td>{{ $firstCrew && $firstCrew->vessel ? $firstCrew->vessel->name : 'Unknown' }}</td>
                                <td>
                                    @if($expense->receipt)
                                        <a href="{{ Storage::url($expense->receipt) }}" target="_blank" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-file-text-line me-1"></i> View
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td style="display:none;" data-order="{{ $expense->created_at->timestamp }}">{{ $expense->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="fw-bold text-end">Total</td>
                                <td class="fw-bold">{{ number_format($totalExpenses, 2) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
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
    // Type Chart
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($expensesByType->toArray())) !!},
            datasets: [{
                data: {!! json_encode(array_values($expensesByType->toArray())) !!},
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ],
                borderWidth: 1
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
    const dateLabels = {!! json_encode(array_keys($expensesByDate->toArray())) !!};
    const dateData = {!! json_encode(array_values($expensesByDate->toArray())) !!};
    
    new Chart(dateCtx, {
        type: 'bar',
        data: {
            labels: dateLabels,
            datasets: [{
                label: 'Expenses',
                data: dateData,
                backgroundColor: 'rgba(13, 202, 240, 0.6)',
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
                    beginAtZero: true
                }
            }
        }
    });

    // Initialize DataTable with Export Buttons
    $(document).ready(function() {
        $('#tripExpensesTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="ri-file-excel-2-line me-1"></i> Export to Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Trip Expenses Report',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4],
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
                    filename: 'Trip_Expenses_Report_' + new Date().toISOString().split('T')[0],
                    footer: true
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="ri-file-pdf-line me-1"></i> Export to PDF',
                    className: 'btn btn-danger btn-sm',
                    title: 'Trip Expenses Report',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4],
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
                    filename: 'Trip_Expenses_Report_' + new Date().toISOString().split('T')[0],
                    footer: true,
                    customize: function(doc) {
                        doc.info = doc.info || {};
                        doc.info.title = 'Trip Expenses Report';
                        doc.info.author = '{{ config("app.name") }}';
                        
                        doc.header = function(currentPage, pageCount) {
                            return {
                                columns: [
                                    { text: '{{ config("app.name") }}', style: 'companyName', alignment: 'left', margin: [40, 20, 0, 0] },
                                    { text: 'Trip Expenses Report', style: 'headerTitle', alignment: 'center', margin: [0, 20, 0, 0] },
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
                            table.table.widths = ['auto', '*', 'auto', 'auto', 'auto'];
                            
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
                                    } else if (i === table.table.body.length - 1) {
                                        // Footer row
                                        table.table.body[i][j].fillColor = '#f3f4f6';
                                        table.table.body[i][j].bold = true;
                                        table.table.body[i][j].fontSize = 9;
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
            order: [[6, 'desc']], // Sort by created_at (hidden column) descending
            columnDefs: [
                {
                    targets: 6,
                    visible: false,
                    searchable: false
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search expenses...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ expenses",
                infoEmpty: "Showing 0 to 0 of 0 expenses",
                infoFiltered: "(filtered from _MAX_ total expenses)",
                zeroRecords: "No matching expenses found",
                emptyTable: "No expenses available"
            },
            responsive: true,
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();
                
                // Calculate total
                var total = api
                    .column(2, { page: 'current' })
                    .data()
                    .reduce(function(a, b) {
                        var val = parseFloat($(b).text().replace(/,/g, '')) || 0;
                        return a + val;
                    }, 0);
                
                // Update footer
                $(api.column(2).footer()).html(
                    '<strong>' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') + '</strong>'
                );
            }
        });
    });
</script>
@endpush
@endsection
