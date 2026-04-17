<?php

namespace Tests\Unit\Policies;

use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Policies\MinistryNotificationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class MinistryNotificationPolicyTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private MinistryNotificationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new MinistryNotificationPolicy;
    }

    public function test_all_roles_can_view_any(): void
    {
        $group = ServiceGroup::factory()->create();
        $this->assertTrue($this->policy->viewAny($this->createSuperAdmin()));
        $this->assertTrue($this->policy->viewAny($this->createServiceLeader()));
        $this->assertTrue($this->policy->viewAny($this->createFamilyLeader($group)));
        $this->assertTrue($this->policy->viewAny($this->createServant($group)));
    }

    public function test_user_can_view_own_notification(): void
    {
        $owner        = $this->createServant(ServiceGroup::factory()->create());
        $notification = MinistryNotification::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->view($owner, $notification));
    }

    public function test_user_cannot_view_others_notification(): void
    {
        $group        = ServiceGroup::factory()->create();
        $owner        = $this->createServant($group);
        $other        = $this->createServant($group);
        $notification = MinistryNotification::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($this->policy->view($other, $notification));
    }

    public function test_admin_level_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->createSuperAdmin()));
        $this->assertTrue($this->policy->create($this->createServiceLeader()));
    }

    public function test_non_admin_cannot_create(): void
    {
        $group = ServiceGroup::factory()->create();
        $this->assertFalse($this->policy->create($this->createFamilyLeader($group)));
        $this->assertFalse($this->policy->create($this->createServant($group)));
    }

    public function test_admin_level_can_update(): void
    {
        $owner        = $this->createServant(ServiceGroup::factory()->create());
        $notification = MinistryNotification::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->update($this->createSuperAdmin(), $notification));
        $this->assertTrue($this->policy->update($this->createServiceLeader(), $notification));
    }

    public function test_user_can_delete_own_notification(): void
    {
        $group        = ServiceGroup::factory()->create();
        $owner        = $this->createServant($group);
        $notification = MinistryNotification::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->delete($owner, $notification));
    }

    public function test_admin_can_delete_any_notification(): void
    {
        $owner        = $this->createServant(ServiceGroup::factory()->create());
        $notification = MinistryNotification::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->delete($this->createSuperAdmin(), $notification));
        $this->assertTrue($this->policy->delete($this->createServiceLeader(), $notification));
    }

    public function test_non_owner_non_admin_cannot_delete(): void
    {
        $group        = ServiceGroup::factory()->create();
        $owner        = $this->createServant($group);
        $other        = $this->createServant($group);
        $notification = MinistryNotification::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($this->policy->delete($other, $notification));
    }

    public function test_force_delete_only_super_admin(): void
    {
        $owner        = $this->createServant(ServiceGroup::factory()->create());
        $notification = MinistryNotification::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->forceDelete($this->createSuperAdmin(), $notification));
        $this->assertFalse($this->policy->forceDelete($this->createServiceLeader(), $notification));
    }
}
