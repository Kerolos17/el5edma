<section class="app-page-stack">
    <x-slot:title>{{ $serviceGroup->name }}</x-slot:title>

    {{-- Hero Section --}}
    <div class="app-hero-panel">
        <div class="flex items-start gap-4 sm:gap-6 flex-wrap sm:flex-nowrap">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden ring-4 ring-white/30 flex-shrink-0 bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                <span class="text-2xl sm:text-3xl font-bold text-blue-500 dark:text-blue-400">{{ mb_substr($serviceGroup->name, 0, 1) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xl sm:text-2xl font-bold">{{ $serviceGroup->name }}</h2>
                    <span class="app-status-pill {{ $serviceGroup->is_active ? 'tone-emerald' : 'tone-rose' }}">{{ $serviceGroup->is_active ? __('web_app.states.active') : __('web_app.states.inactive') }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($serviceGroup->leader)
                        {{ __('web_app.table.leader') }}: {{ $serviceGroup->leader->name }}
                    @endif
                    @if ($serviceGroup->serviceLeader)
                        &middot; {{ __('web_app.table.service_leader') }}: {{ $serviceGroup->serviceLeader->name }}
                    @endif
                </p>
            </div>
            <div class="flex gap-2 flex-shrink-0 self-start">
                @can('update', $serviceGroup)
                    <button type="button" wire:click="openServiceGroupForm({{ $serviceGroup->id }})" class="app-primary-button !text-sm !py-1.5">
                        <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                        {{ __('web_app.actions.edit') }}
                    </button>
                @endcan
                <a href="{{ route('app.service-groups') }}" wire:navigate class="app-secondary-button !text-sm">
                    <i class="ph ph-arrow-right" aria-hidden="true"></i>
                    {{ __('web_app.resources.service-groups.title') }}
                </a>
            </div>
        </div>
        @if ($serviceGroup->description)
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">{{ $serviceGroup->description }}</p>
        @endif
    </div>

    {{-- Stat Cards --}}
    <div class="app-stat-grid app-stat-grid-2x2">
        <div class="app-stat-card tone-blue">
            <div class="app-stat-icon"><i class="ph ph-users-three" aria-hidden="true"></i></div>
            <div>
                <p>{{ __('service_groups.beneficiaries_count') }}</p>
                <strong>{{ number_format($serviceGroup->beneficiaries->count()) }}</strong>
            </div>
        </div>
        <div class="app-stat-card tone-emerald">
            <div class="app-stat-icon"><i class="ph ph-hand-heart" aria-hidden="true"></i></div>
            <div>
                <p>{{ __('service_groups.servants_count') }}</p>
                <strong>{{ number_format($serviceGroup->servants->count()) }}</strong>
            </div>
        </div>
        <div class="app-stat-card tone-amber">
            <div class="app-stat-icon"><i class="ph ph-crown" aria-hidden="true"></i></div>
            <div>
                <p>{{ __('service_groups.leader') }}</p>
                <strong>{{ $serviceGroup->leader?->name ?? __('web_app.fallback.no_family_leader') }}</strong>
            </div>
        </div>
        <div class="app-stat-card tone-rose">
            <div class="app-stat-icon"><i class="ph ph-user-square" aria-hidden="true"></i></div>
            <div>
                <p>{{ __('service_groups.service_leader') }}</p>
                <strong>{{ $serviceGroup->serviceLeader?->name ?? __('web_app.fallback.unassigned') }}</strong>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Beneficiaries Section --}}
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label">{{ __('service_groups.beneficiaries_tab') }}</p>
                    <h3>{{ __('web_app.table.beneficiaries') }}</h3>
                </div>
                <span class="app-muted-badge">{{ $serviceGroup->beneficiaries->count() }}</span>
            </div>
            <div class="p-4">
                @forelse ($serviceGroup->beneficiaries as $beneficiary)
                    <article class="flex items-center justify-between gap-3 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-gray-500 dark:text-gray-400">{{ mb_substr($beneficiary->full_name, 0, 1) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold truncate">{{ $beneficiary->full_name }}</p>
                                @if ($beneficiary->assignedServant)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $beneficiary->assignedServant->name }}</p>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('app.beneficiary-profile', $beneficiary->id) }}" wire:navigate class="app-link-inline !text-sm">
                            <i class="ph ph-arrow-left" aria-hidden="true"></i>
                        </a>
                    </article>
                @empty
                    <div class="app-empty-state !py-8">
                        <i class="ph ph-users-three" aria-hidden="true"></i>
                        <p>{{ __('web_app.resources.empty_table') }}</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Servants Section --}}
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label">{{ __('service_groups.servants_tab') }}</p>
                    <h3>{{ __('web_app.table.servants') }}</h3>
                </div>
                <span class="app-muted-badge">{{ $serviceGroup->servants->count() }}</span>
            </div>
            <div class="p-4">
                @forelse ($serviceGroup->servants as $servant)
                    <article class="flex items-center gap-3 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-bold text-gray-500 dark:text-gray-400">{{ mb_substr($servant->name, 0, 1) }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold">{{ $servant->name }}</p>
                            @if ($servant->phone)
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $servant->phone }}</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="app-empty-state !py-8">
                        <i class="ph ph-hand-heart" aria-hidden="true"></i>
                        <p>{{ __('web_app.resources.empty_table') }}</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    @include('livewire.web-app.partials.modals.service-group-form')
</section>
