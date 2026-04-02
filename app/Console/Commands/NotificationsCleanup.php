<?php

namespace App\Console\Commands;

use App\Models\MinistryNotification;
use Illuminate\Console\Command;

class NotificationsCleanup extends Command
{
    protected $signature   = 'notifications:cleanup';
    protected $description = 'حذف الإشعارات القديمة: المقروءة منذ 90+ يوماً أو غير المقروءة منذ 180+ يوماً';

    public function handle(): void
    {
        $deleted = 0;

        // Read notifications older than 90 days
        MinistryNotification::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays(90))
            ->chunkById(500, function ($chunk) use (&$deleted) {
                $deleted += $chunk->count();
                MinistryNotification::whereIn('id', $chunk->pluck('id'))->delete();
            });

        // Unread notifications older than 180 days
        MinistryNotification::query()
            ->whereNull('read_at')
            ->where('created_at', '<', now()->subDays(180))
            ->chunkById(500, function ($chunk) use (&$deleted) {
                $deleted += $chunk->count();
                MinistryNotification::whereIn('id', $chunk->pluck('id'))->delete();
            });

        $this->info("✅ تم حذف {$deleted} إشعار قديم.");
    }
}
