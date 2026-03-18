<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

class SendBirthdayReminders extends Command
{
    protected $signature   = 'reminders:birthdays';
    protected $description = 'إرسال تذكيرات أعياد الميلاد للمخدومين بعد 3 أيام';

    public function handle(): void
    {
        $targetDate = now()->addDays(3);

        $beneficiaries = Beneficiary::query()
            ->where('status', 'active')
            ->whereNotNull('birth_date')
            ->whereNotNull('assigned_servant_id')
            ->with(['assignedServant', 'serviceGroup.leader'])
            ->get()
            ->filter(function ($b) use ($targetDate) {
                $birthday = Carbon::parse($b->birth_date)
                    ->setYear($targetDate->year);

                if ($birthday->lt(now()->startOfDay())) {
                    $birthday->addYear();
                }

                return $birthday->month === $targetDate->month
                    && $birthday->day === $targetDate->day;
            });

        $count = 0;

        foreach ($beneficiaries as $beneficiary) {
            $age = Carbon::parse($beneficiary->birth_date)->age + 1;

            // إشعار الخادم
            if ($servant = $beneficiary->assignedServant) {
                $this->sendNotification(
                    userId: $servant->id,
                    locale: $servant->locale ?? 'ar',
                    type: 'birthday',
                    titleKey: 'notifications.birthday_title',
                    bodyKey: 'notifications.birthday_body',
                    params: [
                        'name' => $beneficiary->full_name,
                        'age'  => $age,
                        'days' => 3,
                    ],
                    data: ['beneficiary_id' => $beneficiary->id],
                );
                $count++;
            }

            // إشعار أمين الأسرة
            if ($leader = $beneficiary->serviceGroup?->leader) {
                $this->sendNotification(
                    userId: $leader->id,
                    locale: $leader->locale ?? 'ar',
                    type: 'birthday',
                    titleKey: 'notifications.birthday_title',
                    bodyKey: 'notifications.birthday_body',
                    params: [
                        'name' => $beneficiary->full_name,
                        'age'  => $age,
                        'days' => 3,
                    ],
                    data: ['beneficiary_id' => $beneficiary->id],
                );
                $count++;
            }
        }

        $this->info("✅ تم إرسال {$count} تذكير عيد ميلاد.");
    }

    private function sendNotification(
        int $userId,
        string $locale,
        string $type,
        string $titleKey,
        string $bodyKey,
        array $params,
        array $data = [],
    ): void {
        // حفظ الإشعار في الـ database بلغة المستقبل
        App::setLocale($locale);

        MinistryNotification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => __($titleKey, $params),
            'body'    => __($bodyKey, $params),
            'data'    => $data,
        ]);

        App::setLocale(config('app.locale'));
    }
}
