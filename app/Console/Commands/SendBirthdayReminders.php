<?php

namespace App\Console\Commands;

use App\Jobs\SendFcmNotificationJob;
use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use App\Support\NotificationMetadata;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Requirements: 1.1, 1.2, 1.3, 1.4, 9.1
 */
class SendBirthdayReminders extends Command
{
    protected $signature = 'reminders:birthdays';

    protected $description = 'إرسال تذكيرات أعياد الميلاد للمخدومين بعد 3 أيام';

    public function handle(): void
    {
        $startTime      = microtime(true);
        $targetDate     = now()->addDays(3);
        $count          = 0;
        $originalLocale = App::getLocale();

        try {
            $driver = DB::getDriverName();

            if ($driver === 'sqlite') {
                $monthExpr = "CAST(strftime('%m', birth_date) AS INTEGER)";
                $dayExpr   = "CAST(strftime('%d', birth_date) AS INTEGER)";
            } else {
                $monthExpr = 'MONTH(birth_date)';
                $dayExpr   = 'DAY(birth_date)';
            }

            Beneficiary::query()
                ->where('status', 'active')
                ->whereNotNull('birth_date')
                ->whereRaw("{$monthExpr} = ? AND {$dayExpr} = ?", [
                    $targetDate->month,
                    $targetDate->day,
                ])
                ->with([
                    'assignedServant:id,fcm_token,locale',
                    'assignedServant.pushDevices:id,user_id,token',
                    'serviceGroup.leader:id,fcm_token,locale',
                    'serviceGroup.leader.pushDevices:id,user_id,token',
                ])
                ->chunkById(100, function (Collection $chunk) use (&$count, $originalLocale): void {
                    $rows   = [];
                    $pushes = [];

                    foreach ($chunk as $beneficiary) {
                        $age        = $beneficiary->birth_date->age + 1;
                        $recipients = collect([
                            $beneficiary->assignedServant,
                            $beneficiary->serviceGroup?->leader,
                        ])->filter()->unique('id')->values();

                        foreach ($recipients as $recipient) {
                            $recipientLocale = $recipient->locale ?? 'ar';
                            App::setLocale($recipientLocale);

                            $params = [
                                'name' => $beneficiary->full_name,
                                'age'  => $age,
                                'days' => 3,
                            ];

                            $title            = __('notifications.birthday_title', $params);
                            $body             = __('notifications.birthday_body', $params);
                            $notificationData = NotificationMetadata::enrich('birthday', [
                                'beneficiary_id' => $beneficiary->id,
                                'locale'         => $recipientLocale,
                                'url'            => '/app/beneficiary/' . $beneficiary->id,
                            ]);

                            $rows[] = [
                                'user_id'    => $recipient->id,
                                'type'       => 'birthday',
                                'title'      => $title,
                                'body'       => $body,
                                'data'       => json_encode($notificationData),
                                'created_at' => now()->toDateTimeString(),
                            ];

                            $tokens = $recipient->pushTokens();

                            if ($tokens !== []) {
                                $pushes[] = [
                                    'tokens' => $tokens,
                                    'title'  => $title,
                                    'body'   => $body,
                                    'data'   => $notificationData,
                                ];
                            }

                            $count++;
                        }
                    }

                    App::setLocale($originalLocale);

                    if ($rows !== []) {
                        MinistryNotification::insert($rows);
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

            Log::info('reminders:birthdays', [
                'notifications_sent' => $count,
                'execution_time_sec' => $elapsed,
            ]);

            $this->info("✅ تم إرسال {$count} تذكير عيد ميلاد في {$elapsed}s.");
        } finally {
            App::setLocale($originalLocale);
        }
    }
}
