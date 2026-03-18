<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class SendUnvisitedAlerts extends Command
{
    protected $signature   = 'reminders:unvisited';
    protected $description = 'تنبيه أمناء الأسر عند مرور 14 يوماً بدون زيارة مخدوم';

    public function handle(): void
    {
        $cutoff = now()->subDays(14);

        $beneficiaries = Beneficiary::query()
            ->where('status', 'active')
            ->where(function ($q) use ($cutoff) {
                // لم يُزَر قط أو آخر زيارة أكثر من 14 يوم
                $q->whereDoesntHave('visits')
                  ->orWhere(function ($q2) use ($cutoff) {
                      $q2->whereHas('visits')
                         ->whereRaw(
                             '(SELECT MAX(visit_date) FROM visits WHERE beneficiary_id = beneficiaries.id) < ?',
                             [$cutoff->toDateString()]
                         );
                  });
            })
            ->with(['serviceGroup.leader', 'assignedServant'])
            ->get();

        $count = 0;

        foreach ($beneficiaries as $beneficiary) {
            $lastVisit = $beneficiary->visits()->max('visit_date');
            $days = $lastVisit
                ? (int) now()->diffInDays($lastVisit)
                : null;

            // إشعار أمين الأسرة
            if ($leader = $beneficiary->serviceGroup?->leader) {
                $locale = $leader->locale ?? 'ar';
                App::setLocale($locale);

                MinistryNotification::create([
                    'user_id' => $leader->id,
                    'type'    => 'unvisited_alert',
                    'title'   => __('notifications.unvisited_alert_title'),
                    'body'    => __('notifications.unvisited_alert_body', [
                        'name' => $beneficiary->full_name,
                        'days' => $days ?? '?',
                    ]),
                    'data' => [
                        'beneficiary_id' => $beneficiary->id,
                        'last_visit'     => $lastVisit,
                        'days_unvisited' => $days,
                    ],
                ]);

                App::setLocale(config('app.locale'));
                $count++;
            }

            // إشعار الخادم المسؤول
            if ($servant = $beneficiary->assignedServant) {
                $locale = $servant->locale ?? 'ar';
                App::setLocale($locale);

                MinistryNotification::create([
                    'user_id' => $servant->id,
                    'type'    => 'unvisited_alert',
                    'title'   => __('notifications.unvisited_alert_title'),
                    'body'    => __('notifications.unvisited_alert_body', [
                        'name' => $beneficiary->full_name,
                        'days' => $days ?? '?',
                    ]),
                    'data' => [
                        'beneficiary_id' => $beneficiary->id,
                        'last_visit'     => $lastVisit,
                        'days_unvisited' => $days,
                    ],
                ]);

                App::setLocale(config('app.locale'));
                $count++;
            }
        }

        $this->info("✅ تم إرسال {$count} تنبيه مخدوم غير مزار.");
    }
}
