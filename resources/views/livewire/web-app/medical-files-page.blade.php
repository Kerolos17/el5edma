<section class="app-page-stack">
    <x-slot:title>{{ $meta['title'] }}</x-slot:title>
    <div class="app-hero-panel">
        <div><h2>{{ $meta['title'] }}</h2></div>
        <div class="app-hero-actions">
            @can('create', App\Models\MedicalFile::class)
                <button wire:click="openMedicalFileForm" class="app-primary-button"><i class="ph ph-upload-simple" aria-hidden="true"></i> {{ __('web_app.actions.upload_medical_file') }}</button>
            @endcan
            <a href="{{ route('app.prayer-requests') }}" wire:navigate class="app-secondary-button"><i class="ph ph-hands-praying" aria-hidden="true"></i> {{ __('web_app.actions.prayer_requests') }}</a>
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

        <div class="app-table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('web_app.table.title') }}</th>
                        <th>{{ __('web_app.table.type') }}</th>
                        <th>{{ __('web_app.table.beneficiary') }}</th>
                        <th>{{ __('web_app.table.uploaded_by') }}</th>
                        <th>{{ __('web_app.table.date') }}</th>
                        <th>{{ __('web_app.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td><strong>{{ $record->title ?? __('web_app.fallback.no_title') }}</strong></td>
                            <td>{{ $record->file_type ? __("medical.{$record->file_type}") : '—' }}</td>
                            <td>{{ $record->beneficiary?->full_name ?? '—' }}</td>
                            <td>{{ $record->uploadedBy?->name ?? '—' }}</td>
                            <td>{{ optional($record->created_at)->format('Y-m-d') }}</td>
                            <td>
                                <div class="app-inline-actions">
                                    <a href="{{ route('medical-files.download', $record->id) }}" class="app-link-inline">
                                        <i class="ph ph-download-simple" aria-hidden="true"></i>
                                        {{ __('web_app.actions.download') }}
                                    </a>
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
                    <strong>{{ $record->title ?? __('web_app.fallback.no_title') }}</strong>
                    <p>{{ $record->file_type ? __("medical.{$record->file_type}") : '—' }} · {{ optional($record->created_at)->format('Y-m-d') }}</p>
                    <div class="app-mobile-meta">
                        <span>{{ $record->beneficiary?->full_name ?? '—' }}</span>
                        <span>{{ $record->uploadedBy?->name ?? '—' }}</span>
                    </div>
                </article>
            @empty
                <div class="app-empty-state">
                    <i class="ph ph-file-lock" aria-hidden="true"></i>
                    <p>{{ __('web_app.resources.empty_table') }}</p>
                </div>
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