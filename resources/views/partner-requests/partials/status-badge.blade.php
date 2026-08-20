@php
    $statusConfig = match ($status) {
        'pending' => [
            'class' => 'bg-warning-subtle text-warning',
            'icon' => 'ri-time-line',
            'label' => 'Pending'
        ],
        'approved' => [
            'class' => 'bg-success-subtle text-success',
            'icon' => 'ri-checkbox-circle-line',
            'label' => 'Approved'
        ],
        'declined' => [
            'class' => 'bg-danger-subtle text-danger',
            'icon' => 'ri-close-circle-line',
            'label' => 'Declined'
        ],
        'withdrawn' => [
            'class' => 'bg-secondary-subtle text-secondary',
            'icon' => 'ri-arrow-go-back-line',
            'label' => 'Withdrawn'
        ],
        default => [
            'class' => 'bg-light text-dark',
            'icon' => 'ri-question-line',
            'label' => ucfirst($status)
        ],
    };
@endphp
<span class="badge {{ $statusConfig['class'] }}" role="status" aria-label="Status: {{ $statusConfig['label'] }}">
    <i class="{{ $statusConfig['icon'] }} me-1" aria-hidden="true"></i>
    {{ $statusConfig['label'] }}
</span>
