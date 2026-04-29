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

        $url = $notification->data['url'] ?? null;

        if ($url && str_starts_with($url, '/servant')) {
            $this->redirect($url, navigate: true);
            return;
        }

        $this->loadNotifications();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servant.notifications-bell');
    }
}
