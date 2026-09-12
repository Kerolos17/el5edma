<?php

namespace App\Console\Commands;

use App\Jobs\SendFcmNotificationJob;
use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use App\Support\NotificationMetadata;
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
        $startTime      = microtime(true);
        $cutoff         = now()->subDays(14);
        $count          = 0;
        $originalLocale = App::getLocale();

        try {
            Beneficiary::query()
                ->where('status', 'active')
                ->withMax('visits', 'visit_date')
                ->where(function ($q) use ($cutoff): void {
                    $q->whereNull('visits_max_visit_date')
                        ->orWhere('visits_max_visit_date', '<', $cutoff->toDateTimeString());
                })
                ->with([
                    'serviceGroup.leader:id,fcm_token,locale',
                    'serviceGroup.leader.pushDevices:id,user_id,token',
                    'assignedServant:id,fcm_token,locale',
                    'assignedServant.pushDevices:id,user_id,token',
                ])
                ->chunkById(100, function (Collection $chunk) use (&$count, $originalLocale): void {
                    $rows   = [];
                    $pushes = [];

                    foreach ($chunk as $beneficiary) {
                        $lastVisit   = $beneficiary->visits_max_visit_date;
                        $days        = $lastVisit ? (int) now()->diffInDays($lastVisit) : null;
                        $dataPayload = NotificationMetadata::enrich('unvisited_alert', [
                            'beneficiary_id' => (string) $beneficiary->id,
                            'last_visit'     => (string) ($lastVisit ?? ''),
                            'days_unvisited' => (string) ($days ?? ''),
                            'url'            => '/app/beneficiary/' . $beneficiary->id,
                        ]);

                        $recipients = collect([
                            $beneficiary->serviceGroup?->leader,
                            $beneficiary->assignedServant,
                        ])->filter()->unique('id')->values();

                        foreach ($recipients as $recipient) {
                            App::setLocale($recipient->locale ?? 'ar');

                            $title = __('notifications.unvisited_alert_title');
                            $body  = __('notifications.unvisited_alert_body', [
                                'name' => $beneficiary->full_name,
                                'days' => $days ?? '?',
                            ]);

                            $rows[] = [
                                'user_id'    => $recipient->id,
                                'type'       => 'unvisited_alert',
                                'title'      => $title,
                                'body'       => $body,
                                'data'       => json_encode($dataPayload),
                                'created_at' => now()->toDateTimeString(),
                            ];

                            $tokens = $recipient->pushTokens();

                            if ($tokens !== []) {
                                $pushes[] = [
                                    'tokens' => $tokens,
                                    'title'  => $title,
                                    'body'   => $body,
                                    'data'   => $dataPayload,
                                ];
                            }

                            $count++;
                        }
                    }

                    App::setLocale($originalLocale);

                    if ($rows !== []) {
                        MinistryNotification::insertOrIgnore($rows);
                    }

                    foreach ($pushes as $push) {
                        SendFcmNotificationJob::dispatch(
                            $push['tokens'],
                            $push['title'],
                            $push['body'],
                            $push['data'],
                        );
                    }
                });

            $elapsed = round(microtime(true) - $startTime, 2);

            Log::info("reminders:unvisited — تم إرسال {$count} تنبيه في {$elapsed} ثانية");
            $this->info("✅ تم إرسال {$count} تنبيه مخدوم غير مزار.");
        } finally {
            App::setLocale($originalLocale);
        }
    }
}
