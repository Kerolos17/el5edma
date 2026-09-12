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

            $pushes = $this->createNotificationRecords($newServant, $serviceGroup, $leaders);
            $this->dispatchFcmNotifications($newServant, $pushes);
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
    ): array {
        $now            = now();
        $previousLocale = app()->getLocale();
        $notifications  = [];
        $pushes         = [];

        try {
            foreach ($leaders as $leader) {
                app()->setLocale($leader->locale ?? 'ar');

                $title = __('notifications.servant_registered.title');
                $body  = __('notifications.servant_registered.body', [
                    'name'          => $newServant->name,
                    'service_group' => $serviceGroup->name,
                ]);
                $data = NotificationMetadata::enrich('servant_registered', [
                    'servant_id'       => $newServant->id,
                    'servant_name'     => $newServant->name,
                    'service_group_id' => $serviceGroup->id,
                    'registered_at'    => $now->toIso8601String(),
                    'url'              => '/app/users',
                ]);

                $notifications[] = [
                    'user_id'    => $leader->id,
                    'type'       => 'servant_registered',
                    'title'      => $title,
                    'body'       => $body,
                    'data'       => json_encode($data),
                    'read_at'    => null,
                    'created_at' => $now,
                ];

                $tokens = $leader->pushTokens();

                if ($tokens !== []) {
                    $pushes[] = [
                        'tokens' => $tokens,
                        'title'  => $title,
                        'body'   => $body,
                        'data'   => $data,
                    ];
                }
            }

            DB::table('ministry_notifications')->insert($notifications);

            return $pushes;
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
            ]),
        ]);
    }

    protected function dispatchFcmNotifications(User $newServant, array $pushes): void
    {
        try {
            foreach ($pushes as $push) {
                SendFcmNotificationJob::dispatch(
                    $push['tokens'],
                    $push['title'],
                    $push['body'],
                    $push['data'],
                );
            }
        } catch (\Exception $e) {
            Log::warning('FCM notification dispatch failed for self-registration', [
                'user_id' => $newServant->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
