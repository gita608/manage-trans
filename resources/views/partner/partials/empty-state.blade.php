{{--
    Partner Portal Empty State

    @param string $icon - Remix icon class
    @param string $title - Empty state title
    @param string $message - Empty state message
    @param slot $action - Optional action button
--}}

<div class="text-center py-5 partner-empty-state">
    <div class="partner-empty-state-icon mb-4">
        <i class="{{ $icon ?? 'ri-inbox-line' }}" aria-hidden="true"></i>
    </div>
    <h5 class="mb-3">{{ $title }}</h5>
    <p class="text-muted mb-3">{{ $message }}</p>
    @isset($action)
        {{ $action }}
    @endisset
</div>