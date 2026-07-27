{{--
    Props:
    - $label (string)
    - $value (int|string) — used with counter when $useCounter is true
    - $icon (string) — remix icon class e.g. ri-user-line
    - $color (string) — primary|info|success|warning|danger|secondary
    - $url (string|null) — optional link
    - $linkText (string) — default "View Details"
    - $subtitle (string|null)
    - $colClass (string) — default col-xl-3 col-md-6
    - $useCounter (bool) — default true when value is numeric
--}}
@php
    $color = $color ?? 'primary';
    $colClass = $colClass ?? 'col-xl-3 col-md-6';
    $useCounter = $useCounter ?? is_numeric($value);
    $linkText = $linkText ?? 'View Details';
    $hasFooter = isset($subtitle) || isset($url);
@endphp
<div class="{{ $colClass }}">
    <div class="card card-animate border shadow-sm position-relative h-100">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <div class="avatar-sm flex-shrink-0 me-3">
                    <span class="avatar-title bg-{{ $color }}-subtle text-{{ $color }} rounded">
                        <i class="{{ $icon }} fs-4"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-12">{{ $label }}</p>
                </div>
            </div>
            <h3 class="{{ $hasFooter ? 'mb-3' : 'mb-0' }} fw-bold">
                @if($useCounter)
                    <span class="counter-value" data-target="{{ $value }}">0</span>
                @else
                    {{ $value }}
                @endif
            </h3>
            @isset($subtitle)
                <p class="text-muted mb-2 small">{!! $subtitle !!}</p>
            @endisset
            @isset($url)
                <a href="{{ $url }}" class="text-decoration-none text-muted small">
                    {{ $linkText }} <i class="ri-arrow-right-line align-middle"></i>
                </a>
            @endisset
        </div>
    </div>
</div>
