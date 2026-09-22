{{-- Mobile-visible loading feedback for list search/filter/pagination.
     The desktop table is hidden on phones, so this row is the only
     progress signal on small screens. --}}
<div wire:loading.delay wire:target="search,filter,gotoPage,nextPage,previousPage" class="app-list-loading" role="status">
    <span class="app-list-spinner" aria-hidden="true"></span>
    <span>{{ __('web_app.resources.loading') }}</span>
</div>
