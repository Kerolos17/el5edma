<section class="app-page-stack">
    <x-slot:title>{{ $meta['title'] }}</x-slot:title>
    <div class="app-hero-panel">
        <div><h2>{{ $meta['title'] }}</h2></div>
        <div class="app-hero-actions">
            <button wire:click="openPrayerForm" class="app-primary-button"><i class="ph ph-plus" aria-hidden="true"></i> {{ __('web_app.actions.add_prayer_request') }}</button>
            <a href="{{ route('app.medical-files') }}" wire:navigate class="app-secondary-button"><i class="ph ph-file-lock" aria-hidden="true"></i> {{ __('web_app.actions.medical_files') }}</a>
        </div>
    </div>
    <div class="app-stat-grid app-stat-grid-compact">
        @foreach ($stats as $stat)<article class="app-stat-card tone-{{ $stat['tone'] }}"><div><p>{{ $stat['label'] }}</p><strong>{{ number_format($stat['value']) }}</strong></div></article>@endforeach
    </div>
    <section class="app-panel app-toolbar-panel">
        <div class="app-toolbar">
            <label class="app-search-field"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('web_app.resources.search_placeholder', ['title' => $meta['title']]) }}"></label>
            <div class="app-chip-row" role="tablist">@foreach ($filters as $item)<button wire:click="$set('filter', '{{ $item['value'] }}')" class="app-filter-chip {{ $filter === $item['value'] ? 'is-active' : '' }}">{{ $item['label'] }}</button>@endforeach</div>
        </div>
    </section>

    <section class="app-panel">
        <div class="app-panel-header">
            <div>
                <p class="app-section-label">{{ __('web_app.resources.operational_view') }}</p>
                <h3>{{ $meta['title'] }}</h3>
            </div>
            @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator)
                <span class="app-muted-badge">{{ trans_choice('web_app.resources.items_count', $records->total(), ['count' => number_format($records->total())]) }}</span>
            @endif
        </div>

        <div class="app-table-wrap" wire:loading.attr="aria-busy" wire:target="search,filter,gotoPage,nextPage,previousPage">
            <table class="app-table" aria-label="{{ $meta['title'] }}">
                <thead>
                    <tr>
                        <th scope="col">{{ __('web_app.table.title') }}</th>
                        <th scope="col">{{ __('web_app.table.beneficiary') }}</th>
                        <th scope="col">{{ __('web_app.table.created_by') }}</th>
                        <th scope="col">{{ __('web_app.table.date') }}</th>
                        <th scope="col">{{ __('web_app.table.status') }}</th>
                        <th scope="col">{{ __('web_app.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>
                                <strong>{{ $record->title }}</strong>
                                @if ($record->body)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">{{ $record->body }}</span>
                                @endif
                            </td>
                            <td>{{ $record->beneficiary?->full_name ?? '—' }}</td>
                            <td>{{ $record->createdBy?->name ?? '—' }}</td>
                            <td>{{ optional($record->created_at)->format('Y-m-d') }}</td>
                            <td><span class="app-status-pill @switch($record->status) @case('answered') tone-emerald @break @case('closed') tone-slate @break @default tone-blue @endswitch">{{ __("web_app.states.{$record->status}") }}</span></td>
                            <td>
                                <div class="app-inline-actions">
                                    @can('update', $record)
                                        <button type="button" wire:click="editPrayer({{ $record->id }})" class="app-link-inline">
                                            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                                            {{ __('web_app.actions.edit') }}
                                        </button>
                                        <button type="button" wire:click="closePrayerRequest({{ $record->id }})" wire:confirm="{{ __('web_app.confirm.close_prayer') }}" class="app-link-inline">
                                            <i class="ph ph-check-circle" aria-hidden="true"></i>
                                            {{ __('web_app.actions.close') }}
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="app-empty-cell">
                                {{ __('web_app.resources.empty_table') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="app-mobile-list">
            @forelse ($records as $record)
                <article class="app-mobile-card">
                    <strong>{{ $record->title }}</strong>
                    <p>{{ $record->beneficiary?->full_name ?? '—' }} · {{ optional($record->created_at)->format('Y-m-d') }}</p>
                    <div class="app-mobile-meta">
                        <span>{{ $record->createdBy?->name ?? '—' }}</span>
                        <span class="app-status-pill @switch($record->status) @case('answered') tone-emerald @break @case('closed') tone-slate @break @default tone-blue @endswitch">{{ __("web_app.states.{$record->status}") }}</span>
                    </div>
                </article>
            @empty
                <x-web-app.empty-state icon="ph-hands-praying" :message="__('web_app.resources.empty_table')" />
            @endforelse
        </div>

        @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="app-pagination-wrap">
                {{ $records->onEachSide(1)->links() }}
            </div>
        @endif
    </section>

    @include('livewire.web-app.partials.modals.index')
</section>