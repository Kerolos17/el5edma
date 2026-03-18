<?php

namespace App\Livewire;

use App\Models\MinistryNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsBell extends Component
{
    public int $unreadCount = 0;

    public array $notifications = [];

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $this->unreadCount = MinistryNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        $this->notifications = MinistryNotification::where('user_id', Auth::id())
            ->latest('created_at')
            ->limit(8)
            ->get()
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

    public function render()
    {
        return view('livewire.notifications-bell');
    }
}
