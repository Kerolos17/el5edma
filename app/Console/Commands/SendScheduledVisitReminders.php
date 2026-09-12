<?php

namespace App\Console\Commands;

use App\Jobs\SendFcmNotificationJob;
use App\Models\MinistryNotification;
use App\Models\ScheduledVisit;
use App\Support\NotificationMetadata;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class SendScheduledVisitReminders extends Command
{
    protected $signature = 'reminders:scheduled-visits';

    protected $description = 'إرسال تذكيرات الزيارات المجدولة للغد';

    public function handle(): void
    {
        $startTime      = microtime(true);
        $tomorrow       = now()->addDay()->toDateString();
        $originalLocale = App::getLocale();
        $count          = 0;

        try {
            $visits = ScheduledVisit::query()
                ->where('status', 'pending')
                ->whereDate('scheduled_date', $tomorrow)
                ->whereNull('reminder_sent_at')
                ->with([
                    'beneficiary:id,full_name',
                    'assignedServant:id,fcm_token,locale',
                    'assignedServant.pushDevices:id,user_id,token',
                    'servants:id,fcm_token,locale',
                    'servants.pushDevices:id,user_id,token',
                ])
                ->get();

            $rows     = [];
            $pushes   = [];
            $visitIds = [];

            foreach ($visits as $visit) {
                $servants = $visit->servants
                    ->whenEmpty(fn ($collection) => $visit->assignedServant ? $collection->push($visit->assignedServant) : $collection)
                    ->unique('id')
                    ->values();

                if ($servants->isEmpty()) {
                    continue;
                }

                $hasRecipients = false;

                foreach ($servants as $servant) {
                    App::setLocale($servant->locale ?? 'ar');

                    $title = __('notifications.visit_reminder_title');
                    $body  = __('notifications.visit_reminder_body', [
                        'name' => $visit->beneficiary?->full_name ?? '—',
                    ]);
                    $dataPayload = NotificationMetadata::enrich('visit_reminder', [
                        'scheduled_visit_id' => (string) $visit->id,
                        'beneficiary_id'     => (string) $visit->beneficiary_id,
                        'scheduled_date'     => (string) $visit->scheduled_date,
                        'scheduled_time'     => (string) $visit->scheduled_time,
                        'url'                => '/app/beneficiary/' . $visit->beneficiary_id,
                    ]);

                    $rows[] = [
                        'user_id'    => $servant->id,
                        'type'       => 'visit_reminder',
                        'title'      => $title,
                        'body'       => $body,
                        'data'       => json_encode($dataPayload),
                        'created_at' => now()->toDateTimeString(),
                    ];

                    $tokens = $servant->pushTokens();

                    if ($tokens !== []) {
                        $pushes[] = [
                            'tokens' => $tokens,
                            'title'  => $title,
                            'body'   => $body,
                            'data'   => $dataPayload,
                        ];
                    }

                    $hasRecipients = true;
                    $count++;
                }

                if ($hasRecipients) {
                    $visitIds[] = $visit->id;
                }
            }

            App::setLocale($originalLocale);

            if ($rows !== []) {
                MinistryNotification::insert($rows);

                foreach ($pushes as $push) {
                    SendFcmNotificationJob::dispatch(
                        $push['tokens'],
                        $push['title'],
                        $push['body'],
                        $push['data'],
                    );
                }

                ScheduledVisit::whereIn('id', array_unique($visitIds))
                    ->update(['reminder_sent_at' => now()]);
            }

            $elapsed = round(microtime(true) - $startTime, 2);

            Log::info("reminders:scheduled-visits — تم إرسال {$count} تذكير في {$elapsed} ثانية");

            $this->info("✅ تم إرسال {$count} تذكير زيارة مجدولة.");
        } finally {
            App::setLocale($originalLocale);
        }
    }
}
