<?php

namespace App\Services;

use App\DTOs\MulticastResult;
use App\Models\PushDevice;
use App\Models\User;
use App\Support\NotificationMetadata;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;

class PushNotificationService
{
    protected Messaging $messaging;

    private const BATCH_SIZE = 500;

    private const INVALID_TOKEN_ERRORS = ['UNREGISTERED', 'INVALID_ARGUMENT'];

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        $tokens = $user->pushTokens();

        if (empty($tokens)) {
            return false;
        }

        return $this->sendNotification($tokens, $title, $body, $data);
    }

    public function sendToMultiple(Collection $users, string $title, string $body, array $data = []): bool
    {
        $users->loadMissing('pushDevices');

        $tokens = $users
            ->flatMap(fn (User $user) => $user->pushTokens())
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($tokens)) {
            return false;
        }

        return $this->sendNotification($tokens, $title, $body, $data);
    }

    public function sendMulticast(array $tokens, string $title, string $body, array $data = []): MulticastResult
    {
        $result = new MulticastResult;
        $tokens = array_values(array_unique(array_filter($tokens)));

        if (empty($tokens)) {
            return $result;
        }

        $notificationData = NotificationMetadata::enrich($data['type'] ?? 'generic', $data);
        $stringData       = $this->stringifyData($notificationData);
        $notification     = Notification::create($title, $body);
        $batches          = array_chunk($tokens, self::BATCH_SIZE);

        foreach ($batches as $batch) {
            $this->processBatch($batch, $notification, $stringData, $result);
        }

        if ($result->invalidTokens) {
            $this->handleInvalidTokens($result->invalidTokens);
        }

        return $result;
    }

    public function sendBatch(array $notifications): void
    {
        foreach ($notifications as $notification) {
            $tokens = $notification['tokens'] ?? [];
            $title  = $notification['title']  ?? '';
            $body   = $notification['body']   ?? '';
            $extra  = $notification['data']   ?? [];

            if (empty($tokens)) {
                continue;
            }

            $this->sendMulticast($tokens, $title, $body, $extra);
        }
    }

    protected function handleInvalidTokens(array $failedTokens): void
    {
        $failedTokens = array_values(array_unique(array_filter($failedTokens)));

        if (empty($failedTokens)) {
            return;
        }

        $hashes = array_map(fn (string $token) => hash('sha256', $token), $failedTokens);

        PushDevice::whereIn('token_hash', $hashes)->delete();

        // Keep transitional legacy storage clean as well.
        User::whereIn('fcm_token', $failedTokens)->update(['fcm_token' => null]);
    }

    protected function sendNotification(array $tokens, string $title, string $body, array $data = []): bool
    {
        try {
            $tokens           = array_values(array_unique(array_filter($tokens)));
            $notification     = Notification::create($title, $body);
            $notificationData = NotificationMetadata::enrich($data['type'] ?? 'generic', $data);
            $stringData       = $this->stringifyData($notificationData);
            $message          = $this->buildMessage($notification, $stringData);

            if (count($tokens) === 1) {
                try {
                    $message = $message->withToken($tokens[0]);

                    $this->messaging->send($message);
                } catch (\Throwable $e) {
                    // Single-token sends have no multicast failure report, so inspect
                    // the exception and purge dead tokens the same way as batches.
                    $errorCode = strtoupper($e->getMessage() ?? '');

                    foreach (self::INVALID_TOKEN_ERRORS as $invalidCode) {
                        if (str_contains($errorCode, $invalidCode)) {
                            $this->handleInvalidTokens([$tokens[0]]);

                            break;
                        }
                    }

                    throw $e;
                }
            } else {
                $report = $this->messaging->sendMulticast($message, $tokens);

                $invalidTokens = [];

                foreach ($report->failures()->getItems() as $failure) {
                    $token     = $failure->target()->value();
                    $errorCode = strtoupper($failure->error()?->getMessage() ?? 'UNKNOWN');

                    foreach (self::INVALID_TOKEN_ERRORS as $invalidCode) {
                        if (str_contains($errorCode, $invalidCode)) {
                            $invalidTokens[] = $token;

                            break;
                        }
                    }
                }

                $this->handleInvalidTokens($invalidTokens);
            }

            Log::info('Push notification sent', [
                'title'        => $title,
                'tokens_count' => count($tokens),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Firebase Push Error: ' . $e->getMessage());

            return false;
        }
    }

    private function processBatch(
        array $batch,
        Notification $notification,
        array $stringData,
        MulticastResult $result,
    ): void {
        try {
            $message = $this->buildMessage($notification, $stringData);
            $report  = $this->messaging->sendMulticast($message, $batch);

            $result->successCount += $report->successes()->count();
            $result->failureCount += $report->failures()->count();

            foreach ($report->failures()->getItems() as $failure) {
                $token     = $failure->target()->value();
                $errorCode = $failure->error()?->getMessage() ?? 'UNKNOWN';

                Log::error('FCM send failure', [
                    'token_hash' => hash('sha256', $token),
                    'error_code' => $errorCode,
                    'timestamp'  => now()->toIso8601String(),
                ]);

                foreach (self::INVALID_TOKEN_ERRORS as $invalidCode) {
                    if (str_contains(strtoupper($errorCode), $invalidCode)) {
                        $result->invalidTokens[] = $token;

                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('FCM batch send error', [
                'error'      => $e->getMessage(),
                'batch_size' => count($batch),
                'timestamp'  => now()->toIso8601String(),
            ]);

            $result->failureCount += count($batch);
        }
    }

    private function buildMessage(Notification $notification, array $stringData): CloudMessage
    {
        $androidConfig = AndroidConfig::fromArray([
            'priority'     => $stringData['android_message_priority'] ?? 'normal',
            'notification' => [
                'sound'                   => 'default',
                'default_sound'           => true,
                'default_vibrate_timings' => true,
                'notification_priority'   => $stringData['android_notification_priority'] ?? 'PRIORITY_DEFAULT',
                'channel_id'              => ($stringData['severity'] ?? 'medium') === 'critical' ? 'critical-alerts' : 'general-alerts',
            ],
        ]);

        $apnsConfig = ApnsConfig::fromArray([
            'headers' => [
                'apns-priority' => $stringData['apns_priority'] ?? '5',
            ],
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'badge' => 1,
                ],
            ],
        ]);

        $webPushConfig = WebPushConfig::fromArray([
            'headers' => [
                'Urgency' => $stringData['web_urgency'] ?? 'normal',
                'TTL'     => '900',
            ],
            'notification' => [
                'title'              => $notification->title(),
                'body'               => $notification->body(),
                'icon'               => '/icons/icon-192x192.png',
                'badge'              => '/icons/icon-72x72.png',
                'tag'                => $stringData['tag'] ?? 'ministry-generic',
                'data'               => $stringData,
                'vibrate'            => array_map('intval', json_decode($stringData['vibrate'] ?? '[]', true) ?: []),
                'renotify'           => filter_var($stringData['renotify'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'requireInteraction' => filter_var($stringData['require_interaction'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'silent'             => false,
            ],
            'fcm_options' => [
                'link' => $stringData['url'] ?? '/app/dashboard',
            ],
        ]);

        return CloudMessage::new()
            ->withNotification($notification)
            ->withData($stringData)
            ->withAndroidConfig($androidConfig)
            ->withApnsConfig($apnsConfig)
            ->withWebPushConfig($webPushConfig);
    }

    private function stringifyData(array $data): array
    {
        return collect($data)
            ->map(fn (mixed $value): string => match (true) {
                is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                is_bool($value)  => $value ? 'true' : 'false',
                $value === null  => '',
                default          => (string) $value,
            })
            ->all();
    }
}
