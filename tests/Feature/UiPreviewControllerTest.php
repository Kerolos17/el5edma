<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPreviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'array']);
    }

    public function test_servant_ui_preview_route_requires_authentication(): void
    {
        $this->get(route('ui-preview.servant'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_open_servant_ui_preview_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('ui-preview.servant'))
            ->assertOk()
            ->assertSee('Quick Mission')
            ->assertSee('Care Board')
            ->assertSee('Beneficiary-Centered');
    }

    public function test_authenticated_user_can_open_full_demo_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('ui-preview.full-demo'))
            ->assertOk()
            ->assertSee('Frontend Demo')
            ->assertSee('Beneficiary Profile')
            ->assertSee('Family Leader Board');
    }
}
