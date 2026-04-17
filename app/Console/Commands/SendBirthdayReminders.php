<?php
namespace App\Console\Commands;

use App\Jobs\SendFcmNotificationJob;
use App\Models\Beneficiary;
use App\Models\MinistryNotification;
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
            // Requirement 1.1 — SQL filter بدل PHP filter
            // Requirement 1.2 — chunkById بدفعات 100
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
                    'serviceGroup.leader:id,fcm_token,locale',
                ])
                ->chunkById(100, function (Collection $chunk) use ($targetDate, &$count, $originalLocale) {
                    $rows   = [];
                    $tokens = [];

                    // قيم افتراضية بالعربية للـ FCM multicast — تُستبدل بآخر قيمة من الدفعة
                    App::setLocale('ar');
                    $title = __('notifications.birthday_title', ['name' => '', 'age' => '', 'days' => 3]);
                    $body  = __('notifications.birthday_body', ['name' => '', 'age' => '', 'days' => 3]);
                    App::setLocale($originalLocale);

                    foreach ($chunk as $beneficiary) {
                        $age = $beneficiary->birth_date->age + 1;

                        // إشعار الخادم المعيّن
                        if ($servant = $beneficiary->assignedServant) {
                            $locale = $servant->locale ?? 'ar';
                            App::setLocale($locale);

                            $params = [
                                'name' => $beneficiary->full_name,
                                'age'  => $age,
                                'days' => 3,
                            ];

                            $title = __('notifications.birthday_title', $params);
                            $body  = __('notifications.birthday_body', $params);

                            $rows[] = [
                                'user_id'    => $servant->id,
                                'type'       => 'birthday',
                                'title'      => $title,
                                'body'       => $body,
                                'data'       => json_encode([
                                    'beneficiary_id' => $beneficiary->id,
                                    'url'            => route('filament.admin.resources.beneficiaries.view', ['record' => $beneficiary->id]),
                                ]),
                                'created_at' => now()->toDateTimeString(),
                            ];

                            if ($servant->fcm_token) {
                                $tokens[] = $servant->fcm_token;
                            }

                            $count++;
                        }

                        // إشعار أمين الأسرة
                        if ($leader = $beneficiary->serviceGroup?->leader) {
                            $locale = $leader->locale ?? 'ar';
                            App::setLocale($locale);

                            $params = [
                                'name' => $beneficiary->full_name,
                                'age'  => $age,
                                'days' => 3,
                            ];

                            $title = __('notifications.birthday_title', $params);
                            $body  = __('notifications.birthday_body', $params);

                            $rows[] = [
                                'user_id'    => $leader->id,
                                'type'       => 'birthday',
                                'title'      => $title,
                                'body'       => $body,
                                'data'       => json_encode([
                                    'beneficiary_id' => $beneficiary->id,
                                    'url'            => route('filament.admin.resources.beneficiaries.view', ['record' => $beneficiary->id]),
                                ]),
                                'created_at' => now()->toDateTimeString(),
                            ];

                            if ($leader->fcm_token) {
                                $tokens[] = $leader->fcm_token;
                            }

                            $count++;
                        }
                    }

                    // Requirement 1.3 — bulk insert بدل create() الفردي
                    if (! empty($rows)) {
                        MinistryNotification::insert($rows);
                    }

                    if (! empty($tokens)) {
                        SendFcmNotificationJob::dispatch($tokens, $title, $body, []);
                    }
                });

            $elapsed = round(microtime(true) - $startTime, 2);

            // Requirement 9.1 — تسجيل وقت التنفيذ والعدد
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
