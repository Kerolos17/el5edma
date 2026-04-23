<?php

namespace App\Console\Commands;

use App\Models\MinistryNotification;
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
    protected $signature = 'pwa:test-push {uid_or_email} {--title=Test Notification} {--body=This is a test push from Artisan!}';

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
        $identifier = $this->argument('uid_or_email');

        $user = User::where('email', $identifier)->orWhere('id', $identifier)->first();

        if (! $user) {
            $this->error("المستخدم {$identifier} غير موجود.");

            return;
        }

        if (empty($user->fcm_token)) {
            $this->warn("المستخدم {$identifier} ('{$user->name}') ليس لديه FCM Token مسجل. يرجى تسجيل الدخول للحساب من متصفح داعم والموافقة على الإشعارات أولاً.");

            return;
        }

        $this->info("جاري إرسال إشعار إلى {$user->name}...");

        // 1. إنشاء الإشعار في لوحة التحكم (Dashboard)
        $payload = NotificationMetadata::enrich('test_alert', [
            'severity'  => 'high',
            'url'       => route('filament.admin.resources.ministry-notifications.index'),
            'test_data' => 'This is test payload',
        ]);

        MinistryNotification::create([
            'user_id' => $user->id,
            'type'    => 'test_alert',
            'title'   => $this->option('title'),
            'body'    => $this->option('body'),
            'data'    => $payload,
        ]);
        $this->info('✅ الإشعار تم إضافته داخل لوحة التحكم (Dashboard) بنجاح!');

        // 2. إرسال الـ Push Notification عبر الهاتف/Firebase
        $success = $pushService->sendToUser(
            $user,
            $this->option('title'),
            $this->option('body'),
            $payload,
        );

        if ($success) {
            $this->info('✅ الإشعار تم إرساله بنجاح! راجع جهاز المستخدم للتأكد.');
        } else {
            $this->error('❌ فشل الإرسال، تواصل مع السجلات (Logs) أو تأكد من إعدادات Firebase Credentials.');
        }
    }
}
