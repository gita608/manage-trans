{{-- Shown only on iOS, where Safari has no install prompt API --}}
<div class="modal fade" id="pwa-ios-help" tabindex="-1" aria-labelledby="pwa-ios-help-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pwa-ios-help-title">Add to Home Screen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Install {{ getSetting('app_name', config('app.name')) }} for a full-screen app with its own home screen icon.</p>
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Tap <i class="ri-share-line align-middle text-primary"></i> <strong>Share</strong> in the Safari toolbar.</li>
                    <li class="mb-2">Choose <strong>Add to Home Screen</strong>.</li>
                    <li>Tap <strong>Add</strong> to confirm.</li>
                </ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft-secondary" id="pwa-install-dismiss" data-bs-dismiss="modal">Don't show again</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ assetVersioned('assets/js/pwa.js') }}"></script>
