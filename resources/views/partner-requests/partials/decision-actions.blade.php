<div class="review-actions-container mb-4">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <a href="{{ route('partner-requests.index') }}" class="btn btn-light">
            <i class="ri-arrow-left-line me-1"></i> Back to Queue
        </a>
        <div class="review-actions-primary">
            @if(auth()->user()->hasPermission('create_trips'))
                <button type="button" class="btn btn-success btn-review-approve" data-bs-toggle="modal" data-bs-target="#approveModal">
                    <i class="ri-checkbox-circle-line me-1"></i> Approve
                </button>
            @endif
            <button type="button" class="btn btn-danger btn-review-decline" data-bs-toggle="modal" data-bs-target="#declineModal">
                <i class="ri-close-circle-line me-1"></i> Decline
            </button>
        </div>
    </div>
</div>

<form action="{{ route('partner-requests.approve', $partnerRequest) }}" method="POST" id="approveForm" class="d-none">
    @csrf
    <input type="hidden" name="request_version" value="{{ $requestVersion }}">
</form>

<form action="{{ route('partner-requests.decline', $partnerRequest) }}" method="POST" id="declineForm" class="d-none">
    @csrf
    <input type="hidden" name="request_version" value="{{ $requestVersion }}">
    <input type="hidden" name="decline_reason" id="decline_reason_hidden">
</form>