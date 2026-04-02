<?php

namespace App\Filament\Widgets;

use App\Models\MinistryNotification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

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
        $recent = MinistryNotification::where('user_id', Auth::id())
            ->latest('created_at')
            ->limit(8)
            ->get();

        $this->unreadCount = MinistryNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        $this->notifications = $recent
            ->map(fn ($n) => [
                'id'    => $n->id,
                'type'  => $n->type,
                'title' => $n->title,
                'body'  => $n->body,
                'read'  => ! is_null($n->read_at),
                'time'  => $n->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function markAllRead(): void
    {
        MinistryNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->loadNotifications();
    }

    public function markRead(int $id): void
    {
        MinistryNotification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['read_at' => now()]);

        $this->loadNotifications();
    }
}
