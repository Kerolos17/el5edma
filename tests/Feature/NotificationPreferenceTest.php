<?php

namespace Tests\Feature;

use App\Livewire\WebApp\ProfilePage;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_mutes_and_unmutes_a_type(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(NotificationPreference::allows($user, 'birthday'));

        $this->assertFalse(NotificationPreference::toggle($user, 'birthday')); // now muted
        $this->assertFalse(NotificationPreference::allows($user, 'birthday'));

        $this->assertTrue(NotificationPreference::toggle($user, 'birthday')); // enabled again
        $this->assertTrue(NotificationPreference::allows($user, 'birthday'));
    }

    public function test_critical_and_admin_types_cannot_be_muted(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(NotificationPreference::toggle($user, 'critical_case'));
        $this->assertFalse(NotificationPreference::toggle($user, 'join_request_submitted'));
        $this->assertTrue(NotificationPreference::allows($user, 'critical_case'));
    }

    public function test_muted_user_receives_no_direct_notification(): void
    {
        $user = User::factory()->create();
        NotificationPreference::create(['user_id' => $user->id, 'type' => 'visit_created']);

        app(InternalNotificationService::class)->notifyUser(
            $user,
            'visit_created',
            'Title',
            'Body',
        );

        $this->assertDatabaseMissing('ministry_notifications', ['user_id' => $user->id]);
    }

    public function test_critical_notifications_bypass_preferences(): void
    {
        $user = User::factory()->create();
        NotificationPreference::create(['user_id' => $user->id, 'type' => 'visit_created']);

        app(InternalNotificationService::class)->notifyUser(
            $user,
            'critical_case',
            'Title',
            'Body',
        );

        $this->assertDatabaseHas('ministry_notifications', [
            'user_id' => $user->id,
            'type'    => 'critical_case',
        ]);
    }

    public function test_muted_users_are_filtered_out_of_bulk_notifications(): void
    {
        $muted    = User::factory()->create();
        $listener = User::factory()->create();
        NotificationPreference::create(['user_id' => $muted->id, 'type' => 'new_beneficiary']);

        app(InternalNotificationService::class)->notifyUsers(
            collect([$muted, $listener]),
            'new_beneficiary',
            'Title',
            'Body',
        );

        $this->assertDatabaseMissing('ministry_notifications', ['user_id' => $muted->id]);
        $this->assertDatabaseHas('ministry_notifications', ['user_id' => $listener->id]);
    }

    public function test_profile_toggle_updates_preferences_from_the_ui(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->call('toggleNotificationPreference', 'birthday')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'type'    => 'birthday',
        ]);

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->call('toggleNotificationPreference', 'birthday');

        $this->assertDatabaseMissing('notification_preferences', [
            'user_id' => $user->id,
            'type'    => 'birthday',
        ]);
    }
}
