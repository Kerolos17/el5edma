<?php
namespace App\Console\Commands;

use App\Jobs\SendFcmNotificationJob;
use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class SendUnvisitedAlerts extends Command
{
    protected $signature = 'reminders:unvisited';

    protected $description = 'تنبيه أمناء الأسر عند مرور 14 يوماً بدون زيارة مخدوم';

    public function handle(): void
    {
        $startTime = microtime(true);
        $cutoff    = now()->subDays(14);
        $count     = 0;

        Beneficiary::query()
            ->where('status', 'active')
            ->withMax('visits', 'visit_date')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('visits_max_visit_date')
                    ->orWhere('visits_max_visit_date', '<', $cutoff->toDateTimeString());
            })
            ->with([
                'serviceGroup.leader:id,fcm_token,locale',
                'assignedServant:id,fcm_token,locale',
            ])
            ->chunkById(100, function (Collection $chunk) use (&$count) {
                $rows   = [];
                $tokens = [];

                foreach ($chunk as $beneficiary) {
                    $lastVisit = $beneficiary->visits_max_visit_date;
                    $days      = $lastVisit
                        ? (int) now()->diffInDays($lastVisit)
                        : null;

                    $dataPayload = [
                        'beneficiary_id' => (string) $beneficiary->id,
                        'last_visit'     => (string) ($lastVisit ?? ''),
                        'days_unvisited' => (string) ($days ?? ''),
                        'url'            => route('filament.admin.resources.beneficiaries.view', ['record' => $beneficiary->id]),
                    ];

                    // إشعار أمين الأسرة
                    if ($leader = $beneficiary->serviceGroup?->leader) {
                        $originalLocale = App::getLocale();
                        App::setLocale($leader->locale ?? 'ar');

                        $title = __('notifications.unvisited_alert_title');
                        $body  = __('notifications.unvisited_alert_body', [
                            'name' => $beneficiary->full_name,
                            'days' => $days ?? '?',
                        ]);

                        App::setLocale($originalLocale);

                        $rows[] = [
                            'user_id'    => $leader->id,
                            'type'       => 'unvisited_alert',
                            'title'      => $title,
                            'body'       => $body,
                            'data'       => json_encode($dataPayload),
                            'created_at' => now()->toDateTimeString(),
                        ];

                        if ($leader->fcm_token) {
                            $tokens[] = $leader->fcm_token;
                        }

                        $count++;
                    }

                    // إشعار الخادم المسؤول
                    if ($servant = $beneficiary->assignedServant) {
                        $originalLocale = App::getLocale();
                        App::setLocale($servant->locale ?? 'ar');

                        $title = __('notifications.unvisited_alert_title');
                        $body  = __('notifications.unvisited_alert_body', [
                            'name' => $beneficiary->full_name,
                            'days' => $days ?? '?',
                        ]);

                        App::setLocale($originalLocale);

                        $rows[] = [
                            'user_id'    => $servant->id,
                            'type'       => 'unvisited_alert',
                            'title'      => $title,
                            'body'       => $body,
                            'data'       => json_encode($dataPayload),
                            'created_at' => now()->toDateTimeString(),
                        ];

                        if ($servant->fcm_token) {
                            $tokens[] = $servant->fcm_token;
                        }

                        $count++;
                    }
                }

                if (! empty($rows)) {
                    MinistryNotification::insertOrIgnore($rows);
                }

                if (! empty($tokens)) {
                    $title = __('notifications.unvisited_alert_title');
                    $body  = __('notifications.unvisited_alert_body', ['name' => '', 'days' => '']);
                    SendFcmNotificationJob::dispatch($tokens, $title, $body, []);
                }
            });

        $elapsed = round(microtime(true) - $startTime, 2);

        Log::info("reminders:unvisited — تم إرسال {$count} تنبيه في {$elapsed} ثانية");
        $this->info("✅ تم إرسال {$count} تنبيه مخدوم غير مزار.");
    }
}
