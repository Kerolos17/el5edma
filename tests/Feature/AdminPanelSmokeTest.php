<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_render_the_filament_dashboard(): void
    {
        $admin = User::factory()->create([
            'role'      => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }
}
