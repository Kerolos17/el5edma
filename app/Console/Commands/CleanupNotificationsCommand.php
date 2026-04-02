<?php
namespace App\Console\Commands;

use App\Models\MinistryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupNotificationsCommand extends Command
{
    protected $signature = 'notifications:cleanup';

    protected $description = 'حذف الإشعارات المقروءة الأقدم من 30 يوماً';

    public function handle(): void
    {
        $deleted = 0;
        $cutoff  = now()->subDays(30);

        do {
            $count  = MinistryNotification::whereNotNull('read_at')
                ->where('read_at', '<', $cutoff)
                ->limit(500)
                ->delete();
            $deleted += $count;
        } while ($count > 0);

        if ($deleted === 0) {
            Log::info('notifications:cleanup — لا توجد سجلات للحذف');
        } else {
            Log::info("notifications:cleanup — تم حذف {$deleted} سجل");
        }

        $this->info("✅ تم حذف {$deleted} إشعار قديم.");
    }
}
