<div class="review-actions-container mb-4">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <a href="{{ route('partner-requests.index') }}" class="btn btn-light">
            <i class="ri-arrow-left-line me-1"></i> Back to Queue
        </a>
        <div class="review-actions-primary">
            @if($canApprove)
                <button type="button" class="btn btn-success btn-review-approve" id="approveBtn">
                    <i class="ri-checkbox-circle-line me-1"></i> Approve
                </button>
            @endif
            @if($canDecline)
                <button type="button" class="btn btn-danger btn-review-decline" data-bs-toggle="modal" data-bs-target="#declineModal">
                    <i class="ri-close-circle-line me-1"></i> Decline
                </button>
            @endif
        </div>
    </div>
</div>

@if($canApprove)
<form action="{{ route('partner-requests.approve', $partnerRequest) }}" method="POST" id="approveForm" class="d-none">
    @csrf
    <input type="hidden" name="request_version" value="{{ $requestVersion }}">
</form>
@endif

@if($canDecline)
<form action="{{ route('partner-requests.decline', $partnerRequest) }}" method="POST" id="declineForm" class="d-none">
    @csrf
    <input type="hidden" name="request_version" value="{{ $requestVersion }}">
    <input type="hidden" name="decline_reason" id="decline_reason_hidden">
</form>
@endif