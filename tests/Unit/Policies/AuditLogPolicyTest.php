<?php

namespace Tests\Unit\Policies;

use App\Models\AuditLog;
use App\Policies\AuditLogPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AuditLogPolicyTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    private AuditLogPolicy $policy;
    private AuditLog $log;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AuditLogPolicy();
        $this->log    = AuditLog::factory()->create();
    }

    public function test_admin_level_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->createSuperAdmin()));
        $this->assertTrue($this->policy->viewAny($this->createServiceLeader()));
    }

    public function test_non_admin_cannot_view_any(): void
    {
        $group = \App\Models\ServiceGroup::factory()->create();
        $this->assertFalse($this->policy->viewAny($this->createFamilyLeader($group)));
        $this->assertFalse($this->policy->viewAny($this->createServant($group)));
    }

    public function test_admin_level_can_view(): void
    {
        $this->assertTrue($this->policy->view($this->createSuperAdmin(), $this->log));
        $this->assertTrue($this->policy->view($this->createServiceLeader(), $this->log));
    }

    public function test_non_admin_cannot_view(): void
    {
        $group = \App\Models\ServiceGroup::factory()->create();
        $this->assertFalse($this->policy->view($this->createServant($group), $this->log));
    }

    public function test_create_is_always_false(): void
    {
        $this->assertFalse($this->policy->create($this->createSuperAdmin()));
        $this->assertFalse($this->policy->create($this->createServiceLeader()));
    }

    public function test_update_is_always_false(): void
    {
        $this->assertFalse($this->policy->update($this->createSuperAdmin(), $this->log));
        $this->assertFalse($this->policy->update($this->createServiceLeader(), $this->log));
    }

    public function test_delete_is_always_false(): void
    {
        $this->assertFalse($this->policy->delete($this->createSuperAdmin(), $this->log));
        $this->assertFalse($this->policy->delete($this->createServiceLeader(), $this->log));
    }

    public function test_restore_is_always_false(): void
    {
        $this->assertFalse($this->policy->restore($this->createSuperAdmin(), $this->log));
    }

    public function test_force_delete_only_super_admin(): void
    {
        $this->assertTrue($this->policy->forceDelete($this->createSuperAdmin(), $this->log));
        $this->assertFalse($this->policy->forceDelete($this->createServiceLeader(), $this->log));
    }
}
