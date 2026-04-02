<?php
namespace Tests\Unit;

use App\Models\ServiceGroup;
use App\Models\User;
use App\Policies\ServiceGroupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * اختبارات صلاحيات إدارة روابط التسجيل في ServiceGroupPolicy
 * Requirements: 7.6
 */
class ServiceGroupPolicyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private ServiceGroupPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ServiceGroupPolicy();
    }

    #[Test]
    public function super_admin_can_manage_registration_link_for_any_service_group(): void
    {
        $superAdmin   = User::factory()->create(['role' => 'super_admin']);
        $serviceGroup = ServiceGroup::factory()->create();

        $result = $this->policy->manageRegistrationLink($superAdmin, $serviceGroup);

        $this->assertTrue($result);
    }

    #[Test]
    public function service_leader_can_manage_registration_link_for_any_service_group(): void
    {
        $serviceLeader = User::factory()->create(['role' => 'service_leader']);
        $serviceGroup  = ServiceGroup::factory()->create();

        $result = $this->policy->manageRegistrationLink($serviceLeader, $serviceGroup);

        $this->assertTrue($result);
    }

    #[Test]
    public function family_leader_can_manage_registration_link_for_own_service_group(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $familyLeader = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $serviceGroup->id,
        ]);

        $result = $this->policy->manageRegistrationLink($familyLeader, $serviceGroup);

        $this->assertTrue($result);
    }

    #[Test]
    public function family_leader_cannot_manage_registration_link_for_other_service_group(): void
    {
        $serviceGroup1 = ServiceGroup::factory()->create();
        $serviceGroup2 = ServiceGroup::factory()->create();
        $familyLeader  = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $serviceGroup1->id,
        ]);

        $result = $this->policy->manageRegistrationLink($familyLeader, $serviceGroup2);

        $this->assertFalse($result);
    }

    #[Test]
    public function servant_cannot_manage_registration_link_for_any_service_group(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $servant      = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup->id,
        ]);

        $result = $this->policy->manageRegistrationLink($servant, $serviceGroup);

        $this->assertFalse($result);
    }

    #[Test]
    public function servant_cannot_manage_registration_link_even_for_own_service_group(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $servant      = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup->id,
        ]);

        $result = $this->policy->manageRegistrationLink($servant, $serviceGroup);

        $this->assertFalse($result);
    }

    #[Test]
    public function super_admin_can_manage_multiple_service_groups(): void
    {
        $superAdmin    = User::factory()->create(['role' => 'super_admin']);
        $serviceGroup1 = ServiceGroup::factory()->create();
        $serviceGroup2 = ServiceGroup::factory()->create();
        $serviceGroup3 = ServiceGroup::factory()->create();

        $this->assertTrue($this->policy->manageRegistrationLink($superAdmin, $serviceGroup1));
        $this->assertTrue($this->policy->manageRegistrationLink($superAdmin, $serviceGroup2));
        $this->assertTrue($this->policy->manageRegistrationLink($superAdmin, $serviceGroup3));
    }

    #[Test]
    public function service_leader_can_manage_multiple_service_groups(): void
    {
        $serviceLeader = User::factory()->create(['role' => 'service_leader']);
        $serviceGroup1 = ServiceGroup::factory()->create();
        $serviceGroup2 = ServiceGroup::factory()->create();
        $serviceGroup3 = ServiceGroup::factory()->create();

        $this->assertTrue($this->policy->manageRegistrationLink($serviceLeader, $serviceGroup1));
        $this->assertTrue($this->policy->manageRegistrationLink($serviceLeader, $serviceGroup2));
        $this->assertTrue($this->policy->manageRegistrationLink($serviceLeader, $serviceGroup3));
    }

    #[Test]
    public function family_leader_access_is_scoped_to_own_group_only(): void
    {
        $serviceGroup1 = ServiceGroup::factory()->create();
        $serviceGroup2 = ServiceGroup::factory()->create();
        $familyLeader  = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $serviceGroup1->id,
        ]);

        // Can access own group
        $this->assertTrue($this->policy->manageRegistrationLink($familyLeader, $serviceGroup1));

        // Cannot access other group
        $this->assertFalse($this->policy->manageRegistrationLink($familyLeader, $serviceGroup2));
    }
}
