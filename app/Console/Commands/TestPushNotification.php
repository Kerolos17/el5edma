<?php

namespace App\Console\Commands;

use App\DTOs\MulticastResult;
use App\Models\PushDevice;
use App\Models\User;
use App\Services\PushNotificationService;
use App\Support\NotificationMetadata;
use Illuminate\Console\Command;

class TestPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pwa:test-push {uid_or_email?} {--token=} {--device-id=} {--title=Test Notification} {--body=This is a test push from Artisan!}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إرسال إشعار تجريبي لمستخدم باستخدام الـ Firebase لاختبار عمل النظام.';

    /**
     * Execute the console command.
     */
    public function handle(PushNotificationService $pushService)
    {
        $payload = NotificationMetadata::enrich('test_alert', [
            'severity'  => 'high',
            'url'       => route('app.notifications'),
            'test_data' => 'This is test payload',
        ]);

        // Direct-to-token mode: no user lookup, no database writes.
        if ($token = $this->option('token')) {
            $this->info('جاري إرسال إشعار تجريبي إلى التوكن المُعطى...');

            $result = $pushService->sendMulticast(
                [$token],
                $this->option('title'),
                $this->option('body'),
                $payload,
            );

            return $this->reportMulticastResult($result);
        }

        $identifier = $this->argument('uid_or_email');

        if (! $identifier) {
            $this->error('حدد uid_or_email أو استخدم --token="..."');

            return 1;
        }

        $user = User::where('email', $identifier)->orWhere('id', $identifier)->first();

        if (! $user) {
            $this->error("المستخدم {$identifier} غير موجود.");

            return 1;
        }

        if ($deviceId = $this->option('device-id')) {
            $device = PushDevice::query()
                ->where('user_id', $user->id)
                ->whereKey($deviceId)
                ->first();

            if (! $device) {
                $this->error('الجهاز غير موجود لهذا المستخدم.');

                return 1;
            }

            $result = $pushService->sendMulticast(
                [$device->token],
                $this->option('title'),
                $this->option('body'),
                $payload,
            );

            return $this->reportMulticastResult($result);
        }

        if (empty($user->pushTokens())) {
            $this->warn("المستخدم {$identifier} ('{$user->name}') ليس لديه FCM Token مسجل. يرجى تسجيل الدخول للحساب من متصفح داعم والموافقة على الإشعارات أولاً.");

            return 1;
        }

        $this->info("جاري إرسال إشعار إلى {$user->name}...");

        $result = $pushService->sendMulticast(
            $user->pushTokens(),
            $this->option('title'),
            $this->option('body'),
            $payload,
        );

        return $this->reportMulticastResult($result);
    }

    private function reportMulticastResult(MulticastResult $result): int
    {
        $this->line("FCM accepted: {$result->successCount}; failed: {$result->failureCount}.");

        if ($result->successCount > 0) {
            $this->info('✅ قبلت FCM الرسالة. هذا لا يؤكد ظهورها على الهاتف.');

            return 0;
        }

        $this->error('❌ فشل الإرسال، تواصل مع السجلات (Logs) أو تأكد من إعدادات Firebase Credentials.');

        return 1;
    }
}
