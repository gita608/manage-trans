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
                <div>
                    <table id="tripSummaryTable" class="table table-hover table-nowrap align-middle mb-0 w-100">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Crew Name</th>
                                <th>Vessel</th>
                                <th>Time</th>
                                <th>Destination From</th>
                                <th>Destination To</th>
                                <th>Remarks</th>
                                <th class="text-danger">Sub Remark</th>
                                <th style="display:none;">Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($crews as $crew)
                            @php
                                $trip = $crew->trip;
                            @endphp
                            <tr>
                                <td data-order="{{ $trip->trip_date->timestamp }}">{{ $trip->trip_date->format('M d, Y') }}</td>
                                <td><strong>{{ $crew->name ?? '-' }}</strong></td>
                                <td>{{ $crew->vessel ? $crew->vessel->name : '-' }}</td>
                                <td>{{ $crew->pick_up_time ? \Carbon\Carbon::parse($crew->pick_up_time)->format('h:i A') : '-' }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 140px;" title="{{ $crew->from_location ?? '-' }}">
                                        {{ $crew->from_location ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 140px;" title="{{ $crew->to_location ?? '-' }}">
                                        {{ $crew->to_location ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 140px;" title="{{ $crew->remarks ?? '-' }}">
                                        {{ $crew->remarks ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if(!empty($crew->sub_remark) && $crew->sub_remark !== '-')
                                        <span class="badge-sub-remark">{{ $crew->sub_remark }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
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
    .dataTables_wrapper {
        width: 100%;
    }
    .dataTables_wrapper .dataTables_filter {
        text-align: right;
    }
    .dataTables_wrapper .dataTables_filter input {
        display: inline-block;
        width: auto;
        margin-left: 0.5rem;
    }
    .dataTables_wrapper .table-responsive {
        border: 1px solid #eff2f7;
        border-radius: 0.35rem;
    }
    .dt-buttons .btn {
        margin-right: 0.35rem !important;
    }
    .badge-sub-remark {
        color: #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.08);
        border: 1px solid rgba(220, 53, 69, 0.25);
        font-weight: 700 !important;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        display: inline-block;
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
    // Initialize DataTable with Export Buttons
    $(document).ready(function() {
        $('#tripSummaryTable').DataTable({
            dom: "<'row mb-3 align-items-center'<'col-sm-12 col-md-6 d-flex align-items-center gap-2'B><'col-sm-12 col-md-6 d-flex justify-content-md-end justify-content-start mt-2 mt-md-0'f>>" +
                 "<'row'<'col-sm-12'<'table-responsive'tr>>>" +
                 "<'row mt-3 align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-start mt-2 mt-md-0'p>>",
            autoWidth: false,
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="ri-file-excel-2-line me-1"></i> Export to Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Trip Summary Report',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
                        modifier: {
                            page: 'all',
                            search: 'none'
                        },
                        format: {
                            body: function(data, row, column, node) {
                                function stripHtml(html) {
                                    if (!html) return '';
                                    if (typeof html === 'string' && !/<[^>]+>/.test(html)) {
                                        return html.trim();
                                    }
                                    if (node && node.nodeType === 1) {
                                        return $(node).text().trim() || '';
                                    }
                                    if (typeof html === 'string') {
                                        var $temp = $('<div>').html(html);
                                        return $temp.text().trim() || html.replace(/<[^>]+>/g, '').trim();
                                    }
                                    return '';
                                }
                                return stripHtml(data);
                            }
                        }
                    },
                    filename: 'Trip_Summary_Report_' + new Date().toISOString().split('T')[0],
                    customize: function(xlsx) {
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        var styles = xlsx.xl['styles.xml'];

                        try {
                            var parser = new DOMParser();
                            var xmlDoc = parser.parseFromString(styles, 'text/xml');

                            // Add Red Font for Sub Remark
                            var fonts = xmlDoc.getElementsByTagName('fonts')[0];
                            var fontNode = xmlDoc.createElement('font');
                            fontNode.innerHTML = '<sz val="11"/><color rgb="FFDC3545"/><name val="Calibri"/><b/>';
                            fonts.appendChild(fontNode);
                            fonts.setAttribute('count', fonts.getElementsByTagName('font').length);
                            var redFontIdx = fonts.getElementsByTagName('font').length - 1;

                            // Add cell format XF for Red Sub Remark
                            var cellXfs = xmlDoc.getElementsByTagName('cellXfs')[0];
                            var redXf = xmlDoc.createElement('xf');
                            redXf.setAttribute('numFmtId', '0');
                            redXf.setAttribute('fontId', redFontIdx);
                            redXf.setAttribute('fillId', '0');
                            redXf.setAttribute('borderId', '0');
                            redXf.setAttribute('xfId', '0');
                            redXf.setAttribute('applyFont', '1');
                            cellXfs.appendChild(redXf);
                            var redStyleId = cellXfs.getElementsByTagName('xf').length - 1;

                            cellXfs.setAttribute('count', cellXfs.getElementsByTagName('xf').length);

                            var serializer = new XMLSerializer();
                            xlsx.xl['styles.xml'] = serializer.serializeToString(xmlDoc);

                            // Apply redStyleId to Sub Remark column (H) cells with non-empty content
                            $('row', sheet).each(function(rIdx) {
                                if (rIdx > 0) {
                                    var cell = $(this).find('c[r^="H"]');
                                    if (cell.length && cell.text().trim() !== '' && cell.text().trim() !== '-') {
                                        cell.attr('s', redStyleId);
                                    }
                                }
                            });
                        } catch (e) {
                            console.error('Error styling Excel export:', e);
                        }
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="ri-file-pdf-line me-1"></i> Export to PDF',
                    className: 'btn btn-danger btn-sm',
                    title: 'Trip Summary Report',
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
                                function stripHtml(html) {
                                    if (!html) return '';
                                    if (typeof html === 'string' && !/<[^>]+>/.test(html)) {
                                        return html.trim();
                                    }
                                    if (node && node.nodeType === 1) {
                                        return $(node).text().trim() || '';
                                    }
                                    if (typeof html === 'string') {
                                        var $temp = $('<div>').html(html);
                                        return $temp.text().trim() || html.replace(/<[^>]+>/g, '').trim();
                                    }
                                    return '';
                                }
                                return stripHtml(data);
                            }
                        }
                    },
                    filename: 'Trip_Summary_Report_' + new Date().toISOString().split('T')[0],
                    customize: function(doc) {
                        doc.info = doc.info || {};
                        doc.info.title = 'Trip Summary Report';
                        doc.info.author = '{{ config("app.name") }}';
                        doc.info.subject = 'Trip Summary Report';
                        
                        doc.header = function(currentPage, pageCount) {
                            return {
                                columns: [
                                    { text: '{{ config("app.name") }}', style: 'companyName', alignment: 'left', margin: [40, 20, 0, 0] },
                                    { text: 'Trip Summary Report', style: 'headerTitle', alignment: 'center', margin: [0, 20, 0, 0] },
                                    { text: 'Page ' + currentPage.toString() + ' of ' + pageCount, alignment: 'right', style: 'pageNumber', margin: [0, 20, 40, 0] }
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
                        
                        // Style the sub remark column in PDF
                        if (doc.content[doc.content.length - 1].table) {
                            var table = doc.content[doc.content.length - 1];
                            table.table.headerRows = 1;
                            
                            for (var i = 0; i < table.table.body.length; i++) {
                                var row = table.table.body[i];
                                for (var j = 0; j < row.length; j++) {
                                    if (i > 0 && j === 7) {
                                        // Sub Remark column (index 7) -> Red text (#dc3545), bold
                                        var txt = (typeof row[j] === 'object' && row[j].text) ? row[j].text : row[j];
                                        if (txt && txt !== '-' && txt.trim() !== '') {
                                            row[j] = {
                                                text: txt,
                                                color: '#dc3545',
                                                bold: true,
                                                fillColor: (i % 2 === 0) ? '#ffffff' : '#f9fafb'
                                            };
                                        }
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
            order: [[0, 'asc']],
            columnDefs: [
                {
                    targets: 8,
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
            }
        });
    });
</script>
@endpush
@endsection

