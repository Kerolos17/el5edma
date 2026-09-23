<?php

namespace Tests\Unit\Policies;

use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Policies\ScheduledVisitPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ScheduledVisitPolicyTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private ScheduledVisitPolicy $policy;
    private ServiceGroup $groupA;
    private ServiceGroup $groupB;
    private User $serviceLeader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy        = new ScheduledVisitPolicy;
        $this->groupA        = ServiceGroup::factory()->create();
        $this->groupB        = ServiceGroup::factory()->create();
        $this->serviceLeader = $this->createServiceLeader();
        $this->groupA->update(['service_leader_id' => $this->serviceLeader->id]);
    }

    public function test_super_admin_full_access(): void
    {
        $admin = $this->createSuperAdmin();
        $sv    = ScheduledVisit::factory()->create();
        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $sv));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $sv));
        $this->assertTrue($this->policy->delete($admin, $sv));
    }

    public function test_servant_view_assigned_only(): void
    {
        $servant   = $this->createServant($this->groupA);
        $coServant = $this->createServant($this->groupA);
        $ben       = Beneficiary::factory()->create(['service_group_id' => $this->groupA->id]);

        $assigned = ScheduledVisit::factory()->create([
            'beneficiary_id'      => $ben->id,
            'assigned_servant_id' => $servant->id,
        ]);
        $assigned->syncAssignedServants([$servant->id, $coServant->id]);
        $other = ScheduledVisit::factory()->create([
            'beneficiary_id' => $ben->id,
        ]);

        $this->assertTrue($this->policy->view($servant, $assigned));
        $this->assertTrue($this->policy->view($coServant, $assigned));
        $this->assertFalse($this->policy->view($servant, $other));
    }

    public function test_servant_cannot_create_update_delete(): void
    {
        $servant = $this->createServant($this->groupA);
        $sv      = ScheduledVisit::factory()->create();
        $this->assertFalse($this->policy->create($servant));
        $this->assertFalse($this->policy->update($servant, $sv));
        $this->assertFalse($this->policy->delete($servant, $sv));
    }

    public function test_family_leader_scoped_to_group(): void
    {
        $fl     = $this->createFamilyLeader($this->groupA);
        $benIn  = Beneficiary::factory()->create(['service_group_id' => $this->groupA->id]);
        $benOut = Beneficiary::factory()->create(['service_group_id' => $this->groupB->id]);

        $svIn  = ScheduledVisit::factory()->create(['beneficiary_id' => $benIn->id]);
        $svOut = ScheduledVisit::factory()->create(['beneficiary_id' => $benOut->id]);

        $this->assertTrue($this->policy->view($fl, $svIn));
        $this->assertFalse($this->policy->view($fl, $svOut));
        $this->assertTrue($this->policy->create($fl));
        $this->assertTrue($this->policy->update($fl, $svIn));
        $this->assertFalse($this->policy->update($fl, $svOut));
        $this->assertFalse($this->policy->delete($fl, $svOut));
    }

    public function test_service_leader_is_scoped_to_managed_groups(): void
    {
        $benIn  = Beneficiary::factory()->create(['service_group_id' => $this->groupA->id]);
        $benOut = Beneficiary::factory()->create(['service_group_id' => $this->groupB->id]);
        $svIn   = ScheduledVisit::factory()->create(['beneficiary_id' => $benIn->id]);
        $svOut  = ScheduledVisit::factory()->create(['beneficiary_id' => $benOut->id]);

        $this->assertTrue($this->policy->create($this->serviceLeader));
        $this->assertTrue($this->policy->view($this->serviceLeader, $svIn));
        $this->assertTrue($this->policy->update($this->serviceLeader, $svIn));
        $this->assertTrue($this->policy->delete($this->serviceLeader, $svIn));
        $this->assertFalse($this->policy->view($this->serviceLeader, $svOut));
        $this->assertFalse($this->policy->update($this->serviceLeader, $svOut));
        $this->assertFalse($this->policy->delete($this->serviceLeader, $svOut));
    }

    public function test_super_admin_can_cancel_anything(): void
    {
        $admin = $this->createSuperAdmin();
        $sv    = $this->scheduledVisitInGroup($this->groupA);

        $this->assertTrue($this->policy->cancel($admin, $sv));
    }

    public function test_service_leader_can_cancel_managed_but_not_unmanaged(): void
    {
        $this->assertTrue($this->policy->cancel($this->serviceLeader, $this->scheduledVisitInGroup($this->groupA)));
        $this->assertFalse($this->policy->cancel($this->serviceLeader, $this->scheduledVisitInGroup($this->groupB)));
    }

    public function test_family_leader_can_cancel_own_group_only(): void
    {
        $fl = $this->createFamilyLeader($this->groupA);

        $this->assertTrue($this->policy->cancel($fl, $this->scheduledVisitInGroup($this->groupA)));
        $this->assertFalse($this->policy->cancel($fl, $this->scheduledVisitInGroup($this->groupB)));
    }

    public function test_servant_can_cancel_only_own_assigned_pending_visit(): void
    {
        $servant = $this->createServant($this->groupA);
        $other   = $this->createServant($this->groupA);

        $mine = $this->scheduledVisitInGroup($this->groupA);
        $mine->update(['assigned_servant_id' => $servant->id]);
        $mine->syncAssignedServants([$servant->id]);

        $theirs = $this->scheduledVisitInGroup($this->groupA);
        $theirs->update(['assigned_servant_id' => $other->id]);
        $theirs->syncAssignedServants([$other->id]);

        $done = $this->scheduledVisitInGroup($this->groupA, 'completed');
        $done->update(['assigned_servant_id' => $servant->id]);
        $done->syncAssignedServants([$servant->id]);

        $this->assertTrue($this->policy->cancel($servant, $mine));
        $this->assertFalse($this->policy->cancel($servant, $theirs));
        $this->assertFalse($this->policy->cancel($servant, $done));
    }

    private function scheduledVisitInGroup(ServiceGroup $group, string $status = 'pending'): ScheduledVisit
    {
        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        return ScheduledVisit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'status'         => $status,
        ]);
    }
}
