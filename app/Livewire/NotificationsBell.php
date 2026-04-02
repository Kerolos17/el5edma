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

        $this->notifications = $notifications->map(function ($n) {
            $data = is_string($n->data) ? json_decode($n->data, true) : ($n->data ?? []);

            $url = null;
            if ($n->type === 'new_beneficiary' || $n->type === 'birthday' || $n->type === 'unvisited_alert') {
                $beneficiaryId = $data['beneficiary_id'] ?? null;
                if ($beneficiaryId) {
                    $url = route('filament.admin.resources.beneficiaries.view', ['record' => $beneficiaryId]);
                }
            } elseif ($n->type === 'visit_reminder' || $n->type === 'critical_case') {
                $visitId = $data['visit_id'] ?? null;
                if ($visitId) {
                    $url = route('filament.admin.resources.visits.view', ['record' => $visitId]);
                } else {
                    // Fallback to notification index or beneficiaries
                    $url = route('filament.admin.resources.ministry-notifications.index');
                }
            } elseif ($n->type === 'servant_registered') {
                // رابط لصفحة المستخدمين مع فلتر الخدام المعلقين
                $url = route('filament.admin.resources.users.index');
            }

            return [
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
