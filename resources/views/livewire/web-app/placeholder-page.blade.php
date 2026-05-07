<section class="app-page-stack">
    <x-slot:title>{{ $meta['title'] }}</x-slot:title>

    @include('livewire.web-app.partials.hero')

    @include('livewire.web-app.partials.stats')

    @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator || $records instanceof \Illuminate\Support\Collection)
        @if (count($filters) > 0)
            @include('livewire.web-app.partials.toolbar')
        @endif

        @if ($reportCards->isNotEmpty())
            @include('livewire.web-app.partials.report-cards')
        @else
            @include('livewire.web-app.partials.resource-table')
        @endif
    @endif

    @include('livewire.web-app.partials.modals.index')
</section>
