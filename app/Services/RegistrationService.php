<?php
namespace App\Services;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Jobs\SendFcmNotificationJob;
use App\Models\AuditLog;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * خدمة التسجيل الذاتي للخدام
 * Requirements: 3.1-3.7, 4.1-4.7, 5.1-5.5, 8.1-8.5, 9.1-9.3
 */
class RegistrationService
{
    /**
     * معالجة طلب التسجيل وإنشاء الحساب
     * Requirements: 4.1-4.7, 3.1-3.7
     *
     * @param  array         $data          بيانات التسجيل (name, email, phone, password)
     * @param  ServiceGroup  $serviceGroup  مجموعة الخدمة المرتبطة بالرمز
     * @param  string        $ipAddress     عنوان IP للطلب
     * @return User
     *
     * @throws \Exception
     */
    public function register(array $data, ServiceGroup $serviceGroup, string $ipAddress): User
    {
        try {
            $user = DB::transaction(function () use ($data, $serviceGroup, $ipAddress) {
                // إنشاء حساب المستخدم
                $user = User::createFromSelfRegistration($data, $serviceGroup);

                // تسجيل العملية في audit log (non-blocking)
                try {
                    $this->logRegistration($user, $serviceGroup, $data['token'] ?? '', $ipAddress);
                } catch (\Exception $e) {
                    Log::error('Audit log failed during registration', [
                        'user_id' => $user->id,
                        'error'   => $e->getMessage(),
                    ]);
                }

                // إشعار ترحيبي للخادم الجديد
                $this->createWelcomeNotification($user, $serviceGroup);

                // إرسال إشعارات للقادة
                $this->notifyLeaders($user, $serviceGroup);

                return $user;
            });

            Log::info('Self-registration completed', [
                'user_id'          => $user->id,
                'service_group_id' => $serviceGroup->id,
                'email'            => $user->email,
            ]);

            return $user;

        } catch (UniqueConstraintViolationException $e) {
            Log::warning('Duplicate registration attempt', [
                'email' => $data['email'] ?? 'unknown',
                'ip'    => $ipAddress,
            ]);

            throw new \RuntimeException(__('registration.errors.duplicate'), 0, $e);

        } catch (\Exception $e) {
            Log::error('Self-registration failed', [
                'email'            => $data['email'] ?? 'unknown',
                'service_group_id' => $serviceGroup->id,
                'error'            => $e->getMessage(),
                'ip'               => $ipAddress,
            ]);

            throw $e;
        }
    }

    /**
     * التحقق من عدم وجود تسجيل مكرر
     * Requirements: 9.1, 9.2
     *
     * @param  string  $email
     * @param  string  $phone
     * @return array   ['email' => bool, 'phone' => bool]
     */
    public function checkDuplicates(string $email, string $phone): array
    {
        return [
            'email' => User::where('email', $email)->exists(),
            'phone' => User::where('phone', $phone)->exists(),
        ];
    }

