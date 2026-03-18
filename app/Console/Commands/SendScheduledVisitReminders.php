<?php

namespace App\Console\Commands;

use App\Models\MinistryNotification;
use App\Models\ScheduledVisit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class SendScheduledVisitReminders extends Command
{
    protected $signature   = 'reminders:scheduled-visits';
    protected $description = 'إرسال تذكيرات الزيارات المجدولة للغد';

    public function handle(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        $visits = ScheduledVisit::query()
            ->where('status', 'pending')
            ->whereDate('scheduled_date', $tomorrow)
            ->whereNull('reminder_sent_at')
            ->with(['beneficiary', 'assignedServant'])
            ->get();

        $count = 0;

        foreach ($visits as $visit) {
            $servant = $visit->assignedServant;

            if (! $servant) {
                continue;
            }

            $locale = $servant->locale ?? 'ar';
            App::setLocale($locale);

            MinistryNotification::create([
                'user_id' => $servant->id,
                'type'    => 'visit_reminder',
                'title'   => __('notifications.visit_reminder_title'),
                'body'    => __('notifications.visit_reminder_body', [
                    'name' => $visit->beneficiary?->full_name ?? '—',
                ]),
                'data' => [
                    'scheduled_visit_id' => $visit->id,
                    'beneficiary_id'     => $visit->beneficiary_id,
                    'scheduled_date'     => $visit->scheduled_date,
                    'scheduled_time'     => $visit->scheduled_time,
                ],
            ]);

            // تسجيل إن التذكير اتبعت
            $visit->update(['reminder_sent_at' => now()]);

            App::setLocale(config('app.locale'));
            $count++;
        }

        $this->info("✅ تم إرسال {$count} تذكير زيارة مجدولة.");
    }
}
