<section class="app-page-stack">
    <x-slot:title>{{ __('web_app.notifications.title') }}</x-slot:title>

    <div class="app-hero-panel">
        <div>
            <p class="app-section-label">{{ __('web_app.notifications.panel_title') }}</p>
            <h2>{{ __('web_app.notifications.title') }}</h2>
        </div>
        <div class="app-hero-actions">
            <a href="{{ route('app.dashboard') }}" wire:navigate class="app-secondary-button">
                <i class="ph ph-squares-four" aria-hidden="true"></i>
                {{ __('web_app.actions.dashboard') }}
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
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('web_app.notifications.search_placeholder') }}">
            </label>
            <div class="app-chip-row" role="tablist">
                @php
                    $typeFilters = [
                        ['value' => 'all', 'label' => __('web_app.filters.all')],
                        ['value' => 'unread', 'label' => __('web_app.notifications.unread')],
                    ];
                @endphp
                @foreach ($typeFilters as $item)
                    <a href="{{ route('app.notifications', ['filter' => $item['value']]) }}" wire:navigate
                        class="app-filter-chip {{ $filter === $item['value'] ? 'is-active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
                @if ($unreadCount > 0)
                    <button wire:click="markAllRead" class="app-link-inline ms-auto">
                        <i class="ph ph-check-circle" aria-hidden="true"></i>
                        {{ __('notifications.mark_all_read') }}
                    </button>
                @endif
            </div>
        </div>
    </section>

    <section class="app-panel" style="padding:0">
        @if ($records->isNotEmpty())
            @foreach ($records as $notification)
                @php
                    $typeConfig = match ($notification->type) {
                        'birthday' => ['icon' => 'ph-cake', 'color' => '#d97706', 'bg' => '#fef3c7'],
                        'critical_case' => ['icon' => 'ph-warning-circle', 'color' => '#e11d48', 'bg' => '#ffe4e6'],
                        'visit_reminder' => ['icon' => 'ph-calendar-check', 'color' => '#2563eb', 'bg' => '#dbeafe'],
                        'unvisited_alert' => ['icon' => 'ph-warning', 'color' => '#d97706', 'bg' => '#fef3c7'],
                        'new_beneficiary' => ['icon' => 'ph-user-plus', 'color' => '#059669', 'bg' => '#d1fae5'],
                        'servant_registered' => ['icon' => 'ph-handshake', 'color' => '#7c3aed', 'bg' => '#ede9fe'],
                        default => ['icon' => 'ph-bell', 'color' => '#64748b', 'bg' => '#f1f5f9'],
                    };
                    $isUnread = $notification->read_at === null;
                @endphp
                <div class="app-notif-row {{ $isUnread ? 'is-unread' : '' }}">
                    @if ($isUnread)
                        <div class="app-notif-dot"></div>
                    @endif
                    <div class="app-notif-icon" style="background:{{ $typeConfig['bg'] }};color:{{ $typeConfig['color'] }}">
                        <i class="ph {{ $typeConfig['icon'] }}" aria-hidden="true"></i>
                    </div>
                    <div class="app-notif-body">
                        <strong>{{ $notification->title }}</strong>
                        <p>{{ $notification->body }}</p>
                        <span class="app-notif-meta">
                            <time>{{ $notification->created_at->format('Y-m-d H:i') }}</time>
                            <span class="app-status-pill tone-slate" style="font-size:0.65rem;padding:0.1rem 0.4rem">{{ $notification->type }}</span>
                        </span>
                    </div>
                    @if ($isUnread)
                        <button type="button" wire:click="markRead({{ $notification->id }})" class="app-notif-action" title="{{ __('notifications.read') }}">
                            <i class="ph ph-check" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
            @endforeach
        @else
            <div class="app-empty-state" style="padding:3rem 1rem">
                <i class="ph ph-bell-slash" aria-hidden="true" style="font-size:2.5rem;color:#94a3b8"></i>
                <p>{{ __('notifications.no_notifications') }}</p>
            </div>
        @endif
    </section>

    @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator && $records->hasPages())
        <div class="app-pagination-wrap">
            {{ $records->onEachSide(1)->links() }}
        </div>
    @endif
</section>
