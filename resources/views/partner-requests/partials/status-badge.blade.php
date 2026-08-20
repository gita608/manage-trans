@php
    $statusClass = match ($status) {
        'pending' => 'bg-warning-subtle text-warning',
        'approved' => 'bg-success-subtle text-success',
        'declined' => 'bg-danger-subtle text-danger',
        'withdrawn' => 'bg-secondary-subtle text-secondary',
        default => 'bg-light text-dark',
    };
@endphp
<span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
