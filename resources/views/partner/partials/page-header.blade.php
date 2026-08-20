{{--
    Partner Portal Page Header

    @param string $title - Page title
    @param string|null $subtitle - Optional subtitle
    @param array $breadcrumbs - Breadcrumb items [['label' => '', 'url' => ''], ...]
--}}

<div class="row partner-page-header mb-3">
    <div class="col-12">
        <div class="d-sm-flex align-items-start justify-content-between gap-3">
            <div class="flex-grow-1 flex-text-safe min-width-0">
                <h4 class="partner-page-title mb-1">{{ $title }}</h4>
                @if(!empty($subtitle))
                    <p class="partner-page-subtitle text-muted mb-0">{{ $subtitle }}</p>
                @endif
            </div>

            @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
                <nav class="partner-page-breadcrumb flex-shrink-0" aria-label="Breadcrumb">
                    <ol class="breadcrumb m-0">
                        @foreach($breadcrumbs as $breadcrumb)
                            @if(isset($breadcrumb['url']))
                                <li class="breadcrumb-item">
                                    <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                                </li>
                            @else
                                <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['label'] }}</li>
                            @endif
                        @endforeach
                    </ol>
                </nav>
            @endif
        </div>
    </div>
</div>
