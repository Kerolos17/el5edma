<?php
namespace App\Livewire;

use App\Models\MinistryNotification;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
                'read'  => $n->read_at !== null,
                'time'  => $n->created_at->diffForHumans(),
                'url'   => $url,
            ];
        })->toArray();
    }

    public function markAllRead(): void
    {
        MinistryNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Cache::forget('notifications_unread_' . Auth::id());
        $this->loadNotifications();
    }

    public function markRead(int $id, ?string $url = null): void
    {
        MinistryNotification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['read_at' => now()]);

        Cache::forget('notifications_unread_' . Auth::id());

        if ($url) {
            $this->redirect($url, navigate: FilamentView::hasSpaMode());
        } else {
            $this->loadNotifications();
        }
    }

    public function render()
    {
        return view('livewire.notifications-bell');
    }
}
