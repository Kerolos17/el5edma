<section class="app-page-stack">
    <x-slot:title>{{ __('web_app.shell.search_results_title') }}</x-slot:title>

    <div class="app-hero-panel">
        <div>
            <h2>{{ __('web_app.shell.search_results_title') }}</h2>
            @if ($term !== '')
                <p class="app-muted">{{ __('web_app.shell.search_results_for', ['term' => $term]) }}</p>
            @endif
        </div>
    </div>

    @if ($term === '')
        <section class="app-panel">
            <div class="app-empty-state">
                <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                <p>{{ __('web_app.shell.search_empty_hint') }}</p>
            </div>
        </section>
    @else
        {{-- المخدومون --}}
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label">{{ __('web_app.navigation.beneficiaries') }}</p>
                    <h3>{{ __('web_app.shell.search_beneficiaries') }}</h3>
                </div>
                @if ($beneficiaries->isNotEmpty())
                    <span class="app-muted-badge">{{ $beneficiaries->count() }}</span>
                @endif
            </div>
            @forelse ($beneficiaries as $record)
                <a href="{{ route('app.beneficiary-profile', ['beneficiary' => $record->id]) }}" wire:navigate
                    class="app-search-row">
                    <i class="ph ph-users-three" aria-hidden="true"></i>
                    <span class="app-search-row-title">{{ $record->full_name }}</span>
                    <span class="app-search-row-sub">{{ $record->code ?? '—' }}</span>
                </a>
            @empty
                <p class="app-search-none">{{ __('web_app.shell.search_no_results') }}</p>
            @endforelse
        </section>

        {{-- الزيارات --}}
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label">{{ __('web_app.navigation.visits') }}</p>
                    <h3>{{ __('web_app.shell.search_visits') }}</h3>
                </div>
                @if ($visits->isNotEmpty())
                    <span class="app-muted-badge">{{ $visits->count() }}</span>
                @endif
            </div>
            @forelse ($visits as $record)
                <a href="{{ route('app.visit-profile', ['visit' => $record->id]) }}" wire:navigate
                    class="app-search-row">
                    <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                    <span class="app-search-row-title">{{ $record->beneficiary?->full_name ?? '—' }}</span>
                    <span class="app-search-row-sub">{{ optional($record->visit_date)->isoFormat('LL') }}</span>
                </a>
            @empty
                <p class="app-search-none">{{ __('web_app.shell.search_no_results') }}</p>
            @endforelse
        </section>

        {{-- الخدام --}}
        @if ($users->isNotEmpty())
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.navigation.users') }}</p>
                        <h3>{{ __('web_app.shell.search_users') }}</h3>
                    </div>
                    <span class="app-muted-badge">{{ $users->count() }}</span>
                </div>
                @foreach ($users as $record)
                    <a href="{{ route('app.users') }}" wire:navigate class="app-search-row">
                        <i class="ph ph-identification-card" aria-hidden="true"></i>
                        <span class="app-search-row-title">{{ $record->name }}</span>
                        <span class="app-search-row-sub">{{ $record->role->label() }}</span>
                    </a>
                @endforeach
            </section>
        @endif

        {{-- طلبات الانضمام --}}
        @if ($joinRequests->isNotEmpty())
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.navigation.join_requests') }}</p>
                        <h3>{{ __('web_app.shell.search_join_requests') }}</h3>
                    </div>
                    <span class="app-muted-badge">{{ $joinRequests->count() }}</span>
                </div>
                @foreach ($joinRequests as $record)
                    <a href="{{ route('app.join-requests') }}" wire:navigate class="app-search-row">
                        <i class="ph ph-user-plus" aria-hidden="true"></i>
                        <span class="app-search-row-title">{{ $record->user?->name }}</span>
                        <span class="app-search-row-sub">{{ $record->statusLabel() }}</span>
                    </a>
                @endforeach
            </section>
        @endif
    @endif
</section>
