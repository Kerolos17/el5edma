<?php
namespace App\Filament\Widgets;

use App\Models\MinistryNotification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NotificationsBellWidget extends Widget
{
    protected string $view = 'filament.widgets.notifications-bell';

    protected static bool $isLazy = true; // ← static

    public int $unreadCount = 0;

    public array $notifications = [];

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $userId = Auth::id();

        $notifications = MinistryNotification::where('user_id', $userId)
            ->latest('created_at')
            ->limit(10)
            ->get();

        $this->unreadCount = Cache::remember(
            "notifications_unread_{$userId}",
            60,
            fn() => $notifications->whereNull('read_at')->count()
        );

        $this->notifications = $notifications->map(fn($n) => [
            'id'    => $n->id,
            'type'  => $n->type,
            'title' => $n->title,
            'body'  => $n->body,
            'read'  => $n->read_at !== null,
            'time'  => $n->created_at->diffForHumans(),
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
        MinistryNotification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['read_at' => now()]);

        Cache::forget('notifications_unread_' . Auth::id());
        $this->loadNotifications();
    }
}
