@if($status === 'pending' && $partnerRequests->total() > 0)
    <div class="partner-review-queue-banner mb-3" role="status">
        <i class="ri-time-line" aria-hidden="true"></i>
        <div>
            <strong>{{ $partnerRequests->total() }} {{ Str::plural('request', $partnerRequests->total()) }} awaiting review</strong>
            <span class="d-block small text-muted mt-1">Pending requests are highlighted below. Open a request to review crew details and approve or decline.</span>
        </div>
    </div>
@elseif(($pendingCount ?? 0) > 0 && $status !== 'pending')
    <div class="partner-review-queue-banner mb-3" role="status">
        <i class="ri-time-line" aria-hidden="true"></i>
        <div>
            <strong>{{ $pendingCount }} pending {{ Str::plural('request', $pendingCount) }} need review</strong>
            <a href="{{ route('partner-requests.index', ['status' => 'pending']) }}" class="ms-1">View pending queue</a>
        </div>
    </div>
@endif
