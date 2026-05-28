<section class="app-page-stack">
    <x-slot:title>{{ __('visits.singular') }} — {{ $visit->beneficiary?->full_name ?? __('web_app.fallback.no_name') }}</x-slot:title>

    {{-- Hero Section --}}
    <div class="app-hero-panel">
        <div class="flex items-start gap-4 sm:gap-6 flex-wrap sm:flex-nowrap">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden ring-4 ring-white/30 flex-shrink-0 bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                <span class="text-2xl sm:text-3xl font-bold text-indigo-500 dark:text-indigo-400">{{ mb_substr($visit->beneficiary?->full_name ?? '?', 0, 1) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xl sm:text-2xl font-bold">{{ $visit->beneficiary?->full_name ?? __('web_app.fallback.no_name') }}</h2>
                    @if ($visit->is_critical)
                        <span class="app-status-pill tone-rose">{{ __('web_app.states.critical') }}</span>
                    @elseif ($visit->needs_family_leader || $visit->needs_service_leader)
                        <span class="app-status-pill tone-amber">{{ __('web_app.states.needs_follow_up') }}</span>
                    @else
                        <span class="app-status-pill tone-emerald">{{ __('web_app.states.stable') }}</span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $visit->type ? __("visits.{$visit->type}") : __('visits.singular') }}
                    &middot; {{ optional($visit->visit_date)->format('Y-m-d') }}
                    @if ($visit->duration_minutes)
                        &middot; {{ $visit->duration_minutes }} {{ __('visits.minutes_short') }}
                    @endif
                </p>
                <div class="flex gap-2 mt-3 flex-wrap">
                    @if ($visit->beneficiary)
                        <a href="{{ route('app.beneficiary-profile', $visit->beneficiary->id) }}" wire:navigate class="app-secondary-button !text-sm !py-1.5">
                            <i class="ph ph-users-three" aria-hidden="true"></i>
                            {{ __('web_app.actions.view') }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="flex gap-2 flex-shrink-0 self-start">
                @can('update', $visit)
                    <button type="button" wire:click="editVisit({{ $visit->id }})" class="app-primary-button !text-sm !py-1.5">
                        <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                        {{ __('web_app.actions.edit') }}
                    </button>
                @endcan
                <a href="{{ route('app.visits') }}" wire:navigate class="app-secondary-button !text-sm">
                    <i class="ph ph-arrow-right" aria-hidden="true"></i>
                    {{ __('web_app.resources.visits.title') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Visit Info --}}
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label">{{ __('visits.visit_type') }}</p>
                    <h3>{{ __('visits.title') }}</h3>
                </div>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('visits.type') }}</span>
                    <span class="text-sm font-bold">{{ $visit->type ? __("visits.{$visit->type}") : __('visits.singular') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('visits.visit_date') }}</span>
                    <span class="text-sm font-bold">{{ optional($visit->visit_date)->format('Y-m-d g:i A') }}</span>
                </div>
                @if ($visit->duration_minutes)
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('visits.duration_minutes') }}</span>
                    <span class="text-sm font-bold">{{ $visit->duration_minutes }} {{ __('visits.minutes_short') }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('visits.beneficiary_status') }}</span>
                    <span class="text-sm font-bold">{{ $visit->beneficiary_status ? __("visits.{$visit->beneficiary_status}") : '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('web_app.table.created_by') }}</span>
                    <span class="text-sm font-bold">{{ $visit->createdBy?->name ?? __('web_app.fallback.unassigned') }}</span>
                </div>
            </div>
        </section>

        {{-- Follow-up Status --}}
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label">{{ __('visits.follow_up_required') }}</p>
                    <h3>{{ __('web_app.table.follow_up') }}</h3>
                </div>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('visits.is_critical') }}</span>
                    <span class="text-sm font-bold">{{ $visit->is_critical ? __('web_app.states.yes') : __('web_app.states.no') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('visits.needs_family_leader') }}</span>
                    <span class="text-sm font-bold">{{ $visit->needs_family_leader ? __('web_app.states.yes') : __('web_app.states.no') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('visits.needs_service_leader') }}</span>
                    <span class="text-sm font-bold">{{ $visit->needs_service_leader ? __('web_app.states.yes') : __('web_app.states.no') }}</span>
                </div>
                @if ($visit->critical_resolved_at)
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('visits.critical_resolved_by') }}</span>
                    <span class="text-sm font-bold">{{ $visit->resolvedBy?->name ?? __('web_app.fallback.unassigned') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('visits.critical_resolved_at') }}</span>
                    <span class="text-sm font-bold">{{ $visit->critical_resolved_at->format('Y-m-d g:i A') }}</span>
                </div>
                @endif
            </div>
        </section>
    </div>

    {{-- Beneficiary Info --}}
    @if ($visit->beneficiary)
    <section class="app-panel">
        <div class="app-panel-header">
            <div>
                <p class="app-section-label">{{ __('visits.beneficiary') }}</p>
                <h3>{{ $visit->beneficiary->full_name }}</h3>
            </div>
            <a href="{{ route('app.beneficiary-profile', $visit->beneficiary->id) }}" wire:navigate class="app-link-inline">
                <i class="ph ph-arrow-left" aria-hidden="true"></i>
                {{ __('web_app.actions.view') }}
            </a>
        </div>
        <div class="p-4 sm:p-6">
            <div class="app-stat-grid app-stat-grid-2x2">
                <div class="app-stat-card tone-blue">
                    <div class="app-stat-icon"><i class="ph ph-tree-structure" aria-hidden="true"></i></div>
                    <div>
                        <p>{{ __('web_app.table.group') }}</p>
                        <strong>{{ $visit->beneficiary->serviceGroup?->name ?? __('web_app.fallback.no_group') }}</strong>
                    </div>
                </div>
                @if ($visit->beneficiary->assignedServant)
                <div class="app-stat-card tone-emerald">
                    <div class="app-stat-icon"><i class="ph ph-hand-heart" aria-hidden="true"></i></div>
                    <div>
                        <p>{{ __('web_app.table.servant') }}</p>
                        <strong>{{ $visit->beneficiary->assignedServant->name }}</strong>
                    </div>
                </div>
                @endif
                @if ($visit->beneficiary->area)
                <div class="app-stat-card tone-amber">
                    <div class="app-stat-icon"><i class="ph ph-map-pin-area" aria-hidden="true"></i></div>
                    <div>
                        <p>{{ __('web_app.table.area') }}</p>
                        <strong>{{ $visit->beneficiary->area }}</strong>
                    </div>
                </div>
                @endif
                <div class="app-stat-card tone-rose">
                    <div class="app-stat-icon"><i class="ph ph-phone" aria-hidden="true"></i></div>
                    <div>
                        <p>{{ __('web_app.table.phone') }}</p>
                        <strong>{{ $visit->beneficiary->phone ?? '—' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Feedback --}}
    @if ($visit->feedback)
    <section class="app-panel">
        <div class="app-panel-header">
            <div>
                <p class="app-section-label">{{ __('visits.notes') }}</p>
                <h3>{{ __('visits.feedback') }}</h3>
            </div>
        </div>
        <div class="p-4 sm:p-6">
            <p class="text-sm whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ $visit->feedback }}</p>
        </div>
    </section>
    @endif

    {{-- Participating Servants --}}
    @if ($visit->servants->isNotEmpty())
    <section class="app-panel">
        <div class="app-panel-header">
            <div>
                <p class="app-section-label">{{ __('visits.visit_servants') }}</p>
                <h3>{{ __('visits.servants') }}</h3>
            </div>
            <span class="app-muted-badge">{{ $visit->servants->count() }}</span>
        </div>
        <div class="p-4">
            @foreach ($visit->servants as $servant)
                <article class="flex items-center gap-3 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-bold text-gray-500 dark:text-gray-400">{{ mb_substr($servant->name, 0, 1) }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold">{{ $servant->name }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
    @endif

    @include('livewire.web-app.partials.modals.visit-form')
</section>
