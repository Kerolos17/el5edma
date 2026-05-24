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

    <div class="app-pagination-wrap">
        {{ $records->onEachSide(1)->links() }}
    </div>

    {{-- Detail Modal --}}
    @if ($viewingLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             wire:click.self="closeView">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto">
                <div class="app-modal-header !mb-0 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ __('audit_logs.singular') }} #{{ $viewingLog->id }}
                    </h3>
                    <button wire:click="closeView" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('users.name') }}</span>
                            <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
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
                            <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                                {{ __("audit_logs.model_" . str(class_basename($viewingLog->model_type))->snake()) ?: class_basename($viewingLog->model_type) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('audit_logs.model_id') }}</span>
                            <p class="mt-1 font-mono text-sm font-bold text-gray-900 dark:text-white">#{{ $viewingLog->model_id }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('audit_logs.ip_address') }}</span>
                            <p class="mt-1 font-mono text-sm text-gray-900 dark:text-white">{{ $viewingLog->ip_address ?? '—' }}</p>
                        </div>
                    </div>

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

                <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-end">
                    <button wire:click="closeView" class="app-primary-button bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 !shadow-none">
                        <i class="ph ph-x" aria-hidden="true"></i>
                        {{ __('web_app.actions.close') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</section>
