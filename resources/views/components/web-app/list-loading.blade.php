{{-- List loading feedback: three pulsing skeleton rows on mobile (where the
     table is hidden and this is the only in-place signal), plus the small
     spinner row for larger screens. wire:loading.delay avoids flicker on
     sub-100ms responses. --}}
<div wire:loading.delay wire:target="search,filter,gotoPage,nextPage,previousPage" role="status"
    aria-label="{{ __('web_app.resources.loading') }}">
    <div class="app-skeleton-list" aria-hidden="true">
        <div class="app-skeleton-row">
            <span class="app-skeleton app-skeleton-avatar"></span>
            <span class="app-skeleton-lines">
                <span class="app-skeleton app-skeleton-line is-w60"></span>
                <span class="app-skeleton app-skeleton-line is-w90"></span>
            </span>
        </div>
        <div class="app-skeleton-row">
            <span class="app-skeleton app-skeleton-avatar"></span>
            <span class="app-skeleton-lines">
                <span class="app-skeleton app-skeleton-line is-w45"></span>
                <span class="app-skeleton app-skeleton-line is-w75"></span>
            </span>
        </div>
        <div class="app-skeleton-row">
            <span class="app-skeleton app-skeleton-avatar"></span>
            <span class="app-skeleton-lines">
                <span class="app-skeleton app-skeleton-line is-w55"></span>
                <span class="app-skeleton app-skeleton-line is-w85"></span>
            </span>
        </div>
    </div>
    <div class="app-list-loading">
        <span class="app-list-spinner" aria-hidden="true"></span>
        <span>{{ __('web_app.resources.loading') }}</span>
    </div>
</div>
