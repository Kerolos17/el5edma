<section class="app-page-stack">
    <x-slot:title>{{ __('audit_logs.title') }}</x-slot:title>

    <div class="app-page-actions">
        <a href="{{ route('app.dashboard') }}" wire:navigate class="app-secondary-button">
            <i class="ph ph-squares-four" aria-hidden="true"></i>
            {{ __('web_app.actions.dashboard') }}
        </a>
    </div>

    {{-- Stats --}}
    <div class="app-stat-grid">
        @foreach ($stats as $stat)
            <article class="app-stat-card tone-{{ $stat['tone'] }}">
                <div>
                    <p>{{ $stat['label'] }}</p>
                    <strong>{{ $stat['value'] }}</strong>
                </div>
            </article>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="app-toolbar">
        <div class="app-chip-row">
            <button type="button" wire:click="$set('filterAction', 'all')"
                    class="app-filter-chip {{ $filterAction === 'all' ? 'is-active' : '' }}">
                {{ __('web_app.filters.all') }}
            </button>
            <button type="button" wire:click="$set('filterAction', 'created')"
                    class="app-filter-chip {{ $filterAction === 'created' ? 'is-active' : '' }}">
                {{ __('audit_logs.created') }}
            </button>
            <button type="button" wire:click="$set('filterAction', 'updated')"
                    class="app-filter-chip {{ $filterAction === 'updated' ? 'is-active' : '' }}">
                {{ __('audit_logs.updated') }}
            </button>
            <button type="button" wire:click="$set('filterAction', 'deleted')"
                    class="app-filter-chip {{ $filterAction === 'deleted' ? 'is-active' : '' }}">
                {{ __('audit_logs.deleted') }}
            </button>
        </div>

        <div class="app-chip-row">
            <button type="button" wire:click="$set('filterModel', 'all')"
                    class="app-filter-chip {{ $filterModel === 'all' ? 'is-active' : '' }}">
                {{ __('web_app.filters.all') }}
            </button>
            <button type="button" wire:click="$set('filterModel', 'beneficiary')"
                    class="app-filter-chip {{ $filterModel === 'beneficiary' ? 'is-active' : '' }}">
                {{ __('audit_logs.model_beneficiary') }}
            </button>
            <button type="button" wire:click="$set('filterModel', 'visit')"
                    class="app-filter-chip {{ $filterModel === 'visit' ? 'is-active' : '' }}">
                {{ __('audit_logs.model_visit') }}
            </button>
            <button type="button" wire:click="$set('filterModel', 'user')"
                    class="app-filter-chip {{ $filterModel === 'user' ? 'is-active' : '' }}">
                {{ __('audit_logs.model_user') }}
            </button>
            <button type="button" wire:click="$set('filterModel', 'service_group')"
                    class="app-filter-chip {{ $filterModel === 'service_group' ? 'is-active' : '' }}">
                {{ __('audit_logs.model_service_group') }}
            </button>
            <button type="button" wire:click="$set('filterModel', 'scheduled_visit')"
                    class="app-filter-chip {{ $filterModel === 'scheduled_visit' ? 'is-active' : '' }}">
                {{ __('audit_logs.model_scheduled_visit') }}
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="app-table-wrap">
        <table class="app-table">
            <thead>
                <tr>
                    <th>{{ __('users.name') }}</th>
                    <th>{{ __('audit_logs.model') }}</th>
                    <th>{{ __('audit_logs.model_id') }}</th>
                    <th>{{ __('audit_logs.action') }}</th>
                    <th>{{ __('beneficiaries.created_at') }}</th>
                    <th>{{ __('web_app.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $log)
                    <tr>
                        <td>
                            <strong>{{ $log->user?->name ?? __('web_app.fallback.deleted_user') }}</strong>
                        </td>
                        <td>
                            <span class="app-status-pill">
                                {{ __("audit_logs.model_" . str(class_basename($log->model_type))->snake()) ?: class_basename($log->model_type) }}
                            </span>
                        </td>
                        <td>
                            <strong class="font-mono">#{{ $log->model_id }}</strong>
                        </td>
                        <td>
                            <span class="app-status-pill"
                                  style="background: {{ match($log->action) {
                                      'created' => '#d1fae5',
                                      'updated' => '#fef3c7',
                                      'deleted' => '#ffe4e6',
                                      default   => '#f1f5f9',
                                  } }}; color: {{ match($log->action) {
                                      'created' => '#065f46',
                                      'updated' => '#92400e',
                                      'deleted' => '#9f1239',
                                      default   => '#475569',
                                  } }};">
                                {{ __("audit_logs.{$log->action}") }}
                            </span>
                        </td>
                        <td>
                            <span>{{ $log->created_at->format('Y-m-d H:i') }}</span>
                        </td>
                        <td>
                            <button wire:click="viewLog({{ $log->id }})" class="app-link-inline" style="margin-top:0">
                                <i class="ph ph-eye" aria-hidden="true"></i>
                                {{ __('web_app.actions.view') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="app-empty-state">
                                <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                                <p>{{ __('audit_logs.no_records') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Card List --}}
    <div class="app-mobile-list">
        @forelse ($records as $log)
            <article class="app-mobile-card">
                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <strong>{{ $log->user?->name ?? __('web_app.fallback.deleted_user') }}</strong>
                        <p>
                            <span class="app-status-pill" style="font-size:0.65rem;padding:0.1rem 0.4rem">
                                {{ __("audit_logs.model_" . str(class_basename($log->model_type))->snake()) ?: class_basename($log->model_type) }}
                            </span>
                            <span class="font-mono ms-1">#{{ $log->model_id }}</span>
                        </p>
                    </div>
                    <span class="app-status-pill"
                          style="background: {{ match($log->action) {
                              'created' => '#d1fae5',
                              'updated' => '#fef3c7',
                              'deleted' => '#ffe4e6',
                              default   => '#f1f5f9',
                          } }}; color: {{ match($log->action) {
                              'created' => '#065f46',
                              'updated' => '#92400e',
                              'deleted' => '#9f1239',
                              default   => '#475569',
                          } }};">
                        {{ __("audit_logs.{$log->action}") }}
                    </span>
                </div>
                <div class="app-mobile-meta">
                    <span>{{ $log->created_at->format('Y-m-d H:i') }}</span>
                    <button wire:click="viewLog({{ $log->id }})" class="app-link-inline" style="margin-top:0">
                        <i class="ph ph-eye" aria-hidden="true"></i>
                        {{ __('web_app.actions.view') }}
                    </button>
                </div>
            </article>
        @empty
            <div class="app-empty-state">
                <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                <p>{{ __('audit_logs.no_records') }}</p>
            </div>
        @endforelse
    </div>

    <div class="app-pagination-wrap">
        {{ $records->onEachSide(1)->links() }}
    </div>

    {{-- Detail Modal --}}
    @if ($viewingLog)
        <div class="app-modal-backdrop" wire:click.self="closeView"></div>
        <div class="app-modal-sheet">
            <div class="app-modal-panel app-modal-panel-wide">
                <div class="app-modal-header">
                    <h3>{{ __('audit_logs.singular') }} #{{ $viewingLog->id }}</h3>
                    <button type="button" wire:click="closeView" class="app-icon-button !border-0 !w-8 !h-8">
                        <i class="ph ph-x" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('users.name') }}</span>
                        <p class="mt-1 text-sm font-bold">
                            {{ $viewingLog->user?->name ?? __('web_app.fallback.deleted_user') }}
                            <span class="text-gray-400 font-normal text-xs">@ {{ $viewingLog->created_at->format('Y-m-d H:i') }}</span>
                        </p>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('audit_logs.action') }}</span>
                        <p class="mt-1">
                            <span class="app-status-pill"
                                  style="background: {{ match($viewingLog->action) {
                                      'created' => '#d1fae5',
                                      'updated' => '#fef3c7',
                                      'deleted' => '#ffe4e6',
                                      default   => '#f1f5f9',
                                  } }}; color: {{ match($viewingLog->action) {
                                      'created' => '#065f46',
                                      'updated' => '#92400e',
                                      'deleted' => '#9f1239',
                                      default   => '#475569',
                                  } }};">
                                {{ __("audit_logs.{$viewingLog->action}") }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('audit_logs.model') }}</span>
                        <p class="mt-1 text-sm font-bold">
                            {{ __("audit_logs.model_" . str(class_basename($viewingLog->model_type))->snake()) ?: class_basename($viewingLog->model_type) }}
                        </p>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('audit_logs.model_id') }}</span>
                        <p class="mt-1 font-mono text-sm font-bold">#{{ $viewingLog->model_id }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('audit_logs.ip_address') }}</span>
                        <p class="mt-1 font-mono text-sm">{{ $viewingLog->ip_address ?? '—' }}</p>
                    </div>
                </div>

                <div class="mt-5 space-y-5">
                    @if ($viewingLog->old_values)
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-bold mb-1 block">{{ __('audit_logs.old_values') }}</span>
                            <pre class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 text-xs overflow-x-auto max-h-48 leading-relaxed border border-gray-200 dark:border-gray-700">{{ json_encode($viewingLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    @if ($viewingLog->new_values)
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-bold mb-1 block">{{ __('audit_logs.new_values') }}</span>
                            <pre class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 text-xs overflow-x-auto max-h-48 leading-relaxed border border-gray-200 dark:border-gray-700">{{ json_encode($viewingLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    @if (! $viewingLog->old_values && ! $viewingLog->new_values)
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('audit_logs.no_records') }}</p>
                    @endif
                </div>

                <div class="app-modal-actions">
                    <button type="button" wire:click="closeView" class="app-secondary-button">
                        <i class="ph ph-x" aria-hidden="true"></i>
                        {{ __('web_app.actions.close') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</section>
