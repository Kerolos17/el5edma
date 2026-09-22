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
        $recent = MinistryNotification::where('user_id', Auth::id())
            ->latest('created_at')
            ->limit(8)
            ->get();

        $this->unreadCount = Cache::remember(
            'notifications_unread_' . Auth::id(),
            60,
            fn () => MinistryNotification::where('user_id', Auth::id())->whereNull('read_at')->count(),
        );

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

        Cache::forget('notifications_unread_' . Auth::id());
        $this->loadNotifications();
    }

    public function markRead(int $id): mixed
    {
        $notification = MinistryNotification::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $notification) {
            return null;
        }

        $notification->update(['read_at' => now()]);

        Cache::forget('notifications_unread_' . Auth::id());
        $this->loadNotifications();

        // Navigate to the related record so a tap never just clears state.
        return $this->redirect($this->targetUrl($notification), navigate: true);
    }

    private function targetUrl(MinistryNotification $notification): string
    {
        $data = $notification->data ?? [];

        if (! empty($data['visit_id'])) {
            return route('filament.admin.resources.visits.view', $data['visit_id']);
        }

        if (! empty($data['beneficiary_id'])) {
            return route('filament.admin.resources.beneficiaries.view', $data['beneficiary_id']);
        }

        return route('filament.admin.resources.ministry-notifications.index');
    }
}
