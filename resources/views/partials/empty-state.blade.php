{{--
    Props:
    - $icon (string) — remix icon class
    - $title (string)
    - $hint (string|null)
    - $actionUrl / $actionLabel (optional)
--}}
<div class="mt-empty-state text-center text-muted py-5 px-3">
    <i class="{{ $icon ?? 'ri-inbox-line' }} fs-1 mb-3 d-block opacity-50"></i>
    <h5 class="mb-1">{{ $title ?? 'Nothing here yet' }}</h5>
    @isset($hint)
        <p class="mb-3 small">{{ $hint }}</p>
    @endisset
    @if(!empty($actionUrl) && !empty($actionLabel))
        <a href="{{ $actionUrl }}" class="btn btn-sm btn-primary">{{ $actionLabel }}</a>
    @endif
</div>
