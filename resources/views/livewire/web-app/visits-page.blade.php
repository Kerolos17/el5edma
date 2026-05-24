<section class="app-page-stack">
    <x-slot:title>{{ $meta['title'] }}</x-slot:title>

    <div class="app-hero-panel">
        <div>
            <h2>{{ $meta['title'] }}</h2>
        </div>
        <div class="app-hero-actions">
            <button type="button" wire:click="openVisitForm" class="app-primary-button">
                <i class="ph ph-plus" aria-hidden="true"></i>
                {{ __('web_app.actions.record_visit') }}
            </button>
            <a href="{{ route('app.beneficiaries') }}" wire:navigate class="app-secondary-button">
                <i class="ph ph-users-three" aria-hidden="true"></i>
                {{ __('web_app.actions.beneficiaries') }}
            </a>
        </div>
    </div>

    <div class="app-stat-grid app-stat-grid-compact">
        @foreach ($stats as $stat)
            <article class="app-stat-card tone-{{ $stat['tone'] }}">
                <div>
                    <p>{{ $stat['label'] }}</p>
                    <strong>{{ number_format($stat['value']) }}</strong>
                </div>
            </article>
        @endforeach
    </div>

    <section class="app-panel app-toolbar-panel">
        <div class="app-toolbar">
            <label class="app-search-field">
                <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('web_app.resources.search_placeholder', ['title' => $meta['title']]) }}">
            </label>
            <div class="app-chip-row" role="tablist">
                @foreach ($filters as $item)
                    <button type="button" wire:click="$set('filter', '{{ $item['value'] }}')"
                        class="app-filter-chip {{ $filter === $item['value'] ? 'is-active' : '' }}">
                        {{ $item['label'] }}
                    </button>
                @endforeach
            </div>
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

        <div class="app-table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('web_app.table.beneficiary') }}</th>
                        <th>{{ __('web_app.table.date') }}</th>
                        <th>{{ __('web_app.table.type') }}</th>
                        <th>{{ __('web_app.table.created_by') }}</th>
                        <th>{{ __('web_app.table.follow_up') }}</th>
                        <th>{{ __('web_app.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>
                                <strong>{{ $record->beneficiary?->full_name ?? __('web_app.fallback.no_name') }}</strong>
                                <span>{{ $record->beneficiary?->serviceGroup?->name ?? __('web_app.fallback.no_group') }}</span>
                            </td>
                            <td>{{ optional($record->visit_date)->format('Y-m-d') }}</td>
                            <td>{{ $record->type ? __("visits.{$record->type}") : __('visits.singular') }}</td>
                            <td>{{ $record->createdBy?->name ?? __('web_app.fallback.unassigned') }}</td>
                            <td>
                                @if ($record->is_critical)
                                    <span class="app-status-pill tone-rose">{{ __('web_app.states.critical') }}</span>
                                @elseif ($record->needs_family_leader || $record->needs_service_leader)
                                    <span class="app-status-pill tone-amber">{{ __('web_app.states.needs_follow_up') }}</span>
                                @else
                                    <span class="app-status-pill tone-emerald">{{ __('web_app.states.stable') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="app-inline-actions">
                                    @can('update', $record)
                                        <button type="button" wire:click="editVisit({{ $record->id }})" class="app-link-inline">
                                            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                                            {{ __('web_app.actions.edit') }}
                                        </button>
                                        @if ($record->is_critical || $record->needs_family_leader || $record->needs_service_leader)
                                            <button type="button" wire:click="resolveVisitFollowUp({{ $record->id }})" class="app-link-inline">
                                                <i class="ph ph-check-circle" aria-hidden="true"></i>
                                                {{ __('web_app.actions.close_follow_up') }}
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-gray-500 dark:text-gray-400">
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
                    <strong>{{ $record->beneficiary?->full_name ?? __('web_app.fallback.no_name') }}</strong>
                    <p>{{ optional($record->visit_date)->format('Y-m-d') }} · {{ $record->type ? __("visits.{$record->type}") : __('visits.singular') }}</p>
                    <div class="app-mobile-meta">
                        <span>{{ $record->createdBy?->name ?? __('web_app.fallback.unassigned') }}</span>
                        <span>{{ $record->is_critical ? __('web_app.states.critical') : __('web_app.states.stable') }}</span>
                    </div>
                </article>
            @empty
                <div class="app-empty-state">
                    <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                    <p>{{ __('web_app.resources.empty_table') }}</p>
                </div>
            @endforelse
        </div>

        @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="app-pagination-wrap">
                {{ $records->onEachSide(1)->links() }}
            </div>
        @endif

    @include('livewire.web-app.partials.modals.index')
</section>