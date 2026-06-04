<section class="app-page-stack">
    <x-slot:title>{{ $title }}</x-slot:title>

    <div class="app-hero-panel">
        <div>
            <p class="app-section-label">{{ $roleLabel }}</p>
            <h2>{{ __('web_app.dashboard.greeting', ['name' => auth()->user()->name]) }}</h2>
        </div>
        <div class="app-hero-actions">
            <a href="{{ route('app.beneficiaries') }}" wire:navigate class="app-primary-button">
                <i class="ph ph-users-three" aria-hidden="true"></i>
                {{ __('web_app.actions.beneficiaries') }}
            </a>
            <a href="{{ route('app.visits') }}" wire:navigate class="app-secondary-button">
                <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                {{ __('web_app.actions.visits') }}
            </a>
        </div>
    </div>

    <div class="app-stat-grid">
        @foreach ($stats as $stat)
            <article class="app-stat-card tone-{{ $stat['tone'] }}">
                <div class="app-stat-icon">
                    <i class="ph {{ $stat['icon'] }}" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ $stat['label'] }}</p>
                    <strong>{{ number_format($stat['value']) }}</strong>
                </div>
            </article>
        @endforeach
    </div>

    <div class="app-dashboard-grid">
        {{-- Left Column: Recent Visits + Critical Cases --}}
        <div class="space-y-6">
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.dashboard.recent_activity') }}</p>
                        <h3>{{ __('web_app.dashboard.recent_visits') }}</h3>
                    </div>
                    <a href="{{ route('app.visits') }}" wire:navigate>{{ __('web_app.actions.view_all') }}</a>
                </div>

                <div class="app-activity-list">
                    @forelse ($recentVisits as $visit)
                        <article class="app-activity-row">
                            <div>
                                <strong>{{ $visit->beneficiary?->full_name ?? __('web_app.dashboard.unknown_name') }}</strong>
                                <span>{{ $visit->createdBy?->name ?? __('web_app.dashboard.unknown_user') }}</span>
                            </div>
                            <time>{{ $visit->visit_date?->format('Y-m-d') }}</time>
                        </article>
                    @empty
                        <div class="app-empty-state">
                            <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                            <p>{{ __('web_app.dashboard.empty_visits') }}</p>
                        </div>
                    @endforelse
                </div>
            </section>

            @if ($criticalCases->isNotEmpty())
                <section class="app-panel">
                    <div class="app-panel-header">
                        <div>
                            <p class="app-section-label">{{ __('web_app.dashboard.stats.critical_cases') }}</p>
                            <h3>{{ __('web_app.dashboard.critical_cases') }}</h3>
                        </div>
                        <a href="{{ route('app.beneficiaries', ['filter' => 'critical']) }}" wire:navigate>{{ __('web_app.actions.view_all') }}</a>
                    </div>

                    <div class="app-table-wrap">
                        <table class="app-table" aria-label="">
                            <thead>
                                <tr>
                                    <th>{{ __('web_app.table.name') }}</th>
                                    <th>{{ __('web_app.table.phone') }}</th>
                                    <th>{{ __('web_app.table.group') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($criticalCases as $beneficiary)
                                    <tr>
                                        <td><strong>{{ $beneficiary->full_name }}</strong></td>
                                        <td><span>{{ $beneficiary->phone ?? '—' }}</span></td>
                                        <td><span>{{ $beneficiary->serviceGroup?->name ?? '—' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="app-mobile-list">
                        @foreach ($criticalCases as $beneficiary)
                            <article class="app-mobile-card">
                                <strong>{{ $beneficiary->full_name }}</strong>
                                <div class="app-mobile-meta">
                                    <span>{{ $beneficiary->phone ?? '—' }}</span>
                                    <span>{{ $beneficiary->serviceGroup?->name ?? '—' }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- Right Column: Side Metrics + Birthdays + Unvisited --}}
        <div class="space-y-6">
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.dashboard.side_metrics') }}</p>
                        <h3>{{ __('web_app.dashboard.system_readiness') }}</h3>
                    </div>
                </div>

                <dl class="app-mini-metrics">
                    <div>
                        <dt>{{ __('web_app.dashboard.stats.open_prayer_requests') }}</dt>
                        <dd>{{ number_format($secondaryStats['openPrayerRequests']) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('web_app.dashboard.stats.medical_files') }}</dt>
                        <dd>{{ number_format($secondaryStats['medicalFiles']) }}</dd>
                    </div>
                    @if ($secondaryStats['users'] !== null)
                        <div>
                            <dt>{{ __('web_app.dashboard.stats.scoped_users') }}</dt>
                            <dd>{{ number_format($secondaryStats['users']) }}</dd>
                        </div>
                    @endif
                    @if ($secondaryStats['serviceGroups'] !== null)
                        <div>
                            <dt>{{ __('web_app.dashboard.stats.service_groups') }}</dt>
                            <dd>{{ number_format($secondaryStats['serviceGroups']) }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            @if ($todayBirthdays->isNotEmpty())
                <section class="app-panel">
                    <div class="app-panel-header">
                        <div>
                            <p class="app-section-label">{{ __('web_app.dashboard.today') }}</p>
                            <h3>&#x1F382; {{ __('web_app.dashboard.birthdays') }}</h3>
                        </div>
                    </div>

                    <div class="app-activity-list">
                        @foreach ($todayBirthdays as $beneficiary)
                            <article class="app-activity-row">
                                <div>
                                    <strong>{{ $beneficiary->full_name }}</strong>
                                    <span>{{ $beneficiary->serviceGroup?->name ?? '—' }}</span>
                                </div>
                                <span class="app-status-pill" style="background:#fef3c7;color:#92400e">
                                    {{ $beneficiary->birth_date?->format('M d') }}
                                </span>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($unvisited->isNotEmpty())
                <section class="app-panel">
                    <div class="app-panel-header">
                        <div>
                            <p class="app-section-label">{{ __('web_app.dashboard.attention') }}</p>
                            <h3>&#x23F0; {{ __('web_app.dashboard.unvisited') }}</h3>
                        </div>
                        <a href="{{ route('app.beneficiaries', ['filter' => 'needs_visit']) }}" wire:navigate>{{ __('web_app.actions.view_all') }}</a>
                    </div>

                    <div class="app-activity-list">
                        @foreach ($unvisited as $beneficiary)
                            <article class="app-activity-row">
                                <div>
                                    <strong>{{ $beneficiary->full_name }}</strong>
                                    <span>{{ $beneficiary->serviceGroup?->name ?? '—' }}</span>
                                </div>
                                <span class="app-status-pill" style="background:#fee2e2;color:#991b1b">
                                    {{ __('web_app.dashboard.never_visited') }}
                                </span>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>

    {{-- Visits Chart --}}
    @if ($visitsChart->isNotEmpty())
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label">{{ __('web_app.dashboard.stats.visits_this_month') }}</p>
                    <h3>{{ __('web_app.dashboard.visits_chart') }}</h3>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500 font-bold">{{ $visitsChart->sum() }} total</span>
            </div>

            <div class="pt-6 pb-4 px-2">
                @php $max = max(1, max($visitsChart->values()->toArray())); @endphp
                <div class="space-y-2">
                    @foreach ($visitsChart as $month => $count)
                        @php
                            $pct = ($count / $max) * 100;
                            $barColor = $count >= $max * 0.75 ? '#ef4444' : ($count >= $max * 0.5 ? '#f59e0b' : '#3b82f6');
                        @endphp
                        <div class="flex items-center gap-3">
                            <span class="w-12 text-xs font-bold text-gray-500 dark:text-gray-400 text-end flex-shrink-0">
                                {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M') }}
                            </span>
                            <div class="flex-1 h-7 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 relative">
                                <div class="h-full rounded-lg transition-all duration-500"
                                     style="width: {{ max(4, $pct) }}%; background: linear-gradient(135deg, {{ $barColor }}, {{ $barColor }}dd);">
                                </div>
                            </div>
                            <span class="w-8 text-xs font-bold text-gray-600 dark:text-gray-300 text-end flex-shrink-0">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</section>
