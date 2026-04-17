<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeLoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_code_logs_in_and_redirects_to_admin(): void
    {
        $user = User::factory()->create([
            'personal_code' => '1234',
            'is_active'     => true,
        ]);

        $response = $this->post(route('login.code'), ['code' => '1234']);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_code_returns_error(): void
    {
        $response = $this->post(route('login.code'), ['code' => '9999']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create([
            'personal_code' => '5678',
            'is_active'     => false,
        ]);

        $response = $this->post(route('login.code'), ['code' => '5678']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_code_too_short_fails_validation(): void
    {
        $response = $this->post(route('login.code'), ['code' => '12']);

        $response->assertSessionHasErrors('code');
    }

    public function test_code_too_long_fails_validation(): void
    {
        $response = $this->post(route('login.code'), ['code' => '1234567']);

        $response->assertSessionHasErrors('code');
    }

    public function test_missing_code_fails_validation(): void
    {
        $response = $this->post(route('login.code'), []);

        $response->assertSessionHasErrors('code');
    }

    public function test_login_updates_last_login_at(): void
    {
        $user = User::factory()->create([
            'personal_code' => '4321',
            'is_active'     => true,
            'last_login_at' => null,
        ]);

        $this->post(route('login.code'), ['code' => '4321']);

        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
