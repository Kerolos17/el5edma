<section class="app-page-stack">
    <x-slot:title>{{ $title }}</x-slot:title>

    <div class="app-hero-panel" data-role="{{ auth()->user()->role }}">
        <div>
            <p class="app-hero-kicker">{{ $roleLabel }} · {{ now()->isoFormat('dddd, D MMMM') }}</p>
            <h2>{{ __('web_app.dashboard.greeting', ['name' => auth()->user()->name]) }}</h2>
            <p class="app-hero-description">{{ __('web_app.dashboard.hero_title') }}</p>
        </div>
        <div class="app-hero-actions">
            <a href="{{ route('app.visits') }}" wire:navigate class="app-primary-button">
                <i class="ph ph-plus-circle" aria-hidden="true"></i>
                {{ __('web_app.actions.record_visit') }}
            </a>
            <a href="{{ route('app.beneficiaries') }}" wire:navigate class="app-secondary-button">
                <i class="ph ph-users-three" aria-hidden="true"></i>
                {{ __('web_app.actions.beneficiaries') }}
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
                        <a href="{{ route('app.visit-profile', $visit->id) }}" wire:navigate class="app-activity-row">
                            <div class="app-activity-body">
                                <span class="app-activity-icon type-{{ $visit->type }}">
                                    @switch($visit->type)
                                        @case('phone')<i class="ph ph-phone-call" aria-hidden="true"></i>@break
                                        @case('church')<i class="ph ph-church" aria-hidden="true"></i>@break
                                        @default<i class="ph ph-house-line" aria-hidden="true"></i>
                                    @endswitch
                                </span>
                                <div>
                                    <strong>{{ $visit->beneficiary?->full_name ?? __('web_app.dashboard.unknown_name') }}</strong>
                                    <span>{{ $visit->createdBy?->name ?? __('web_app.dashboard.unknown_user') }}</span>
                                </div>
                            </div>
                            <div class="app-activity-meta">
                                @if ($visit->is_critical)
                                    <span class="app-status-pill tone-rose">{{ __('web_app.dashboard.critical') }}</span>
                                @endif
                                <time>{{ $visit->visit_date?->isoFormat('D MMM') }}</time>
                            </div>
                        </a>
                    @empty
                        <div class="app-empty-state">
                            <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                            <p>{{ __('web_app.dashboard.empty_visits') }}</p>
                            <a href="{{ route('app.visits') }}" wire:navigate class="app-primary-button">
                                <i class="ph ph-plus-circle" aria-hidden="true"></i>
                                {{ __('web_app.actions.record_visit') }}
                            </a>
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
                        <table class="app-table">
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
                                        <td>
                                            <a href="{{ route('app.beneficiary-profile', $beneficiary->id) }}" wire:navigate class="app-link-inline">
                                                <strong>{{ $beneficiary->full_name }}</strong>
                                            </a>
                                        </td>
                                        <td><span>{{ $beneficiary->phone ?? '—' }}</span></td>
                                        <td><span><a href="{{ route('app.service-group-profile', $beneficiary->serviceGroup?->id) }}" wire:navigate class="app-link-inline">{{ $beneficiary->serviceGroup?->name ?? '—' }}</a></span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="app-mobile-list">
                        @foreach ($criticalCases as $beneficiary)
                            <a href="{{ route('app.beneficiary-profile', $beneficiary->id) }}" wire:navigate class="app-mobile-card">
                                <strong>{{ $beneficiary->full_name }}</strong>
                                <div class="app-mobile-meta">
                                    <span>{{ $beneficiary->phone ?? '—' }}</span>
                                    <span>{{ $beneficiary->serviceGroup?->name ?? '—' }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

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
                            <h3>{{ __('web_app.dashboard.birthdays') }}</h3>
                        </div>
                    </div>

                    <div class="app-activity-list">
                        @foreach ($todayBirthdays as $beneficiary)
                            <a href="{{ route('app.beneficiary-profile', $beneficiary->id) }}" wire:navigate class="app-activity-row">
                                <div class="app-activity-body">
                                    <span class="app-activity-icon type-birthday">
                                        <i class="ph ph-cake" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <strong>{{ $beneficiary->full_name }}</strong>
                                        <span>{{ $beneficiary->serviceGroup?->name ?? '—' }}</span>
                                    </div>
                                </div>
                                @if ($beneficiary->birth_date)
                                    <span class="app-status-pill tone-amber">{{ $beneficiary->birth_date->isoFormat('D MMM') }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($unvisited->isNotEmpty())
                <section class="app-panel">
                    <div class="app-panel-header">
                        <div>
                            <p class="app-section-label">{{ __('web_app.dashboard.attention') }}</p>
                            <h3>{{ __('web_app.dashboard.unvisited') }}</h3>
                        </div>
                        <a href="{{ route('app.beneficiaries', ['filter' => 'needs_visit']) }}" wire:navigate>{{ __('web_app.actions.view_all') }}</a>
                    </div>

                    <div class="app-activity-list">
                        @foreach ($unvisited as $beneficiary)
                            <a href="{{ route('app.beneficiary-profile', $beneficiary->id) }}" wire:navigate class="app-activity-row">
                                <div class="app-activity-body">
                                    <span class="app-activity-icon type-unvisited">
                                        <i class="ph ph-clock-countdown" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <strong>{{ $beneficiary->full_name }}</strong>
                                        <span>{{ $beneficiary->serviceGroup?->name ?? '—' }}</span>
                                    </div>
                                </div>
                                <span class="app-status-pill tone-rose">{{ __('web_app.dashboard.never_visited') }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>

    @if ($visitsChart->isNotEmpty())
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label">{{ __('web_app.dashboard.stats.visits_this_month') }}</p>
                    <h3>{{ __('web_app.dashboard.visits_chart') }}</h3>
                </div>
                <span class="app-chart-total">{{ __('web_app.resources.visits_count', ['count' => $visitsChart->sum()]) }}</span>
            </div>

            <div class="app-chart">
                @php $max = max(1, max($visitsChart->values()->toArray())); @endphp
                <div class="app-chart-bars">
                    @foreach ($visitsChart as $month => $count)
                        @php
                            $pct = ($count / $max) * 100;
                            $barLevel = $count >= $max * 0.75 ? 'high' : ($count >= $max * 0.5 ? 'mid' : 'low');
                            $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $month)->isoFormat('MMM');
                        @endphp
                        <div class="app-chart-bar-group">
                            <span class="app-chart-label">{{ $monthLabel }}</span>
                            <div class="app-chart-track">
                                <div class="app-chart-fill {{ $barLevel }}"
                                     style="width: {{ max(4, $pct) }}%; animation-delay: {{ $loop->index * 0.06 }}s">
                                </div>
                            </div>
                            <span class="app-chart-value">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</section>
