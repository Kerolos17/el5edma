<section class="app-page-stack">
    <x-slot:title>{{ $meta['title'] }}</x-slot:title>

    <div class="app-hero-panel">
        <div>
            <h2>{{ $meta['title'] }}</h2>
        </div>
        <div class="app-hero-actions">
            @can('create', App\Models\Beneficiary::class)
                <button type="button" wire:click="openBeneficiaryForm" class="app-primary-button">
                    <i class="ph ph-user-plus" aria-hidden="true"></i>
                    {{ __('web_app.actions.add_beneficiary') }}
                </button>
            @endcan
            <a href="{{ route('app.visits') }}" wire:navigate class="app-secondary-button">
                <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                {{ __('web_app.actions.visits') }}
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
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="{{ __('web_app.resources.search_placeholder', ['title' => $meta['title']]) }}">
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
                        <th scope="col"></th>
                        <th scope="col">{{ __('web_app.table.beneficiary') }}</th>
                        <th scope="col">{{ __('web_app.table.group') }}</th>
                        <th scope="col">{{ __('web_app.table.servant') }}</th>
                        <th scope="col">{{ __('web_app.table.visits') }}</th>
                        <th scope="col">{{ __('web_app.table.status') }}</th>
                        <th scope="col">{{ __('web_app.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td class="!pr-0 !w-12">
                                <a href="{{ route('app.beneficiary-profile', $record->id) }}" wire:navigate>
                                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center">
                                        @if ($record->photo_url)
                                            <img src="{{ $record->photo_url }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-sm font-bold text-gray-400 dark:text-gray-500">{{ mb_substr($record->full_name, 0, 1) }}</span>
                                        @endif
                                    </div>
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('app.beneficiary-profile', $record->id) }}" wire:navigate class="font-bold text-decoration-none">
                                    <strong>{{ $record->full_name }}</strong>
                                    <span>{{ $record->code ?: __('web_app.fallback.no_code') }}</span>
                                </a>
                            </td>
                            <td>{{ $record->serviceGroup?->name ?? __('web_app.fallback.unassigned_feminine') }}</td>
                            <td>{{ $record->assignedServant?->name ?? __('web_app.fallback.unassigned') }}</td>
                            <td>{{ number_format($record->visits_count) }}</td>
                            <td><span class="app-status-pill tone-slate">{{ $record->status ? __("beneficiaries.{$record->status}") : __('beneficiaries.active') }}</span></td>
                            <td>
                                <div class="app-inline-actions">
                                    <a href="{{ route('app.beneficiary-profile', $record->id) }}" wire:navigate class="app-link-inline">
                                        <i class="ph ph-eye" aria-hidden="true"></i>
                                        {{ __('web_app.actions.view') }}
                                    </a>
                                    @can('update', $record)
                                        <button type="button" wire:click="openBeneficiaryForm({{ $record->id }})" class="app-link-inline">
                                            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                                            {{ __('web_app.actions.edit') }}
                                        </button>
                                    @endcan
                                    <button type="button" wire:click="openVisitForm({{ $record->id }})" class="app-link-inline">
                                        <i class="ph ph-plus" aria-hidden="true"></i>
                                        {{ __('web_app.actions.visit') }}
                                    </button>
                                    <button type="button" wire:click="openPrayerForm({{ $record->id }})" class="app-link-inline">
                                        <i class="ph ph-hands-praying" aria-hidden="true"></i>
                                        {{ __('web_app.actions.prayer') }}
                                    </button>
                                    @can('create', App\Models\MedicalFile::class)
                                        <button type="button" wire:click="openMedicalFileForm({{ $record->id }})" class="app-link-inline">
                                            <i class="ph ph-upload-simple" aria-hidden="true"></i>
                                            {{ __('web_app.actions.medical_file') }}
                                        </button>
                                    @endcan
                                    @can('delete', $record)
                                        <button type="button" wire:click="deleteBeneficiary({{ $record->id }})" wire:confirm="هل أنت متأكد من حذف هذا المخدوم؟" class="app-link-inline app-link-danger">
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
                </tbody>
            </table>
        </div>

        <div class="app-mobile-list">
            @forelse ($records as $record)
                <article class="app-mobile-card">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('app.beneficiary-profile', $record->id) }}" wire:navigate>
                            <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center">
                                @if ($record->photo_url)
                                    <img src="{{ $record->photo_url }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <span class="text-sm font-bold text-gray-400">{{ mb_substr($record->full_name, 0, 1) }}</span>
                                @endif
                            </div>
                        </a>
                        <div class="min-w-0">
                            <a href="{{ route('app.beneficiary-profile', $record->id) }}" wire:navigate class="font-bold text-decoration-none">
                                <strong>{{ $record->full_name }}</strong>
                            </a>
                            <p class="truncate">{{ $record->serviceGroup?->name ?? __('web_app.fallback.unassigned_feminine') }}</p>
                        </div>
                    </div>
                    <div class="app-mobile-meta">
                        <span>{{ $record->assignedServant?->name ?? __('web_app.fallback.unassigned') }}</span>
                        <span>{{ trans_choice('web_app.resources.visits_count', $record->visits_count, ['count' => number_format($record->visits_count)]) }}</span>
                    </div>
                    <div class="app-mobile-actions">
                        <a href="{{ route('app.beneficiary-profile', $record->id) }}" wire:navigate class="app-link-inline">
                            <i class="ph ph-eye" aria-hidden="true"></i>
                            {{ __('web_app.actions.view') }}
                        </a>
                        @can('update', $record)
                            <button type="button" wire:click="openBeneficiaryForm({{ $record->id }})" class="app-link-inline">
                                <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                                {{ __('web_app.actions.edit') }}
                            </button>
                        @endcan
                        <button type="button" wire:click="openVisitForm({{ $record->id }})" class="app-link-inline">
                            <i class="ph ph-plus" aria-hidden="true"></i>
                            {{ __('web_app.actions.visit') }}
                        </button>
                        <button type="button" wire:click="openPrayerForm({{ $record->id }})" class="app-link-inline">
                            <i class="ph ph-hands-praying" aria-hidden="true"></i>
                            {{ __('web_app.actions.prayer') }}
                        </button>
                        @can('create', App\Models\MedicalFile::class)
                            <button type="button" wire:click="openMedicalFileForm({{ $record->id }})" class="app-link-inline">
                                <i class="ph ph-upload-simple" aria-hidden="true"></i>
                                {{ __('web_app.actions.medical_file') }}
                                        </button>
                                    @endcan
                                    @can('delete', $record)
                                        <button type="button" wire:click="deleteBeneficiary({{ $record->id }})" wire:confirm="هل أنت متأكد من حذف هذا المخدوم؟" class="app-link-inline app-link-danger">
                                            <i class="ph ph-trash" aria-hidden="true"></i>
                                            {{ __('web_app.actions.delete') }}
                                        </button>
                                    @endcan
                                </div>
                            </article>
            @empty
                <x-web-app.empty-state
                    icon="ph-users-three"
                    :message="__('web_app.resources.empty_table')"
                />
            @endforelse
        </div>

        @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="app-pagination-wrap">
                {{ $records->onEachSide(1)->links() }}
            </div>
        @endif

    @include('livewire.web-app.partials.modals.index')
</section>