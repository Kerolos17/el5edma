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
            'type'    => 'servant_registered',
            'title'   => 'خادم جديد انضم للخدمة',
        ]);
        $this->assertDatabaseHas('ministry_notifications', [
            'user_id' => $serviceLeader->id,
            'type'    => 'servant_registered',
            'title'   => 'New Servant Registered',
        ]);

        Queue::assertPushed(SendFcmNotificationJob::class, function (SendFcmNotificationJob $job) use ($servant): bool {
            return $job->tokens === ['family-ar-token']
                && $job->title === 'خادم جديد انضم للخدمة'
                && str_contains($job->body, $servant->name)
                && ($job->data['url'] ?? null) === '/app/users';
        });

        Queue::assertPushed(SendFcmNotificationJob::class, function (SendFcmNotificationJob $job) use ($servant): bool {
            return $job->tokens === ['service-en-token']
                && $job->title === 'New Servant Registered'
                && str_contains($job->body, $servant->name)
                && ($job->data['url'] ?? null) === '/app/users';
        });

        Queue::assertPushed(SendFcmNotificationJob::class, 2);
        $this->assertSame('ar', App::getLocale());

        $this->assertSame(2, MinistryNotification::whereIn('user_id', [
            $familyLeader->id,
            $serviceLeader->id,
        ])->where('type', 'servant_registered')->count());
    }
}
