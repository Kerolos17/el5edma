<?php

namespace Tests\Unit\Policies;

use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Policies\ScheduledVisitPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ScheduledVisitPolicyTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private ScheduledVisitPolicy $policy;
    private ServiceGroup $groupA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ScheduledVisitPolicy;
        $this->groupA = ServiceGroup::factory()->create();
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
        $servant = $this->createServant($this->groupA);
        $ben     = Beneficiary::factory()->create(['service_group_id' => $this->groupA->id]);

        $assigned = ScheduledVisit::factory()->create([
            'beneficiary_id'      => $ben->id,
            'assigned_servant_id' => $servant->id,
        ]);
        $other = ScheduledVisit::factory()->create([
            'beneficiary_id' => $ben->id,
        ]);

        $this->assertTrue($this->policy->view($servant, $assigned));
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
        $benOut = Beneficiary::factory()->create(['service_group_id' => ServiceGroup::factory()->create()->id]);

        $svIn  = ScheduledVisit::factory()->create(['beneficiary_id' => $benIn->id]);
        $svOut = ScheduledVisit::factory()->create(['beneficiary_id' => $benOut->id]);

        $this->assertTrue($this->policy->view($fl, $svIn));
        $this->assertFalse($this->policy->view($fl, $svOut));
        $this->assertTrue($this->policy->create($fl));
        $this->assertTrue($this->policy->update($fl, $svIn));
        $this->assertFalse($this->policy->update($fl, $svOut));
    }
}
