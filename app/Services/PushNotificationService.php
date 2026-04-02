<?php
namespace App\Services;

use App\DTOs\MulticastResult;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class PushNotificationService
{
    protected Messaging $messaging;

    /** Maximum tokens per Firebase multicast request */
    private const BATCH_SIZE = 500;

    /** Error codes that indicate a token is permanently invalid */
    private const INVALID_TOKEN_ERRORS = ['UNREGISTERED', 'INVALID_ARGUMENT'];

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * إرسال إشعار لمستخدم واحد
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (empty($user->fcm_token)) {
            return false;
        }

        return $this->sendNotification([$user->fcm_token], $title, $body, $data);
    }

    /**
     * إرسال إشعار لمجموعة مستخدمين
     */
    public function sendToMultiple(Collection $users, string $title, string $body, array $data = []): bool
    {
        $tokens = $users->pluck('fcm_token')->filter()->toArray();

        if (empty($tokens)) {
            return false;
        }

        return $this->sendNotification($tokens, $title, $body, $data);
    }

    /**
     * إرسال Multicast مع تقسيم تلقائي لـ 500 token/batch
     * Requirements 4.1, 4.2, 4.3, 9.2
     */
    public function sendMulticast(array $tokens, string $title, string $body, array $data = []): MulticastResult
    {
        $result = new MulticastResult();

        if (empty($tokens)) {
            return $result;
        }

        $stringData   = array_map('strval', $data);
        $notification = Notification::create($title, $body);
        $batches      = array_chunk($tokens, self::BATCH_SIZE);

        foreach ($batches as $batch) {
            $this->processBatch($batch, $notification, $stringData, $result);
        }

        if ($result->invalidTokens) {
            $this->handleInvalidTokens($result->invalidTokens);
        }

        return $result;
    }

    /**
     * معالجة دفعة من إشعارات مختلفة (عناوين/محتوى مختلف)
     * Requirements 4.4
     *
     * @param array $notifications  مصفوفة من ['tokens'=>[], 'title'=>'', 'body'=>'', 'data'=>[]]
     */
    public function sendBatch(array $notifications): void
    {
        foreach ($notifications as $notification) {
            $tokens = $notification['tokens'] ?? [];
            $title  = $notification['title'] ?? '';
            $body   = $notification['body'] ?? '';
            $extra  = $notification['data'] ?? [];

            if (empty($tokens)) {
                continue;
            }

            $this->sendMulticast($tokens, $title, $body, $extra);
        }
    }

    /**
     * تنظيف tokens غير الصالحة بعد استجابة Firebase
     * Requirements 4.3
     */
    protected function handleInvalidTokens(array $failedTokens): void
    {
        if (empty($failedTokens)) {
            return;
        }

        User::whereIn('fcm_token', $failedTokens)->update(['fcm_token' => null]);
    }

    /**
     * المعالجة الفعلية للإرسال عبر Firebase (للتوافق الداخلي)
     */
    protected function sendNotification(array $tokens, string $title, string $body, array $data = []): bool
    {
        try {
            $notification = Notification::create($title, $body);
            $stringData   = array_map('strval', $data);

            if (count($tokens) === 1) {
                $message = CloudMessage::new ()
                    ->withToken($tokens[0])
                    ->withNotification($notification)
                    ->withData($stringData);

                $this->messaging->send($message);
            } else {
                $message = CloudMessage::new ()
                    ->withNotification($notification)
                    ->withData($stringData);

                $this->messaging->sendMulticast($message, $tokens);
            }

            Log::info('تم إرسال Push Notification بنجاح', [
                'title'        => $title,
                'tokens_count' => count($tokens),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Firebase Push Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * معالجة دفعة واحدة من tokens وتحديث MulticastResult
     * Requirements 4.2, 9.2
     */
    private function processBatch(
        array $batch,
        Notification $notification,
        array $stringData,
        MulticastResult $result
    ): void {
        try {
            $message = CloudMessage::new ()
                ->withNotification($notification)
                ->withData($stringData);

            $report = $this->messaging->sendMulticast($message, $batch);

            $result->successCount += $report->successes()->count();
            $result->failureCount += $report->failures()->count();

            foreach ($report->failures()->getItems() as $failure) {
                $token     = $failure->target()->value();
                $errorCode = $failure->error()?->getMessage() ?? 'UNKNOWN';

                // Log anonymized failure details — Requirement 9.2
                Log::error('FCM send failure', [
                    'token_hash' => hash('sha256', $token),
                    'error_code' => $errorCode,
                    'timestamp'  => now()->toIso8601String(),
                ]);

                // Collect invalid tokens for cleanup — Requirement 4.3
                foreach (self::INVALID_TOKEN_ERRORS as $invalidCode) {
                    if (str_contains(strtoupper($errorCode), $invalidCode)) {
                        $result->invalidTokens[] = $token;
                        break;
                    }
                }
            }

        } catch (\Exception $e) {
            // Log the batch failure but do not stop other batches — Requirement 4.2
            Log::error('FCM batch send error', [
                'error'      => $e->getMessage(),
                'batch_size' => count($batch),
                'timestamp'  => now()->toIso8601String(),
            ]);

            $result->failureCount += count($batch);
        }
    }
}
