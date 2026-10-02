<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\MinistryNotification;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * صفحة الإشعارات الكاملة للوحة الخادم — نفس منطق صفحة /app/notifications
 * (قائمة، فلترة، بحث، تحديد كمقروء، تحويل آمن للمسار الداخلي) بتخطيط
 * الخادم الميداني.
 */
#[Layout('servant.layouts.app')]
#[Title('الإشعارات')]
class NotificationsPage extends Component
{
    use WithPagination;

    #[On('fcmMessageReceived')]
    #[On('app-resumed')]
    public function refresh(): void {}

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

    public function markReadAndRedirect(int $id): mixed
    {
        $notification = MinistryNotification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return $this->redirect($this->safeInternalNotificationPath($notification->data['url'] ?? '/servant/dashboard'));
    }

    public function markAllRead(): void
    {
        MinistryNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->dispatch('toast', message: __('web_app.toasts.all_marked_read'), type: 'success');
    }

    /**
     * Only same-origin internal paths survive; legacy /admin/* links are
     * remapped to their /app/* equivalents.
     */
    private function safeInternalNotificationPath(?string $url): string
    {
        $fallback = '/servant/dashboard';

        if (! $url || ! str_starts_with($url, '/')) {
            return $fallback;
        }

        $path  = parse_url($url, PHP_URL_PATH) ?: '';
        $query = parse_url($url, PHP_URL_QUERY);

        $legacyMap = [
            '/admin/visits'           => '/app/visits',
            '/admin/beneficiaries'    => '/app/beneficiaries',
            '/admin/notifications'    => '/app/notifications',
            '/admin/scheduled-visits' => '/app/scheduled-visits',
            '/admin/users'            => '/app/users',
        ];

        $path = $legacyMap[$path] ?? $path;

        $allowedPrefixes = ['/app/', '/servant/'];

        if (! collect($allowedPrefixes)->contains(fn ($prefix) => $path === rtrim($prefix, '/') || str_starts_with($path, $prefix))) {
            return $fallback;
        }

        return $path . ($query ? '?' . $query : '');
    }

    public function render(): View
    {
        $notifications = MinistryNotification::query()
            ->where('user_id', auth()->id())
            ->when($this->filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($this->filter !== 'all' && $this->filter !== 'unread', fn ($q) => $q->where('type', $this->filter))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . str_replace('%', '\%', trim($this->search)) . '%';
                $q->where(fn ($inner) => $inner
                    ->where('title', 'like', $term)
                    ->orWhere('body', 'like', $term));
            })
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('livewire.servant.notifications-page', [
            'notifications' => $notifications,
            'unreadCount'   => MinistryNotification::where('user_id', auth()->id())->whereNull('read_at')->count(),
        ]);
    }
}
