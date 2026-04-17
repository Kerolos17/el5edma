<?php

namespace App\Console\Commands;

use App\Models\MinistryNotification;
use App\Models\User;
use Illuminate\Console\Command;

class NotificationsStatsCommand extends Command
{
    protected $signature = 'notifications:stats';

    protected $description = 'عرض إحصائيات نظام الإشعارات';

    public function handle(): void
    {
        $unread = MinistryNotification::whereNull('read_at')->count();
        $tokens = User::whereNotNull('fcm_token')->count();
        $recent = MinistryNotification::where('created_at', '>=', now()->subDays(7))->count();

        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['إشعارات غير مقروءة', $unread],
                ['FCM tokens نشطة', $tokens],
                ['إشعارات آخر 7 أيام', $recent],
            ],
        );
    }
}
