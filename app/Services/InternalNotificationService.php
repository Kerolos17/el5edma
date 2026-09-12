<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Events\NewMinistryNotification;
use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use App\Models\User;
use App\Support\NotificationMetadata;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class InternalNotificationService
{
    /**
     * إرسال إشعار لجميع المشرفين والخوادم
     */
    public function notifyAll(string $type, string $title, string $body, array $data = []): void
    {
        User::where('is_active', true)
            ->select(['id', 'locale'])
            ->chunkById(200, function (Collection $chunk) use ($type, $title, $body, $data) {
                $this->notifyUsers($chunk, $type, $title, $body, $data);
            });
    }

    /**
     * إرسال إشعار للأشخاص المعنيين بمخدوم معين فقط
     * (الخدام المسؤولين عنه + أمين أسرته + أمناء الخدمة + مديري النظام)
     */
    public function notifyRelatedUsers(Beneficiary $beneficiary, string $type, string $title, string $body, array $data = []): void
    {
        $userIds = [];

        // 1. الخادم المعين
        if ($beneficiary->assigned_servant_id) {
            $userIds[] = $beneficiary->assigned_servant_id;
        }

        // 2. أمين الأسرة (نجلبه من ServiceGroup)
        if ($beneficiary->serviceGroup && $beneficiary->serviceGroup->leader_id) {
            $userIds[] = $beneficiary->serviceGroup->leader_id;
        }

        // 3. المشرفين العامين وأمناء الخدمة
        $superAdminsAndLeaders = User::whereIn('role', [UserRole::SuperAdmin->value, UserRole::ServiceLeader->value])->pluck('id')->toArray();
        $userIds               = array_merge($userIds, $superAdminsAndLeaders);

        // إزالة التكرارات (في حال كان الخادم هو نفسه أمين الأسرة مثلاً)
        $userIds = array_unique($userIds);

        if (empty($userIds)) {
            return;
        }

        $users = User::whereIn('id', $userIds)->get();

        $this->notifyUsers($users, $type, $title, $body, $data);
    }

    /**
     * إرسال إشعار لمستخدمين محددين
     */
    public function notifyUsers(Collection $users, string $type, string $title, string $body, array $data = []): void
    {
        $notifications = [];
        $broadcasts    = [];
        $now           = now();
        $payload       = NotificationMetadata::enrich($type, $data);

        foreach ($users as $user) {
            $notifications[] = [
                'user_id'    => $user->id,
                'type'       => $type,
                'title'      => $title,
                'body'       => $body,
                'data'       => json_encode($payload),
                'created_at' => $now,
            ];

            $broadcasts[] = [
                'user_id' => $user->id,
                'payload' => [
                    'type'       => $type,
                    'title'      => $title,
                    'body'       => $body,
                    'data'       => $payload,
                    'created_at' => $now->toDateTimeString(),
                ],
            ];
        }

        // Database is the source of truth. Persist first so realtime clients
        // cannot refresh before the notification row actually exists.
        if (! empty($notifications)) {
            MinistryNotification::insert($notifications);
        }

        foreach ($broadcasts as $broadcast) {
            Cache::forget('notifications_unread_' . $broadcast['user_id']);

            try {
                event(new NewMinistryNotification(
                    $broadcast['user_id'],
                    $broadcast['payload'],
                ));
            } catch (\Throwable $e) {
                // Realtime delivery is best-effort; the database notification remains available.
            }
        }
    }

    /**
     * إرسال إشعار لمستخدم واحد
     */
    public function notifyUser(User $user, string $type, string $title, string $body, array $data = []): void
    {
        MinistryNotification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => NotificationMetadata::enrich($type, $data),
        ]);

        Cache::forget('notifications_unread_' . $user->id);

        // Dispatch a broadcast event so the user's browser updates in real-time
        try {
            event(new NewMinistryNotification($user->id, [
                'type'       => $type,
                'title'      => $title,
                'body'       => $body,
                'data'       => NotificationMetadata::enrich($type, $data),
                'created_at' => now()->toDateTimeString(),
            ]));
        } catch (\Throwable $e) {
            // broadcasting not configured — ignore
        }
    }
}
