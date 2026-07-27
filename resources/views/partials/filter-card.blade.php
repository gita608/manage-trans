{{--
    Props:
    - $title (string) default Filters
    - $action (string) form action URL
    - $method (string) default GET
    - $resetUrl (string|null)
    - $collapsed (bool) — start collapsed
    - Slot: $slot for filter fields (use @include with sections or capture)
    Use: wrap fields between include start/end by passing $fieldsHtml
--}}
@php
    $title = $title ?? 'Filters';
    $method = $method ?? 'GET';
    $collapsed = $collapsed ?? false;
    $collapseId = $collapseId ?? 'filterCollapse' . uniqid();
@endphp
<div class="card border shadow-sm mb-3">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">
            <i class="ri-filter-3-line me-1 align-middle"></i>{{ $title }}
        </h5>
        <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="collapse"
            data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $collapsed ? 'false' : 'true' }}">
            <i class="ri-arrow-down-s-line"></i>
        </button>
    </div>
    <div id="{{ $collapseId }}" class="collapse {{ $collapsed ? '' : 'show' }}">
        <div class="card-body">
            <form action="{{ $action }}" method="{{ $method }}" class="row g-3 align-items-end">
                {!! $fields !!}
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line me-1"></i>Apply
                    </button>
                    @isset($resetUrl)
                        <a href="{{ $resetUrl }}" class="btn btn-soft-secondary">Reset</a>
                    @endisset
                </div>
            </form>
        </div>
    </div>
</div>
