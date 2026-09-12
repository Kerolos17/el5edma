<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Models\MinistryNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
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

    public function markReadAndRedirect(int $id): void
    {
        $notification = MinistryNotification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        $url = $this->safeInternalNotificationPath($notification->data['url'] ?? null);

        if ($url !== null) {
            $this->redirect($url, navigate: str_starts_with($url, '/app'));

            return;
        }

        $this->dispatch('toast', message: __('web_app.toasts.marked_read'), type: 'success');
    }

    public function markAllRead(): void
    {
        MinistryNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->dispatch('toast', message: __('web_app.toasts.all_marked_read'), type: 'success');
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

        $path  = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        if (! str_starts_with($path, '/')) {
            return null;
        }

        return ($this->webAppPathForLegacyAdminPath($path) ?? $path) . $query;
    }

    private function webAppPathForLegacyAdminPath(string $path): ?string
    {
        return match (true) {
            str_starts_with($path, '/admin/visits')                 => '/app/visits',
            str_starts_with($path, '/admin/beneficiaries')          => '/app/beneficiaries',
            str_starts_with($path, '/admin/scheduled-visits')       => '/app/scheduled-visits',
            str_starts_with($path, '/admin/prayer-requests')        => '/app/prayer-requests',
            str_starts_with($path, '/admin/medical-files')          => '/app/medical-files',
            str_starts_with($path, '/admin/users')                  => '/app/users',
            str_starts_with($path, '/admin/service-groups')         => '/app/service-groups',
            str_starts_with($path, '/admin/ministry-notifications') => '/app/notifications',
            default                                                 => null,
        };
    }

    public function render(): View
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
            'stats'       => $stats,
            'records'     => $records,
            'unreadCount' => MinistryNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
        ]);
    }
}
