<div
    class="app-notification-root"
    data-user-id="{{ Auth::id() }}"
    wire:poll.60000ms.visible="loadNotifications">

    <button
        type="button"
        data-notif-mute
        class="app-notification-mute"
        title="{{ __('notifications.sound_unmute') }}">
        <i class="ph ph-speaker-high" aria-hidden="true" data-notif-sound-on></i>
        <i class="ph ph-speaker-slash" style="display:none;" aria-hidden="true" data-notif-sound-off></i>
    </button>

    <button
        type="button"
        data-notif-toggle
        class="app-notification-bell"
        title="{{ __('notifications.title') }}">
        @if ($unreadCount > 0)
            <i class="ph-fill ph-bell" aria-hidden="true"></i>
            <span>{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @else
            <i class="ph ph-bell" aria-hidden="true"></i>
        @endif
    </button>

    <div data-notif-backdrop class="app-notification-backdrop" aria-hidden="true"></div>

    <div data-notif-panel class="app-notification-panel">

        <div class="app-notification-header">
            <div>
                <i class="ph-fill ph-bell" aria-hidden="true"></i>
                <span>{{ __('notifications.title') }}</span>
                @if ($unreadCount > 0)
                    <strong>{{ $unreadCount }}</strong>
                @endif
            </div>
            @if ($unreadCount > 0)
                <button wire:click="markAllRead" type="button">
                    {{ __('notifications.mark_all_read') }}
                </button>
            @endif
            <a href="{{ route('app.notifications') }}" wire:navigate class="app-notification-view-all">
                <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
            </a>
        </div>

        <div class="app-notification-list">
            @forelse ($notifications as $n)
                <button
                    type="button"
                    wire:key="sn-{{ $n['id'] }}"
                    wire:click="markRead({{ $n['id'] }})"
                    class="app-notification-item {{ $n['read'] ? '' : 'is-unread' }}">
                    <span class="app-notification-type">
                        <i class="ph-fill {{ match($n['type']) {
                            'birthday' => 'ph-cake',
                            'critical_case' => 'ph-warning-circle',
                            'visit_reminder' => 'ph-calendar-check',
                            'unvisited_alert' => 'ph-clock-countdown',
                            'new_beneficiary' => 'ph-user-plus',
                            'servant_registered' => 'ph-user-check',
                            default => 'ph-bell',
                        } }}" aria-hidden="true"></i>
                    </span>

                    <span class="app-notification-content">
                        <strong>{{ $n['title'] }}</strong>
                        <small>{{ $n['body'] }}</small>
                        <time>{{ $n['time'] }}</time>
                    </span>

                    @if (! $n['read'])
                        <span class="app-notification-dot" aria-hidden="true"></span>
                    @endif
                </button>
            @empty
                <div class="app-notification-empty">
                    <i class="ph ph-bell-slash" aria-hidden="true"></i>
                    <p>{{ __('notifications.no_notifications') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>