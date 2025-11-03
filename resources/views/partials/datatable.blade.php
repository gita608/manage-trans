@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.10/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
    <script>
        (function(){
            function loadScript(src){
                return new Promise(function(resolve, reject){
                    var s=document.createElement('script');
                    s.src=src; s.async=true; s.onload=resolve; s.onerror=reject;
                    document.head.appendChild(s);
                });
            }

            function onReady(cb){
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', cb);
                } else { cb(); }
            }

            onReady(async function(){
                try {
                    if (!window.jQuery) {
                        await loadScript('https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js');
                    }
                    if (!jQuery.fn.DataTable) {
                        await loadScript('https://cdn.jsdelivr.net/npm/datatables.net@1.13.10/js/jquery.dataTables.min.js');
                        await loadScript('https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.10/js/dataTables.bootstrap5.min.js');
                    }

                    var selector = @json($selector ?? '.datatable');
                    if (typeof selector === 'string' && document.querySelector(selector)) {
                        jQuery(selector).DataTable({
                            pageLength: 10,
                            lengthMenu: [10, 25, 50, 100],
                            order: [],
                        });
                    }
                } catch (e) {
                    console.error('DataTables init error:', e);
                }
            });
        })();
    </script>
@endpush