    /**
     * إرسال إشعارات للقادة
     * Requirements: 5.1-5.5
     *
     * @param  User          $newServant
     * @param  ServiceGroup  $serviceGroup
     * @return void
     */
    public function notifyLeaders(User $newServant, ServiceGroup $serviceGroup): void
    {
        try {
            // تحديد القادة المستهدفين
            $leaders = $this->getServiceGroupLeaders($serviceGroup);

            if ($leaders->isEmpty()) {
                Log::info('No leaders to notify for self-registration', [
                    'user_id'          => $newServant->id,
                    'service_group_id' => $serviceGroup->id,
                ]);
                return;
            }

            // إنشاء الإشعارات في قاعدة البيانات (bulk insert)
            $this->createNotificationRecords($newServant, $serviceGroup, $leaders);

            // إرسال إشعارات FCM (non-blocking)
            $this->dispatchFcmNotifications($newServant, $serviceGroup, $leaders);

        } catch (\Exception $e) {
            Log::error('Failed to notify leaders for self-registration', [
                'user_id'          => $newServant->id,
                'service_group_id' => $serviceGroup->id,
                'error'            => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * تسجيل عملية التسجيل في audit log
     * Requirements: 8.1-8.5
     *
     * @param  User          $user
     * @param  ServiceGroup  $serviceGroup
     * @param  string        $token
     * @param  string        $ipAddress
     * @return void
     */
    public function logRegistration(
        User $user,
        ServiceGroup $serviceGroup,
        string $token,
        string $ipAddress
    ): void {
        $maskedToken = $token ? substr($token, 0, 8) . '...' : '';
        AuditLog::logSelfRegistration($user, $serviceGroup, $maskedToken, $ipAddress);
    }

    /**
     * الحصول على قادة مجموعة الخدمة + أمين الخدمة + مدير النظام
     * Requirements: 5.1, 5.2
     *
     * @param  ServiceGroup  $serviceGroup
     * @return \Illuminate\Support\Collection
     */
    protected function getServiceGroupLeaders(ServiceGroup $serviceGroup): \Illuminate\Support\Collection
    {
        // جمع IDs القادة من الأسرة
        $leaderIds = collect([
            $serviceGroup->leader_id,         // أمين الأسرة
            $serviceGroup->service_leader_id, // أمين الخدمة (إن وجد)
        ])->filter();

        // جلب جميع المستخدمين: قادة الأسرة + أمناء الخدمة + مديري النظام
        return User::where(function ($query) use ($leaderIds) {
            $query->whereIn('id', $leaderIds)   // قادة الأسرة المحددين
                ->orWhere('role', UserRole::ServiceLeader->value) // جميع أمناء الخدمة
                ->orWhere('role', UserRole::SuperAdmin->value);   // جميع مديري النظام
        })
            ->where('is_active', true)
            ->get();
    }

    /**
     * إنشاء سجلات الإشعارات في قاعدة البيانات
     * Requirements: 5.1, 5.2, 5.3, 5.5
     *
     * @param  User                              $newServant
     * @param  ServiceGroup                      $serviceGroup
     * @param  \Illuminate\Support\Collection    $leaders
     * @return void
     */
    protected function createNotificationRecords(
        User $newServant,
        ServiceGroup $serviceGroup,
        \Illuminate\Support\Collection $leaders
    ): void {
        $now = now();

        $previousLocale = app()->getLocale();

        $notifications = $leaders->map(function (User $leader) use ($newServant, $serviceGroup, $now) {
            // Build notification text in the recipient's preferred locale
            app()->setLocale($leader->locale ?? 'ar');

            return [
                'user_id'    => $leader->id,
                'type'       => 'servant_registered',
                'title'      => __('notifications.servant_registered.title'),
                'body'       => __('notifications.servant_registered.body', [
                    'name'          => $newServant->name,
                    'service_group' => $serviceGroup->name,
                ]),
                'data'       => json_encode([
                    'servant_id'       => $newServant->id,
                    'servant_name'     => $newServant->name,
                    'service_group_id' => $serviceGroup->id,
                    'registered_at'    => $now->toIso8601String(),
                    'url'              => UserResource::getUrl('view', ['record' => $newServant->id]),
                ]),
                'read_at'    => null,
                'created_at' => $now,
            ];
        })->toArray();

        // Restore the original request locale
        app()->setLocale($previousLocale);

        DB::table('ministry_notifications')->insert($notifications);
    }

    /**
     * إنشاء إشعار ترحيبي للخادم الجديد
     */
    protected function createWelcomeNotification(User $newServant, ServiceGroup $serviceGroup): void
    {
        MinistryNotification::create([
            'user_id' => $newServant->id,
            'type'    => 'servant_registered',
            'title'   => __('notifications.welcome_servant.title'),
            'body'    => __('notifications.welcome_servant.body', [
                'name'          => $newServant->name,
                'service_group' => $serviceGroup->name,
            ]),
            'data'    => json_encode([
                'service_group_id' => $serviceGroup->id,
                'registered_at'    => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * إرسال إشعارات FCM للقادة الذين لديهم tokens
     * Requirements: 5.4
     *
     * @param  User                              $newServant
     * @param  ServiceGroup                      $serviceGroup
     * @param  \Illuminate\Support\Collection    $leaders
     * @return void
     */
    protected function dispatchFcmNotifications(
        User $newServant,
        ServiceGroup $serviceGroup,
        \Illuminate\Support\Collection $leaders
    ): void {
        try {
            $tokens = $leaders->pluck('fcm_token')->filter()->values()->toArray();

            if (empty($tokens)) {
                return;
            }

            $title = __('notifications.servant_registered.title');
            $body  = __('notifications.servant_registered.body', [
                'name'          => $newServant->name,
                'service_group' => $serviceGroup->name,
            ]);
            $data = [
                'servant_id'       => $newServant->id,
                'servant_name'     => $newServant->name,
                'service_group_id' => $serviceGroup->id,
                'registered_at'    => now()->toIso8601String(),
            ];

            SendFcmNotificationJob::dispatch($tokens, $title, $body, $data);

        } catch (\Exception $e) {
            // لا نرمي الخطأ — الإشعارات داخل التطبيق كافية
            Log::warning('FCM notification dispatch failed for self-registration', [
                'user_id' => $newServant->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
