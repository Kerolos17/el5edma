<?php
namespace App\Console\Commands;

use App\Jobs\SendFcmNotificationJob;
use App\Models\MinistryNotification;
use App\Models\ScheduledVisit;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class SendScheduledVisitReminders extends Command
{
    protected $signature = 'reminders:scheduled-visits';

    protected $description = 'إرسال تذكيرات الزيارات المجدولة للغد';

    protected PushNotificationService $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        parent::__construct();
        $this->pushService = $pushService;
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $tomorrow  = now()->addDay()->toDateString();

        $visits = ScheduledVisit::query()
            ->where('status', 'pending')
            ->whereDate('scheduled_date', $tomorrow)
            ->whereNull('reminder_sent_at')
            ->with(['beneficiary:id,full_name', 'assignedServant:id,fcm_token,locale'])
            ->get();

        $rows     = [];
        $tokens   = [];
        $visitIds = [];

        $originalLocale = App::getLocale();

        foreach ($visits as $visit) {
            $servant = $visit->assignedServant;

            if (! $servant) {
                continue;
            }

            App::setLocale($servant->locale ?? 'ar');

            $title = __('notifications.visit_reminder_title');
            $body  = __('notifications.visit_reminder_body', [
                'name' => $visit->beneficiary?->full_name ?? '—',
            ]);
            $dataPayload = [
                'scheduled_visit_id' => (string) $visit->id,
                'beneficiary_id'     => (string) $visit->beneficiary_id,
                'scheduled_date'     => (string) $visit->scheduled_date,
                'scheduled_time'     => (string) $visit->scheduled_time,
                'url'                => route('filament.admin.resources.beneficiaries.view', ['record' => $visit->beneficiary_id]),
            ];

            App::setLocale($originalLocale);

            $rows[] = [
                'user_id'    => $servant->id,
                'type'       => 'visit_reminder',
                'title'      => $title,
                'body'       => $body,
                'data'       => json_encode($dataPayload),
                'created_at' => now()->toDateTimeString(),
            ];

            if ($servant->fcm_token) {
                $tokens[] = $servant->fcm_token;
            }

            $visitIds[] = $visit->id;
        }

        $count = count($rows);

        if ($count > 0) {
            MinistryNotification::insert($rows);

            if (! empty($tokens)) {
                $title = __('notifications.visit_reminder_title');
                $body  = __('notifications.visit_reminder_title');
                SendFcmNotificationJob::dispatch($tokens, $title, $body, []);
            }

            ScheduledVisit::whereIn('id', $visitIds)->update(['reminder_sent_at' => now()]);
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        Log::info("reminders:scheduled-visits — تم إرسال {$count} تذكير في {$elapsed} ثانية");

        $this->info("✅ تم إرسال {$count} تذكير زيارة مجدولة.");
    }
}
