<?php

namespace Tests\Unit\Policies;

use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\ServiceGroup;
use App\Policies\MedicalFilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class MedicalFilePolicyTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    private MedicalFilePolicy $policy;
    private ServiceGroup $groupA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new MedicalFilePolicy();
        $this->groupA = ServiceGroup::factory()->create();
    }

    public function test_update_always_returns_false(): void
    {
        $admin = $this->createSuperAdmin();
        $mf = MedicalFile::factory()->create();
        $this->assertFalse($this->policy->update($admin, $mf));
    }

    public function test_super_admin_can_view_create_delete(): void
    {
        $admin = $this->createSuperAdmin();
        $mf = MedicalFile::factory()->create();
        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $mf));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->delete($admin, $mf));
    }

    public function test_servant_view_scoped_to_assigned_beneficiary(): void
    {
        $servant = $this->createServant($this->groupA);
        $benAssigned = Beneficiary::factory()->create([
            'service_group_id' => $this->groupA->id,
            'assigned_servant_id' => $servant->id,
        ]);
        $benOther = Beneficiary::factory()->create([
            'service_group_id' => $this->groupA->id,
        ]);

        $mfAssigned = MedicalFile::factory()->create(['beneficiary_id' => $benAssigned->id]);
        $mfOther    = MedicalFile::factory()->create(['beneficiary_id' => $benOther->id]);

        $this->assertTrue($this->policy->view($servant, $mfAssigned));
        $this->assertFalse($this->policy->view($servant, $mfOther));
    }

    public function test_servant_cannot_create_or_delete(): void
    {
        $servant = $this->createServant($this->groupA);
        $mf = MedicalFile::factory()->create();
        $this->assertFalse($this->policy->create($servant));
        $this->assertFalse($this->policy->delete($servant, $mf));
    }

    public function test_family_leader_scoped_to_group(): void
    {
        $fl = $this->createFamilyLeader($this->groupA);
        $benInGroup = Beneficiary::factory()->create(['service_group_id' => $this->groupA->id]);
        $benOutGroup = Beneficiary::factory()->create(['service_group_id' => ServiceGroup::factory()->create()->id]);

        $mfIn  = MedicalFile::factory()->create(['beneficiary_id' => $benInGroup->id]);
        $mfOut = MedicalFile::factory()->create(['beneficiary_id' => $benOutGroup->id]);

        $this->assertTrue($this->policy->view($fl, $mfIn));
        $this->assertFalse($this->policy->view($fl, $mfOut));
        $this->assertTrue($this->policy->create($fl));
    }
}
