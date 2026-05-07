<section class="app-panel app-toolbar-panel">
    <div class="app-toolbar">
        <label class="app-search-field">
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="ابحث بسرعة داخل {{ $meta['title'] }}"
                aria-label="بحث">
        </label>

        <div class="app-chip-row" role="tablist" aria-label="فلاتر">
            @foreach ($filters as $item)
                <button
                    type="button"
                    wire:click="$set('filter', '{{ $item['value'] }}')"
                    class="app-filter-chip {{ $filter === $item['value'] ? 'is-active' : '' }}"
                    aria-pressed="{{ $filter === $item['value'] ? 'true' : 'false' }}">
                    {{ $item['label'] }}
                </button>
            @endforeach
        </div>
    </div>
</section>
