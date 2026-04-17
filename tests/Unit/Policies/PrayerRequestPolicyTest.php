<?php

namespace Tests\Unit\Policies;

use App\Models\Beneficiary;
use App\Models\PrayerRequest;
use App\Models\ServiceGroup;
use App\Policies\PrayerRequestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class PrayerRequestPolicyTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private PrayerRequestPolicy $policy;
    private ServiceGroup $groupA;
    private PrayerRequest $prA;
    private PrayerRequest $prB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PrayerRequestPolicy;
        $this->groupA = ServiceGroup::factory()->create();
        $groupB       = ServiceGroup::factory()->create();

        $benA = Beneficiary::factory()->create(['service_group_id' => $this->groupA->id]);
        $benB = Beneficiary::factory()->create(['service_group_id' => $groupB->id]);

        $servant   = $this->createServant($this->groupA);
        $this->prA = PrayerRequest::factory()->create(['beneficiary_id' => $benA->id, 'created_by' => $servant->id]);
        $this->prB = PrayerRequest::factory()->create(['beneficiary_id' => $benB->id]);
    }

    public function test_super_admin_full_access(): void
    {
        $admin = $this->createSuperAdmin();
        $this->assertTrue($this->policy->view($admin, $this->prA));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $this->prA));
        $this->assertTrue($this->policy->delete($admin, $this->prA));
    }

    public function test_family_leader_scoped_to_group(): void
    {
        $fl = $this->createFamilyLeader($this->groupA);
        $this->assertTrue($this->policy->view($fl, $this->prA));
        $this->assertFalse($this->policy->view($fl, $this->prB));
        $this->assertTrue($this->policy->update($fl, $this->prA));
        $this->assertFalse($this->policy->update($fl, $this->prB));
    }

    public function test_servant_sees_own_only(): void
    {
        $servant = $this->createServant($this->groupA);
        $ownPr   = PrayerRequest::factory()->create([
            'beneficiary_id' => Beneficiary::factory()->create(['service_group_id' => $this->groupA->id])->id,
            'created_by'     => $servant->id,
        ]);

        $this->assertTrue($this->policy->view($servant, $ownPr));
        $this->assertFalse($this->policy->view($servant, $this->prA)); // created by another servant
        $this->assertFalse($this->policy->update($servant, $ownPr));
    }

    public function test_servant_can_create(): void
    {
        $servant = $this->createServant($this->groupA);
        $this->assertTrue($this->policy->create($servant));
    }
}
