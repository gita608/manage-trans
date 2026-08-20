@extends('layouts.app')

@section('title', 'Partner Requests | ' . config('app.name'))

@section('content')
@include('partials.page-header', [
    'title' => 'Partner Requests',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Partner Requests'],
    ],
])

<div class="card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title mb-0">Request Queue</h5>
            <form method="GET" action="{{ route('partner-requests.index') }}" class="d-flex flex-wrap gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="submission_method" value="{{ $submissionMethod }}">
                <select name="partner_id" class="form-select form-select-sm" style="min-width: 180px;" onchange="this.form.submit()">
                    <option value="">All Partners</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" @selected((string) $partnerId === (string) $partner->id)>{{ $partner->title }}</option>
                    @endforeach
                </select>
                <input type="search" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search REQ or partner" style="min-width: 200px;">
                <button type="submit" class="btn btn-sm btn-primary">Search</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs nav-tabs-custom nav-primary mb-3 flex-wrap gap-1">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined', 'withdrawn' => 'Withdrawn', 'all' => 'All'] as $tabStatus => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $status === $tabStatus ? 'active' : '' }}"
                       href="{{ route('partner-requests.index', array_merge(request()->except('page'), ['status' => $tabStatus])) }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach(['all' => 'All Methods', 'manual' => 'Manual', 'image' => 'Image'] as $methodValue => $methodLabel)
                <a href="{{ route('partner-requests.index', array_merge(request()->except('page'), ['submission_method' => $methodValue])) }}"
                   class="btn btn-sm {{ $submissionMethod === $methodValue ? 'btn-primary' : 'btn-soft-primary' }}">
                    {{ $methodLabel }}
                </a>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Partner</th>
                        <th>Method</th>
                        <th>Submitted By</th>
                        <th>Submitted</th>
                        <th>Crew</th>
                        <th>Extraction</th>
                        <th>Partner Updated</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partnerRequests as $partnerRequest)
                        <tr>
                            <td class="fw-medium">{{ $partnerRequest->request_reference }}</td>
                            <td>{{ $partnerRequest->partner->title ?? 'N/A' }}</td>
                            <td>
                                @if($partnerRequest->submission_method === 'manual')
                                    <span class="badge bg-info-subtle text-info">Manual</span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary">Image</span>
                                @endif
                            </td>
                            <td>{{ $partnerRequest->partnerUser->name ?? 'N/A' }}</td>
                            <td>{{ $partnerRequest->submitted_at?->format('M d, Y g:i A') ?? 'N/A' }}</td>
                            <td>{{ $partnerRequest->items_count }} Crew</td>
                            <td>
                                @if($partnerRequest->isImage())
                                    @if($partnerRequest->extraction_status === 'completed')
                                        <span class="badge bg-success-subtle text-success">Completed</span>
                                    @elseif($partnerRequest->extraction_status === 'failed')
                                        <span class="badge bg-danger-subtle text-danger">Failed</span>
                                    @elseif($partnerRequest->extraction_status === 'processing')
                                        <span class="badge bg-warning-subtle text-warning">Processing</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $partnerRequest->partner_updated_at?->format('M d, Y g:i A') ?? '—' }}</td>
                            <td>
                                @include('partner-requests.partials.status-badge', ['status' => $partnerRequest->status])
                            </td>
                            <td class="text-end">
                                <a href="{{ route('partner-requests.show', $partnerRequest) }}" class="btn btn-sm btn-primary">
                                    {{ $partnerRequest->isPending() ? 'Review' : 'View' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No partner requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $partnerRequests->links() }}
        </div>
    </div>
</div>
@endsection
