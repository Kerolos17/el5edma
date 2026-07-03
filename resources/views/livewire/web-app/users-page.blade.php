<section class="app-page-stack">
    <x-slot:title>{{ $meta['title'] }}</x-slot:title>
    <div class="app-hero-panel">
        <div>
            <h2>{{ $meta['title'] }}</h2>
        </div>
        <div class="app-hero-actions">
            @can('create', App\Models\User::class)
                <button type="button" wire:click="openUserForm" class="app-primary-button">
                    <i class="ph ph-user-plus" aria-hidden="true"></i>
                    {{ __('web_app.actions.add_user') }}
                </button>
            @endcan
            <a href="{{ route('app.service-groups') }}" wire:navigate class="app-secondary-button">
                <i class="ph ph-tree-structure" aria-hidden="true"></i>
                {{ __('web_app.actions.service_groups') }}
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
                        class="app-filter-chip {{ $filter === $item['value'] ? 'is-active' : '' }}">{{ $item['label'] }}</button>
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
                        <th scope="col">{{ __('web_app.table.user') }}</th>
                        <th scope="col">{{ __('web_app.table.email') }}</th>
                        <th scope="col">{{ __('web_app.table.phone') }}</th>
                        <th scope="col">{{ __('web_app.table.role') }}</th>
                        <th scope="col">{{ __('web_app.table.group') }}</th>
                        <th scope="col">{{ __('web_app.table.status') }}</th>
                        <th scope="col">{{ __('web_app.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template wire:loading wire:target="search,filter,gotoPage,nextPage,previousPage">
                        <x-web-app.table-skeleton :cols="7" :rows="6" />
                    </template>

                    <template wire:loading.remove wire:target="search,filter,gotoPage,nextPage,previousPage">
                    @forelse ($records as $record)
                        <tr>
                            <td><strong>{{ $record->name }}</strong></td>
                            <td>{{ $record->email ?? '—' }}</td>
                            <td>{{ $record->phone ?? '—' }}</td>
                            <td>{{ $record->role->label() }}</td>
                            <td>{{ $record->serviceGroup?->name ?? __('web_app.fallback.unassigned') }}</td>
                            <td><span class="app-status-pill {{ $record->is_active ? 'tone-emerald' : 'tone-rose' }}">{{ $record->is_active ? __('web_app.states.active') : __('web_app.states.inactive') }}</span></td>
                            <td>
                                <div class="app-inline-actions">
                                    @can('update', $record)
                                        <button type="button" wire:click="openUserForm({{ $record->id }})" class="app-link-inline">
                                            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                                            {{ __('web_app.actions.edit') }}
                                        </button>
                                        @if(!$record->is_active)
                                            <button type="button" wire:click="approveUser({{ $record->id }})" class="app-link-inline">
                                                <i class="ph ph-check-circle" aria-hidden="true"></i>
                                                {{ __('web_app.actions.approve') }}
                                            </button>
                                        @endif
                                    @endcan
                                    @can('delete', $record)
                                        <button type="button" wire:click="deleteUser({{ $record->id }})" wire:confirm="هل أنت متأكد من حذف هذا المستخدم؟" class="app-link-inline app-link-danger">
                                            <i class="ph ph-trash" aria-hidden="true"></i>
                                            {{ __('web_app.actions.delete') }}
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="app-empty-cell">
                                {{ __('web_app.resources.empty_table') }}
                            </td>
                        </tr>
                    @endforelse
                    </template>
                </tbody>
            </table>
        </div>

        <div class="app-mobile-list">
            @forelse ($records as $record)
                <article class="app-mobile-card">
                    <strong>{{ $record->name }}</strong>
                    <p>{{ $record->role->label() }} · {{ $record->serviceGroup?->name ?? __('web_app.fallback.unassigned') }}</p>
                    <div class="app-mobile-meta">
                        <span>{{ $record->email ?? '—' }}</span>
                        <span class="app-status-pill {{ $record->is_active ? 'tone-emerald' : 'tone-rose' }}">{{ $record->is_active ? __('web_app.states.active') : __('web_app.states.inactive') }}</span>
                    </div>
                </article>
            @empty
                <x-web-app.empty-state icon="ph-identification-card" :message="__('web_app.resources.empty_table')" />
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