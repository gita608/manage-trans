{{--
    Partner Request Status Badge

    @param string $status - Request status (pending|approved|declined|withdrawn)
    @param bool $withIcon - Show icon (default: true)
--}}

@php
    $withIcon = $withIcon ?? true;
    $badgeClass = match($status) {
        'pending' => 'bg-warning-subtle text-warning',
        'approved' => 'bg-success-subtle text-success',
        'declined' => 'bg-danger-subtle text-danger',
        'withdrawn' => 'bg-secondary-subtle text-secondary',
        default => 'bg-secondary-subtle text-secondary',
    };

    $icon = match($status) {
        'pending' => 'ri-time-line',
        'approved' => 'ri-checkbox-circle-line',
        'declined' => 'ri-close-circle-line',
        'withdrawn' => 'ri-arrow-go-back-line',
        default => 'ri-question-line',
    };

    $label = match($status) {
        'pending' => 'Pending',
        'approved' => 'Approved',
        'declined' => 'Declined',
        'withdrawn' => 'Withdrawn',
        default => ucfirst($status),
    };
@endphp

<span class="badge {{ $badgeClass }} status-badge" role="status" aria-label="Status: {{ $label }}">
    @if($withIcon)
        <i class="{{ $icon }}"></i>
    @endif
    {{ $label }}
</span>