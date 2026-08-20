{{--
    Props:
    - $title (string, required)
    - $breadcrumbs (array of ['label' => string, 'url' => string|null], optional)
    - $actions (HTML string / slot via $actionsHtml, optional)
--}}
@php
    $breadcrumbs = $breadcrumbs ?? [];
@endphp
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div class="flex-grow-1 flex-text-safe min-width-0">
                <h4 class="mb-sm-0">{{ $title }}</h4>
                @if(!empty($subtitle))
                    <p class="partner-review-subtitle text-muted mb-0 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            <div class="page-title-right d-flex align-items-center gap-2 flex-wrap">
                @isset($actions)
                    {!! $actions !!}
                @endisset
                @if(count($breadcrumbs))
                <ol class="breadcrumb m-0">
                    @foreach($breadcrumbs as $crumb)
                        @if(!empty($crumb['url']) && !$loop->last)
                            <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
                        @else
                            <li class="breadcrumb-item active">{{ $crumb['label'] }}</li>
                        @endif
                    @endforeach
                </ol>
                @endif
            </div>
        </div>
    </div>
</div>
