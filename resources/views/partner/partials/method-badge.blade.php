{{--
    Partner Request Submission Method Badge

    @param string $method - Submission method (manual|image)
--}}

@php
    $badgeClass = $method === 'manual'
        ? 'bg-info-subtle text-info'
        : 'bg-primary-subtle text-primary';

    $icon = $method === 'manual'
        ? 'ri-edit-line'
        : 'ri-image-line';

    $label = $method === 'manual' ? 'Manual' : 'Image';
@endphp

<span class="badge {{ $badgeClass }} method-badge" role="text" aria-label="Method: {{ $label }}">
    <i class="{{ $icon }}"></i>
    {{ $label }}
</span>