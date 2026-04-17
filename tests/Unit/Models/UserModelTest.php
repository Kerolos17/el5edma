<?php

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_code_setter_stores_plain_and_hashes(): void
    {
        $user = User::factory()->create(['personal_code' => '1234']);
        $user->refresh();

        $this->assertSame('1234', $user->getRawOriginal('personal_code'));
        $this->assertSame(hash('sha256', '1234'), $user->personal_code_hash);
    }

    public function test_generate_unique_personal_code(): void
    {
        $code = User::generateUniquePersonalCode();
        $this->assertMatchesRegularExpression('/^\d{4,6}$/', $code);
    }

    public function test_generate_unique_personal_code_is_unique(): void
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = User::generateUniquePersonalCode();
        }
        $this->assertCount(10, array_unique($codes));
    }

    public function test_create_from_self_registration(): void
    {
        $group = ServiceGroup::factory()->create();
        $user  = User::createFromSelfRegistration([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'phone'    => '01234567890',
            'password' => 'password123',
        ], $group);

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertEquals(UserRole::Servant, $user->role);
        $this->assertSame($group->id, $user->service_group_id);
        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->personal_code);
    }

    public function test_can_access_panel_active(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $panel = app(Panel::class);
        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_can_access_panel_inactive(): void
    {
        $user  = User::factory()->create(['is_active' => false]);
        $panel = app(Panel::class);
        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_role_helpers(): void
    {
        $admin   = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $leader  = User::factory()->create(['role' => UserRole::ServiceLeader]);
        $fl      = User::factory()->create(['role' => UserRole::FamilyLeader]);
        $servant = User::factory()->create(['role' => UserRole::Servant]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isServant());
        $this->assertTrue($leader->isServiceLeader());
        $this->assertTrue($fl->isFamilyLeader());
        $this->assertTrue($servant->isServant());
    }

    public function test_role_is_cast_to_enum(): void
    {
        $user = User::factory()->create(['role' => 'servant']);
        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertEquals(UserRole::Servant, $user->role);
    }

    public function test_relationships(): void
    {
        $group = ServiceGroup::factory()->create();
        $user  = User::factory()->create(['service_group_id' => $group->id]);

        $this->assertInstanceOf(ServiceGroup::class, $user->serviceGroup);
        $this->assertSame($group->id, $user->serviceGroup->id);
    }
}
