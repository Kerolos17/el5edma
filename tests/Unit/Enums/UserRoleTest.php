<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function test_has_four_cases(): void
    {
        $this->assertCount(4, UserRole::cases());
    }

    public function test_values(): void
    {
        $this->assertSame('super_admin', UserRole::SuperAdmin->value);
        $this->assertSame('service_leader', UserRole::ServiceLeader->value);
        $this->assertSame('family_leader', UserRole::FamilyLeader->value);
        $this->assertSame('servant', UserRole::Servant->value);
    }

    public function test_is_admin_level(): void
    {
        $this->assertTrue(UserRole::SuperAdmin->isAdminLevel());
        $this->assertTrue(UserRole::ServiceLeader->isAdminLevel());
        $this->assertFalse(UserRole::FamilyLeader->isAdminLevel());
        $this->assertFalse(UserRole::Servant->isAdminLevel());
    }

    public function test_options_returns_all_four(): void
    {
        $options = UserRole::options();
        $this->assertCount(4, $options);
        $this->assertArrayHasKey('super_admin', $options);
        $this->assertArrayHasKey('service_leader', $options);
        $this->assertArrayHasKey('family_leader', $options);
        $this->assertArrayHasKey('servant', $options);
    }

    public function test_label_returns_string(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->assertIsString($role->label());
            $this->assertNotEmpty($role->label());
        }
    }
}
