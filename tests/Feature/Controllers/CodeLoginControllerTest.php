<?php

namespace Tests\Feature\Controllers;

use App\Models\JoinRequest;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeLoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_code_and_password_logs_in_and_redirects_to_role_dashboard(): void
    {
        $user = User::factory()->create([
            'personal_code' => '1234',
            'is_active'     => true,
        ]);

        $response = $this->post(route('login.code'), [
            'code'     => '1234',
            'password' => 'password',
        ]);

        // Factory users are servants — routed to their own dashboard.
        $response->assertRedirect('/servant/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_new_kh_format_code_logs_in(): void
    {
        $user = User::factory()->create([
            'personal_code' => User::generateUniquePersonalCode(),
            'is_active'     => true,
        ]);

        $this->assertMatchesRegularExpression('/^KH-[A-Z2-9]{4}-\d{2}$/', $user->personal_code);

        $this->post(route('login.code'), [
            'code'     => $user->personal_code,
            'password' => 'password',
        ])->assertRedirect('/servant/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_code_alone_without_password_is_not_enough(): void
    {
        User::factory()->create([
            'personal_code' => '5678',
            'is_active'     => true,
        ]);

        $this->post(route('login.code'), ['code' => '5678'])
            ->assertSessionHasErrors('password');

        $this->post(route('login.code'), ['code' => '5678', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_invalid_code_returns_error(): void
    {
        $response = $this->post(route('login.code'), [
            'code'     => '9999',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_inactive_user_without_request_cannot_log_in(): void
    {
        User::factory()->create([
            'personal_code' => '5678',
            'is_active'     => false,
        ]);

        $response = $this->post(route('login.code'), [
            'code'     => '5678',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_pending_applicant_can_code_login_and_lands_on_waiting_page(): void
    {
        $applicant = User::factory()->create([
            'personal_code' => '7788',
            'is_active'     => false,
        ]);

        JoinRequest::create([
            'user_id'          => $applicant->id,
            'service_group_id' => ServiceGroup::factory()->create()->id,
            'status'           => JoinRequest::STATUS_PENDING,
        ]);

        $this->post(route('login.code'), [
            'code'     => '7788',
            'password' => 'password',
        ])->assertRedirect(route('registration.status'));

        $this->assertAuthenticatedAs($applicant);
    }

    public function test_code_too_short_fails_validation(): void
    {
        $response = $this->post(route('login.code'), [
            'code'     => '12',
            'password' => 'password',
        ]);

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

        $this->post(route('login.code'), [
            'code'     => '4321',
            'password' => 'password',
        ]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
