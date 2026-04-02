<?php
namespace App\Jobs;

use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job لإرسال إشعارات FCM عبر Queue بشكل غير متزامن
 * Requirements: 8.1, 8.3, 8.4
 */
class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * عدد محاولات إعادة التنفيذ عند الفشل
     * Requirement 8.4
     */
    public int $tries = 3;

    /**
     * وقت الانتظار بين المحاولات (بالثواني)
     * Requirement 8.4
     */
    public int $backoff = 60;

    /**
     * @param array  $tokens  قائمة FCM tokens
     * @param string $title   عنوان الإشعار
     * @param string $body    نص الإشعار
     * @param array  $data    بيانات إضافية اختيارية
     */
    public function __construct(
        public readonly array $tokens,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {}

    /**
     * تنفيذ الـ Job — إرسال الإشعارات عبر PushNotificationService
     * Requirement 8.1, 8.3
     */
    public function handle(PushNotificationService $pushService): void
    {
        if (empty($this->tokens)) {
            return;
        }

        $pushService->sendMulticast($this->tokens, $this->title, $this->body, $this->data);
    }

    /**
     * معالجة الفشل النهائي بعد استنفاد جميع المحاولات
     * يُسجَّل الفشل تلقائياً في جدول failed_jobs بواسطة Laravel
     * Requirement 8.4
     */
    public function failed(Throwable $exception): void
    {
        Log::error('SendFcmNotificationJob فشل نهائياً بعد ' . $this->tries . ' محاولات', [
            'exception'    => $exception->getMessage(),
            'tokens_count' => count($this->tokens),
            'title'        => $this->title,
            'timestamp'    => now()->toIso8601String(),
        ]);
    }
}
