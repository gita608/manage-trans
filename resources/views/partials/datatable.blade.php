@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function() {
            var selector = @json($selector ?? '.datatable');
            
            if ($(selector).length > 0) {
                var table = $(selector);
                
                // Dynamically count header columns
                var columnCount = table.find('thead th').length;
                
                // Remove placeholder row with colspan in tbody to avoid column count issues
                var $tbody = table.find('tbody');
                var hasNoDataRow = $tbody.find('tr').length === 1 && $tbody.find('td[colspan]').length > 0;
                if (hasNoDataRow) { $tbody.empty(); }
                
                // Guard: skip init if no header columns
                if (columnCount === 0) { return; }
                
                // Make the last column (Actions) non-orderable and non-searchable.
                // On narrow screens Responsive folds low-priority columns into a child
                // row that is always expanded. The identifying column stays in the row
                // itself so each record is recognisable; it is the second column, since
                // the first is usually a photo or id. Actions come next, then the rest.
                var priorityColumn = columnCount > 2 ? 1 : 0;

                // Collapsing is for phones and small tablets only. On wider screens the
                // full table is kept and scrolls horizontally inside its container,
                // which is how these lists are meant to be read.
                var collapseColumns = window.innerWidth < 992;

                var columnDefs = [
                    {
                        targets: priorityColumn,
                        responsivePriority: 1
                    },
                    {
                        targets: columnCount - 1,
                        orderable: false,
                        searchable: false,
                        responsivePriority: 2
                    }
                ];

                table.DataTable({
                    columnDefs: columnDefs,
                    autoWidth: false,
                    destroy: true,
                    responsive: collapseColumns ? { details: { type: 'inline' } } : false,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    order: @json($order ?? [[1, 'asc']]),
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        },
                        emptyTable: "No data available in table"
                    },
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                         '<"row"<"col-sm-12"B>>' +
                         '<"row"<"col-sm-12"tr>>' +
                         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    buttons: [
                        {
                            extend: 'copy',
                            className: 'btn btn-secondary btn-sm',
                            exportOptions: {
                                columns: ':not(.no-export)'
                            }
                        },
                        {
                            extend: 'csv',
                            className: 'btn btn-secondary btn-sm',
                            exportOptions: {
                                columns: ':not(.no-export)'
                            }
                        },
                        {
                            extend: 'excel',
                            className: 'btn btn-secondary btn-sm',
                            exportOptions: {
                                columns: ':not(.no-export)'
                            }
                        },
                        {
                            extend: 'pdf',
                            className: 'btn btn-secondary btn-sm',
                            exportOptions: {
                                columns: ':not(.no-export)'
                            }
                        },
                        {
                            extend: 'print',
                            className: 'btn btn-secondary btn-sm',
                            exportOptions: {
                                columns: ':not(.no-export)'
                            }
                        }
                    ]
                });
            }
        });
    </script>
@endpush
