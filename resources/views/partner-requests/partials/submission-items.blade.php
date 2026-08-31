{{-- Read-only partner submission snapshot --}}
<div class="card partner-review-card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
            <span class="partner-review-card-header-icon"><i class="ri-file-list-3-line" aria-hidden="true"></i></span>
            Partner Submission
        </h5>
    </div>
    <div class="card-body">
        @if($partnerRequest->items->isEmpty())
            <div class="partner-review-empty py-3">
                <div class="partner-review-empty-icon">
                    <i class="ri-inbox-line" aria-hidden="true"></i>
                </div>
                <h6>No crew rows submitted</h6>
                <p class="text-muted mb-0 small">
                    @if($partnerRequest->isImage())
                        Automatic extraction did not produce crew rows. You can still approve this request and enter operational details during trip creation.
                    @else
                        This request has no crew details on file.
                    @endif
                </p>
            </div>
        @else
            <div class="submission-items-list">
                @foreach($partnerRequest->items as $index => $item)
                    <div class="submission-item">
                        <div class="submission-item-header">
                            <span class="fw-semibold">Crew #{{ $index + 1 }}</span>
                            @if($item->name)
                                <span class="text-muted">— {{ $item->name }}</span>
                            @endif
                        </div>
                        <div class="submission-item-grid">
                            <div>
                                <label>Trip Date</label>
                                <div>{{ $item->trip_date?->format('M d, Y') ?? '—' }}</div>
                            </div>
                            <div>
                                <label>Name</label>
                                <div>{{ $item->name ?? '—' }}</div>
                            </div>
                            <div>
                                <label>Phone</label>
                                <div>{{ $item->phone ?? '—' }}</div>
                            </div>
                            <div>
                                <label>From</label>
                                <div>{{ $item->from_location ?? '—' }}</div>
                            </div>
                            <div>
                                <label>To</label>
                                <div>{{ $item->to_location ?? '—' }}</div>
                            </div>
                            <div>
                                <label>Vessel</label>
                                <div>
                                    @if($item->vessel)
                                        {{ $item->vessel->name }}
                                    @elseif($item->vessel_name_raw)
                                        <span class="text-muted">{{ $item->vessel_name_raw }}</span>
                                        <small class="d-block text-muted">(unmatched)</small>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            @if($item->pick_up_time)
                                <div>
                                    <label>Pickup Time</label>
                                    <div>{{ \Carbon\Carbon::parse($item->pick_up_time)->format('g:i A') }}</div>
                                </div>
                            @endif
                            @if($item->phone_2)
                                <div>
                                    <label>Phone 2</label>
                                    <div>{{ $item->phone_2 }}</div>
                                </div>
                            @endif
                            @if($partnerRequest->isImage() && $item->address)
                                <div>
                                    <label>Address</label>
                                    <div>{{ $item->address }}</div>
                                </div>
                            @endif
                            @if($item->flight_number)
                                <div>
                                    <label>Flight Number</label>
                                    <div>{{ $item->flight_number }}</div>
                                </div>
                            @endif
                            @if($item->remarks)
                                <div class="submission-item-span-2">
                                    <label>Remarks</label>
                                    <div>{{ $item->remarks }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>