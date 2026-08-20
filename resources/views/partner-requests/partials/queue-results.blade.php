<div class="table-responsive d-none d-lg-block">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th scope="col">Reference</th>
                <th scope="col">Partner</th>
                <th scope="col">Method</th>
                <th scope="col">Submitted</th>
                <th scope="col">Crew</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($partnerRequests as $partnerRequest)
                <tr class="{{ $partnerRequest->isPending() ? 'partner-request-row-pending' : '' }}">
                    <td>
                        <span class="partner-request-reference text-break-safe">{{ $partnerRequest->request_reference }}</span>
                    </td>
                    <td class="text-break-safe">{{ $partnerRequest->partner->title ?? 'N/A' }}</td>
                    <td>
                        @if($partnerRequest->submission_method === 'manual')
                            <span class="badge bg-info-subtle text-info">
                                <i class="ri-edit-line me-1"></i> Manual
                            </span>
                        @else
                            <span class="badge bg-primary-subtle text-primary">
                                <i class="ri-image-line me-1"></i> Image
                            </span>
                        @endif
                    </td>
                    <td>
                        <span class="text-muted">{{ $partnerRequest->submitted_at?->format('M d, g:i A') ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="text-muted">{{ $partnerRequest->items_count }}</span>
                    </td>
                    <td>
                        @include('partner-requests.partials.status-badge', ['status' => $partnerRequest->status])
                    </td>
                    <td class="text-end">
                        <a href="{{ route('partner-requests.show', $partnerRequest) }}"
                           class="btn btn-sm {{ $partnerRequest->isPending() ? 'btn-primary' : 'btn-soft-primary' }}">
                            <i class="ri-{{ $partnerRequest->isPending() ? 'search-eye' : 'eye' }}-line me-1"></i>
                            {{ $partnerRequest->isPending() ? 'Review' : 'View' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="partner-review-empty">
                            <div class="partner-review-empty-icon">
                                <i class="ri-inbox-line" aria-hidden="true"></i>
                            </div>
                            <h6>No requests found</h6>
                            <p class="text-muted mb-0 small">No {{ $status !== 'all' ? $status : '' }} partner requests match your filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-lg-none">
    @forelse($partnerRequests as $partnerRequest)
        <div class="partner-review-queue-card {{ $partnerRequest->isPending() ? 'partner-request-row-pending' : '' }}">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <span class="partner-request-reference fw-bold text-break-safe">{{ $partnerRequest->request_reference }}</span>
                @include('partner-requests.partials.status-badge', ['status' => $partnerRequest->status])
            </div>
            <div class="row g-2 small mb-3">
                <div class="col-6">
                    <span class="text-muted d-block">Partner</span>
                    <span class="fw-medium text-break-safe">{{ $partnerRequest->partner->title ?? 'N/A' }}</span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">Method</span>
                    @if($partnerRequest->submission_method === 'manual')
                        <span class="badge bg-info-subtle text-info mt-1">Manual</span>
                    @else
                        <span class="badge bg-primary-subtle text-primary mt-1">Image</span>
                    @endif
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">Submitted</span>
                    <span>{{ $partnerRequest->submitted_at?->format('M d, g:i A') ?? 'N/A' }}</span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">Crew</span>
                    <span>{{ $partnerRequest->items_count }}</span>
                </div>
            </div>
            <a href="{{ route('partner-requests.show', $partnerRequest) }}"
               class="btn btn-sm {{ $partnerRequest->isPending() ? 'btn-primary' : 'btn-soft-primary' }} w-100">
                <i class="ri-{{ $partnerRequest->isPending() ? 'search-eye' : 'eye' }}-line me-1"></i>
                {{ $partnerRequest->isPending() ? 'Review' : 'View' }}
            </a>
        </div>
    @empty
        <div class="partner-review-empty">
            <div class="partner-review-empty-icon">
                <i class="ri-inbox-line" aria-hidden="true"></i>
            </div>
            <h6>No requests found</h6>
            <p class="text-muted mb-0 small">No {{ $status !== 'all' ? $status : '' }} partner requests match your filters.</p>
        </div>
    @endforelse
</div>

@if($partnerRequests->hasPages())
    <div class="mt-4">
        {{ $partnerRequests->links() }}
    </div>
@endif
