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

        <div class="app-table-wrap" wire:loading.attr="aria-busy" wire:target="search,filter,gotoPage,nextPage,previousPage">
            <table class="app-table" aria-label="{{ $meta['title'] }}">
                <thead>
                    <tr>
                        <th scope="col">{{ __('web_app.table.beneficiary') }}</th>
                        <th scope="col">{{ __('web_app.table.date') }}</th>
                        <th scope="col">{{ __('web_app.table.type') }}</th>
                        <th scope="col">{{ __('web_app.table.created_by') }}</th>
                        <th scope="col">{{ __('web_app.table.follow_up') }}</th>
                        <th scope="col">{{ __('web_app.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>
                                <a href="{{ route('app.visit-profile', $record->id) }}" wire:navigate class="app-link-inline">
                                    <strong>{{ $record->beneficiary?->full_name ?? __('web_app.fallback.no_name') }}</strong>
                                </a>
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
                                    @can('delete', $record)
                                        <button type="button" wire:click="deleteVisit({{ $record->id }})" wire:confirm="هل أنت متأكد من حذف هذه الزيارة؟" class="app-link-inline app-link-danger">
                                            <i class="ph ph-trash" aria-hidden="true"></i>
                                            {{ __('web_app.actions.delete') }}
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
                <a href="{{ route('app.visit-profile', $record->id) }}" wire:navigate class="app-mobile-card">
                    <strong>{{ $record->beneficiary?->full_name ?? __('web_app.fallback.no_name') }}</strong>
                    <p>{{ optional($record->visit_date)->format('Y-m-d') }} · {{ $record->type ? __("visits.{$record->type}") : __('visits.singular') }}</p>
                    <div class="app-mobile-meta">
                        <span>{{ $record->createdBy?->name ?? __('web_app.fallback.unassigned') }}</span>
                        <span>{{ $record->is_critical ? __('web_app.states.critical') : __('web_app.states.stable') }}</span>
                    </div>
                </a>
            @empty
                <x-web-app.empty-state icon="ph-clipboard-text" :message="__('web_app.resources.empty_table')" />
            @endforelse
        </div>

        @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="app-pagination-wrap">
                {{ $records->onEachSide(1)->links() }}
            </div>
        @endif

    @include('livewire.web-app.partials.modals.index')
</section>