<?php

namespace Tests\Feature\Commands;

use App\Jobs\SendFcmNotificationJob;
use App\Models\MinistryNotification;
use App\Models\User;
use App\Support\NotificationMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RetryCriticalNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_command_dispatches_push_for_unread_critical_notification(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'fcm_token' => 'critical-token',
        ]);

        $notification = MinistryNotification::forceCreate([
            'user_id' => $user->id,
            'type'    => 'critical_case',
            'title'   => 'Critical Alert',
            'body'    => 'Needs immediate attention',
            'data'    => NotificationMetadata::enrich('critical_case', [
                'url' => '/admin/visits/1',
            ]),
            'created_at' => now()->subMinutes(15),
        ]);

        $this->artisan('notifications:retry-critical')
            ->assertSuccessful();

        Queue::assertPushed(SendFcmNotificationJob::class, 1);

        /** @var SendFcmNotificationJob $job */
        $job = Queue::pushed(SendFcmNotificationJob::class)->first();

        $this->assertSame(['critical-token'], $job->tokens);
        $this->assertSame(1, $job->data['retry_count']);
        $this->assertTrue($job->data['renotify']);
        $this->assertTrue($job->data['require_interaction']);

        $notification->refresh();

        $this->assertSame(1, $notification->data['retry_count']);
        $this->assertNotEmpty($notification->data['last_retry_at']);
    }

    public function test_retry_command_skips_notifications_that_reached_retry_limit(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'fcm_token' => 'critical-token',
        ]);

        MinistryNotification::forceCreate([
            'user_id' => $user->id,
            'type'    => 'critical_case',
            'title'   => 'Critical Alert',
            'body'    => 'Needs immediate attention',
            'data'    => NotificationMetadata::enrich('critical_case', [
                'retry_count' => 3,
                'retry_limit' => 3,
            ]),
            'created_at' => now()->subMinutes(15),
        ]);

        $this->artisan('notifications:retry-critical')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }
}
