<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendFcmNotificationJob;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegistrationNotificationLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_alerts_preserve_each_leader_locale_in_database_and_push(): void
    {
        Queue::fake();
        App::setLocale('ar');

        $familyLeader = User::factory()->create([
            'role'      => UserRole::FamilyLeader,
            'locale'    => 'ar',
            'is_active' => true,
            'fcm_token' => 'family-ar-token',
        ]);
        $serviceLeader = User::factory()->create([
            'role'      => UserRole::ServiceLeader,
            'locale'    => 'en',
            'is_active' => true,
            'fcm_token' => 'service-en-token',
        ]);
        $group = ServiceGroup::factory()->create([
            'name'              => 'Locale Group',
            'leader_id'         => $familyLeader->id,
            'service_leader_id' => $serviceLeader->id,
        ]);

        $servant = app(RegistrationService::class)->register([
            'name'     => 'Mixed Locale Servant',
            'email'    => 'mixed-locale-servant@example.com',
            'phone'    => '01234567999',
            'password' => 'password123',
            'token'    => 'locale-registration-token',
        ], $group, '127.0.0.1');

        $this->assertDatabaseHas('ministry_notifications', [
            'user_id' => $familyLeader->id,
            'type'    => 'join_request_submitted',
            'title'   => 'طلب انضمام جديد',
        ]);
        $this->assertDatabaseHas('ministry_notifications', [
            'user_id' => $serviceLeader->id,
            'type'    => 'join_request_submitted',
            'title'   => 'New join request',
        ]);

        Queue::assertPushed(
            SendFcmNotificationJob::class,
            fn (SendFcmNotificationJob $job): bool => $job->tokens === ['family-ar-token']
                && $job->title                                     === 'طلب انضمام جديد'
                && str_contains($job->body, $servant->name)
                && ($job->data['url'] ?? null) === '/app/join-requests',
        );

        Queue::assertPushed(
            SendFcmNotificationJob::class,
            fn (SendFcmNotificationJob $job): bool => $job->tokens === ['service-en-token']
                && $job->title                                     === 'New join request'
                && str_contains($job->body, $servant->name)
                && ($job->data['url'] ?? null) === '/app/join-requests',
        );

        Queue::assertPushed(SendFcmNotificationJob::class, 2);
        $this->assertSame('ar', App::getLocale());

        $this->assertSame(2, MinistryNotification::whereIn('user_id', [
            $familyLeader->id,
            $serviceLeader->id,
        ])->where('type', 'join_request_submitted')->count());
    }

    public function test_submit_notification_is_deduplicated_per_recipient(): void
    {
        Queue::fake();
        App::setLocale('ar');

        $leader = User::factory()->create([
            'role'      => UserRole::ServiceLeader,
            'locale'    => 'ar',
            'is_active' => true,
        ]);
        $group = ServiceGroup::factory()->create(['service_leader_id' => $leader->id]);

        $data = [
            'name'     => 'Dedupe Servant',
            'email'    => 'dedupe-servant@example.com',
            'phone'    => '01234567888',
            'password' => 'password123',
        ];

        app(RegistrationService::class)->register($data, $group, '127.0.0.1');

        // A second notification with the same dedupe key is silently skipped.
        $leaderNotifications = MinistryNotification::where('user_id', $leader->id)
            ->where('type', 'join_request_submitted')->get();
        $this->assertSame(1, $leaderNotifications->count());
        $this->assertNotNull($leaderNotifications->first()->dedupe_key);
    }
}
