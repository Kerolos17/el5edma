<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Jobs\SendFcmNotificationJob;
use App\Models\AuditLog;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Support\NotificationMetadata;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * خدمة التسجيل الذاتي للخدام
 * Requirements: 3.1-3.7, 4.1-4.7, 5.1-5.5, 8.1-8.5, 9.1-9.3
 */
class RegistrationService
{
    public function register(array $data, ServiceGroup $serviceGroup, string $ipAddress): User
    {
        try {
            $user = DB::transaction(function () use ($data, $serviceGroup, $ipAddress) {
                $user = User::createFromSelfRegistration($data, $serviceGroup);

                try {
                    $this->logRegistration($user, $serviceGroup, $data['token'] ?? '', $ipAddress);
                } catch (\Exception $e) {
                    Log::error('Audit log failed during registration', [
                        'user_id' => $user->id,
                        'error'   => $e->getMessage(),
                    ]);
                }

                return $user;
            });

            try {
                $this->createWelcomeNotification($user, $serviceGroup);
            } catch (\Throwable $e) {
                Log::warning('Welcome notification failed after self-registration', [
                    'user_id'          => $user->id,
                    'service_group_id' => $serviceGroup->id,
                    'error'            => $e->getMessage(),
                ]);
            }

            $this->notifyLeaders($user, $serviceGroup);

            Log::info('Self-registration completed', [
                'user_id'          => $user->id,
                'service_group_id' => $serviceGroup->id,
            ]);

            return $user;
        } catch (UniqueConstraintViolationException $e) {
            Log::warning('Duplicate registration attempt', [
                'service_group_id' => $serviceGroup->id,
                'ip'               => $ipAddress,
            ]);

            throw new \RuntimeException(__('registration.errors.duplicate'), 0, $e);
        } catch (\Exception $e) {
            Log::error('Self-registration failed', [
                'service_group_id' => $serviceGroup->id,
                'error'            => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function checkDuplicates(string $email, string $phone): array
    {
        return [
            'email' => User::where('email', $email)->exists(),
            'phone' => User::where('phone', $phone)->exists(),
        ];
    }

    public function notifyLeaders(User $newServant, ServiceGroup $serviceGroup): void
    {
        try {
            $leaders = $this->getServiceGroupLeaders($serviceGroup);

            if ($leaders->isEmpty()) {
                Log::info('No leaders to notify for self-registration', [
                    'user_id'          => $newServant->id,
                    'service_group_id' => $serviceGroup->id,
                ]);

                return;
            }

            $this->createNotificationRecords($newServant, $serviceGroup, $leaders);
            $this->dispatchFcmNotifications($newServant, $serviceGroup, $leaders);
        } catch (\Throwable $e) {
            Log::warning('Failed to notify leaders for self-registration', [
                'user_id'          => $newServant->id,
                'service_group_id' => $serviceGroup->id,
                'error'            => $e->getMessage(),
            ]);
        }
    }

    public function logRegistration(
        User $user,
        ServiceGroup $serviceGroup,
        string $token,
        string $ipAddress,
    ): void {
        $maskedToken = $token ? substr($token, 0, 8) . '...' : '';
        AuditLog::logSelfRegistration($user, $serviceGroup, $maskedToken, $ipAddress);
    }

    protected function getServiceGroupLeaders(ServiceGroup $serviceGroup): Collection
    {
        $leaderIds = collect([
            $serviceGroup->leader_id,
            $serviceGroup->service_leader_id,
        ])->filter()->unique()->values();

        return User::where(function ($query) use ($leaderIds) {
            if ($leaderIds->isNotEmpty()) {
                $query->whereIn('id', $leaderIds);
            }

            $method = $leaderIds->isNotEmpty() ? 'orWhere' : 'where';
            $query->{$method}('role', UserRole::SuperAdmin->value);
        })
            ->where('is_active', true)
            ->with('pushDevices')
            ->get();
    }

    protected function createNotificationRecords(
        User $newServant,
        ServiceGroup $serviceGroup,
        Collection $leaders,
    ): void {
        $now            = now();
        $previousLocale = app()->getLocale();
        $notifications  = [];

        try {
            foreach ($leaders as $leader) {
                $leaderLocale = $leader->locale ?? 'ar';
                app()->setLocale($leaderLocale);

                $notifications[] = [
                    'user_id' => $leader->id,
                    'type'    => 'servant_registered',
                    'title'   => __('notifications.servant_registered.title'),
                    'body'    => __('notifications.servant_registered.body', [
                        'name'          => $newServant->name,
                        'service_group' => $serviceGroup->name,
                    ]),
                    'data' => json_encode(NotificationMetadata::enrich('servant_registered', [
                        'servant_id'       => $newServant->id,
                        'servant_name'     => $newServant->name,
                        'service_group_id' => $serviceGroup->id,
                        'registered_at'    => $now->toIso8601String(),
                        'locale'           => $leaderLocale,
                        'url'              => '/app/users',
                    ])),
                    'read_at'    => null,
                    'created_at' => $now,
                ];
            }

            DB::table('ministry_notifications')->insert($notifications);
        } finally {
            app()->setLocale($previousLocale);
        }
    }

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
            'data' => NotificationMetadata::enrich('welcome_servant', [
                'service_group_id' => $serviceGroup->id,
                'registered_at'    => now()->toIso8601String(),
                'locale'           => $newServant->locale ?? app()->getLocale(),
            ]),
        ]);
    }

    protected function dispatchFcmNotifications(
        User $newServant,
        ServiceGroup $serviceGroup,
        Collection $leaders,
    ): void {
        $previousLocale = app()->getLocale();

        try {
            foreach ($leaders as $leader) {
                $tokens = $leader->pushTokens();

                if ($tokens === []) {
                    continue;
                }

                $leaderLocale = $leader->locale ?? 'ar';
                app()->setLocale($leaderLocale);

                $title = __('notifications.servant_registered.title');
                $body  = __('notifications.servant_registered.body', [
                    'name'          => $newServant->name,
                    'service_group' => $serviceGroup->name,
                ]);
                $data = NotificationMetadata::enrich('servant_registered', [
                    'servant_id'       => $newServant->id,
                    'servant_name'     => $newServant->name,
                    'service_group_id' => $serviceGroup->id,
                    'registered_at'    => now()->toIso8601String(),
                    'locale'           => $leaderLocale,
                    'url'              => '/app/users',
                ]);

                SendFcmNotificationJob::dispatch($tokens, $title, $body, $data);
            }
        } catch (\Exception $e) {
            Log::warning('FCM notification dispatch failed for self-registration', [
                'user_id' => $newServant->id,
                'error'   => $e->getMessage(),
            ]);
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}
