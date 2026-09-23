<?php

namespace App\Console\Commands;

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
    protected $signature = 'pwa:test-push {uid_or_email?} {--token=} {--title=Test Notification} {--body=This is a test push from Artisan!}';

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

            return $this->reportResult($result->successCount > 0);
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

        if (empty($user->pushTokens())) {
            $this->warn("المستخدم {$identifier} ('{$user->name}') ليس لديه FCM Token مسجل. يرجى تسجيل الدخول للحساب من متصفح داعم والموافقة على الإشعارات أولاً.");

            return 1;
        }

        $this->info("جاري إرسال إشعار إلى {$user->name}...");

        $success = $pushService->sendToUser(
            $user,
            $this->option('title'),
            $this->option('body'),
            $payload,
        );

        return $this->reportResult($success);
    }

    private function reportResult(bool $success): int
    {
        if ($success) {
            $this->info('✅ الإشعار تم إرساله بنجاح! راجع جهاز المستخدم للتأكد.');

            return 0;
        }

        $this->error('❌ فشل الإرسال، تواصل مع السجلات (Logs) أو تأكد من إعدادات Firebase Credentials.');

        return 1;
    }
}
