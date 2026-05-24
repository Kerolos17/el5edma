<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\MinistryNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class NotificationsBell extends Component
{
    public int $unreadCount = 0;

    public int $previousUnreadCount = -1;

    public array $notifications = [];

    protected $listeners = [
        'fcmMessageReceived'  => 'loadNotifications',
        'notificationCreated' => 'loadNotifications',
    ];

    public function mount(): void
    {
        $this->loadNotifications();
        $this->previousUnreadCount = $this->unreadCount;
    }

    public function loadNotifications(): void
    {
        $userId = Auth::id();

        $recent = MinistryNotification::where('user_id', $userId)
            ->latest('created_at')
            ->limit(10)
            ->get();

        $newCount = MinistryNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        if ($this->previousUnreadCount >= 0 && $newCount > $this->previousUnreadCount) {
            $this->dispatch('new-notification-sound');
        }

        $this->unreadCount         = $newCount;
        $this->previousUnreadCount = $newCount;

        $this->notifications = $recent
            ->map(fn ($n) => [
                'id'    => $n->id,
                'type'  => $n->type,
                'title' => $n->title,
                'body'  => $n->body,
                'read'  => $n->read_at !== null,
                'time'  => $n->created_at->diffForHumans(),
                'url'   => $n->data['url'] ?? null,
            ])->toArray();
    }

    public function markAllRead(): void
    {
        MinistryNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Cache::forget('notifications_unread_' . Auth::id());
        $this->loadNotifications();
    }

    public function markRead(int $id): void
    {
        $notification = MinistryNotification::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notification->update(['read_at' => now()]);
        Cache::forget('notifications_unread_' . Auth::id());

        $url = $this->safeInternalNotificationPath($notification->data['url'] ?? null);

        if ($url !== null) {
            $this->redirect($url, navigate: str_starts_with($url, '/app') || str_starts_with($url, '/servant'));
            return;
        }

        $this->loadNotifications();
    }

    private function safeInternalNotificationPath(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $this->webAppPathForLegacyAdminPath($url) ?? $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return null;
        }

        if ($parts['host'] !== request()->getHost()) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        if (! str_starts_with($path, '/')) {
            return null;
        }

        return $this->webAppPathForLegacyAdminPath($path) ?? $path . $query;
    }

    private function webAppPathForLegacyAdminPath(string $path): ?string
    {
        return match (true) {
            str_starts_with($path, '/admin/visits') => '/app/visits',
            str_starts_with($path, '/admin/beneficiaries') => '/app/beneficiaries',
            str_starts_with($path, '/admin/scheduled-visits') => '/app/scheduled-visits',
            str_starts_with($path, '/admin/prayer-requests') => '/app/prayer-requests',
            str_starts_with($path, '/admin/medical-files') => '/app/medical-files',
            str_starts_with($path, '/admin/users') => '/app/users',
            str_starts_with($path, '/admin/service-groups') => '/app/service-groups',
            str_starts_with($path, '/admin/ministry-notifications') => '/app/notifications',
            default => null,
        };
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servant.notifications-bell');
    }
}
