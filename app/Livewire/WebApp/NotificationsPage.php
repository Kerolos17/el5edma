<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Models\MinistryNotification;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('web-app.layouts.app')]
class NotificationsPage extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $filter = 'all';

    public function markRead(int $id): void
    {
        $notification = MinistryNotification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        $this->dispatch('toast', message: __('web_app.toasts.marked_read'), type: 'success');
    }

    public function markAllRead(): void
    {
        MinistryNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->dispatch('toast', message: __('web_app.toasts.all_marked_read'), type: 'success');
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();

        $query = MinistryNotification::where('user_id', $user->id);

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter !== 'all') {
            $query->where('type', $this->filter);
        }

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', $term)
                ->orWhere('body', 'like', $term));
        }

        $stats = [
            ['label' => __('web_app.notifications.stats.total'), 'value' => MinistryNotification::where('user_id', $user->id)->count(), 'tone' => 'blue'],
            ['label' => __('web_app.notifications.stats.unread'), 'value' => MinistryNotification::where('user_id', $user->id)->whereNull('read_at')->count(), 'tone' => 'rose'],
            ['label' => __('web_app.notifications.stats.types'), 'value' => MinistryNotification::where('user_id', $user->id)->select('type')->distinct()->count('type'), 'tone' => 'amber'],
        ];

        $records = $query->latest('created_at')->paginate(15);

        return view('livewire.web-app.notifications-page', [
            'stats' => $stats,
            'records' => $records,
            'unreadCount' => MinistryNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
        ]);
    }
}
