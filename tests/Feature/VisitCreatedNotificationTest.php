<?php

namespace Tests\Feature;

use App\Jobs\SendFcmNotificationJob;
use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class VisitCreatedNotificationTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    public function test_normal_visit_notifies_related_users_in_database_and_push_queue(): void
    {
        Queue::fake();

        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group, [
            'name'      => 'خادم الزيارة',
            'fcm_token' => 'servant-token',
        ]);
        $leader   = $this->createFamilyLeader($group, ['fcm_token' => 'leader-token']);
        $admin    = $this->createSuperAdmin(['fcm_token' => 'admin-token']);
        $outsider = $this->createServiceLeader(['fcm_token' => 'outsider-token']);
        $group->update(['leader_id' => $leader->id]);

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $group->id,
            'assigned_servant_id' => $servant->id,
        ]);

        $this->actingAs($servant);

        $visit = Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by'     => $servant->id,
            'is_critical'    => false,
        ]);

        $this->assertSame(3, MinistryNotification::where('type', 'visit_created')->count());
        $this->assertDatabaseHas('ministry_notifications', [
            'user_id' => $leader->id,
            'type'    => 'visit_created',
        ]);
        $this->assertDatabaseHas('ministry_notifications', [
            'user_id' => $admin->id,
            'type'    => 'visit_created',
        ]);
        $this->assertDatabaseMissing('ministry_notifications', [
            'user_id' => $outsider->id,
            'type'    => 'visit_created',
        ]);

        Queue::assertPushed(SendFcmNotificationJob::class, 3);
        Queue::assertPushed(SendFcmNotificationJob::class, fn (SendFcmNotificationJob $job): bool => $job->data['type'] === 'visit_created'
                && $job->data['visit_id']                                                                               === $visit->id
                && $job->data['url']                                                                                    === '/app/visit/' . $visit->id);
    }

    public function test_critical_visit_sends_only_the_critical_notification_type(): void
    {
        Queue::fake();

        $group       = ServiceGroup::factory()->create();
        $servant     = $this->createServant($group, ['fcm_token' => 'servant-token']);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $group->id,
            'assigned_servant_id' => $servant->id,
        ]);

        $this->actingAs($servant);

        Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by'     => $servant->id,
            'is_critical'    => true,
        ]);

        $this->assertDatabaseHas('ministry_notifications', [
            'user_id' => $servant->id,
            'type'    => 'critical_case',
        ]);
        $this->assertDatabaseMissing('ministry_notifications', [
            'user_id' => $servant->id,
            'type'    => 'visit_created',
        ]);
    }
}
