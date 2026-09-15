<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalNotificationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_related_notifications_only_target_relevant_active_users_and_super_admins(): void
    {
        $assignedServant = User::factory()->create([
            'role'      => UserRole::Servant,
            'is_active' => true,
        ]);
        $familyLeader = User::factory()->create([
            'role'      => UserRole::FamilyLeader,
            'is_active' => true,
        ]);
        $serviceLeader = User::factory()->create([
            'role'      => UserRole::ServiceLeader,
            'is_active' => true,
        ]);
        $unrelatedServiceLeader = User::factory()->create([
            'role'      => UserRole::ServiceLeader,
            'is_active' => true,
        ]);
        $superAdmin = User::factory()->create([
            'role'      => UserRole::SuperAdmin,
            'is_active' => true,
        ]);
        $inactiveSuperAdmin = User::factory()->create([
            'role'      => UserRole::SuperAdmin,
            'is_active' => false,
        ]);

        $group = ServiceGroup::factory()->create([
            'leader_id'         => $familyLeader->id,
            'service_leader_id' => $serviceLeader->id,
        ]);

        $beneficiary = Beneficiary::withoutEvents(fn () => Beneficiary::factory()->create([
            'service_group_id'    => $group->id,
            'assigned_servant_id' => $assignedServant->id,
        ]));

        app(InternalNotificationService::class)->notifyRelatedUsers(
            $beneficiary,
            'critical_case',
            'Scoped title',
            'Scoped body',
            ['beneficiary_id' => $beneficiary->id],
        );

        foreach ([$assignedServant, $familyLeader, $serviceLeader, $superAdmin] as $recipient) {
            $this->assertDatabaseHas('ministry_notifications', [
                'user_id' => $recipient->id,
                'type'    => 'critical_case',
                'title'   => 'Scoped title',
            ]);
        }

        foreach ([$unrelatedServiceLeader, $inactiveSuperAdmin] as $excluded) {
            $this->assertDatabaseMissing('ministry_notifications', [
                'user_id' => $excluded->id,
                'type'    => 'critical_case',
            ]);
        }
    }
}
