<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ServantPanelAccessTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    #[Test]
    public function unauthenticated_user_is_redirected_from_servant_dashboard(): void
    {
        $this->get(route('servant.dashboard'))->assertRedirect();
    }

    #[Test]
    public function inactive_servant_is_logged_out_and_redirected(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group, ['is_active' => false]);

        $this->actingAs($servant)
            ->get(route('servant.dashboard'))
            ->assertRedirect();
    }

    #[Test]
    public function active_servant_can_access_dashboard(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $this->actingAs($servant)
            ->get(route('servant.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function active_family_leader_can_access_servant_panel(): void
    {
        $group  = ServiceGroup::factory()->create();
        $leader = $this->createFamilyLeader($group);

        $this->actingAs($leader)
            ->get(route('servant.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function active_service_leader_can_access_servant_panel(): void
    {
        $leader = $this->createServiceLeader();

        $this->actingAs($leader)
            ->get(route('servant.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function active_super_admin_can_access_servant_panel(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get(route('servant.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function servant_is_redirected_from_root_servant_url(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $this->actingAs($servant)
            ->get('/servant')
            ->assertRedirect(route('servant.dashboard'));
    }
}
