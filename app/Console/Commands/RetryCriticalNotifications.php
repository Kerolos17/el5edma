<?php

namespace App\Console\Commands;

use App\Jobs\SendFcmNotificationJob;
use App\Models\MinistryNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RetryCriticalNotifications extends Command
{
    protected $signature = 'notifications:retry-critical';

    protected $description = 'Retry unread critical notifications that have not been acknowledged yet';

    public function handle(): void
    {
        $retried = 0;

        MinistryNotification::query()
            ->where('type', 'critical_case')
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subHours(2))
            ->with('user:id,fcm_token,is_active')
            ->chunkById(100, function ($notifications) use (&$retried): void {
                foreach ($notifications as $notification) {
                    $data              = is_array($notification->data) ? $notification->data : [];
                    $retryable         = filter_var($data['retryable'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $retryLimit        = (int) ($data['retry_limit'] ?? 0);
                    $retryCount        = (int) ($data['retry_count'] ?? 0);
                    $retryAfterSeconds = (int) ($data['retry_after_seconds'] ?? 0);
                    $lastRetryAt       = ! empty($data['last_retry_at']) ? Carbon::parse($data['last_retry_at']) : null;

                    if (! $retryable || $retryCount >= $retryLimit) {
                        continue;
                    }

                    if ($lastRetryAt && now()->diffInSeconds($lastRetryAt) < $retryAfterSeconds) {
                        continue;
                    }

                    $user = $notification->user;

                    if (! $user?->is_active || empty($user->fcm_token)) {
                        continue;
                    }

                    $data['retry_count']         = $retryCount + 1;
                    $data['last_retry_at']       = now()->toIso8601String();
                    $data['renotify']            = true;
                    $data['require_interaction'] = true;

                    SendFcmNotificationJob::dispatch([$user->fcm_token], $notification->title, $notification->body, $data);

                    $notification->forceFill(['data' => $data])->save();
                    $retried++;
                }
            });

        $this->info("✅ Retried {$retried} critical notifications.");
    }
}
