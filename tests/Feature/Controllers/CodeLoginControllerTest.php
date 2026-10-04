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

    public function test_valid_code_logs_in_and_redirects_to_role_dashboard(): void
    {
        $user = User::factory()->create([
            'personal_code' => '1234',
            'is_active'     => true,
        ]);

        $response = $this->post(route('login.code'), ['code' => '1234']);

        // Factory users are servants — routed to their own dashboard.
        $response->assertRedirect('/servant/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_new_numeric_code_is_six_digits_and_logs_in(): void
    {
        $user = User::factory()->create([
            'personal_code' => User::generateUniquePersonalCode(),
            'is_active'     => true,
        ]);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $user->personal_code);

        $this->post(route('login.code'), ['code' => $user->personal_code])
            ->assertRedirect('/servant/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_legacy_kh_format_codes_still_work(): void
    {
        $user = User::factory()->create([
            'personal_code' => 'KH-AB24-56',
            'is_active'     => true,
        ]);

        $this->post(route('login.code'), ['code' => 'KH-AB24-56'])
            ->assertRedirect('/servant/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_code_alone_is_enough_no_password_required(): void
    {
        $user = User::factory()->create([
            'personal_code' => '5678',
            'is_active'     => true,
        ]);

        $this->post(route('login.code'), ['code' => '5678'])
            ->assertRedirect('/servant/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_code_returns_error(): void
    {
        $response = $this->post(route('login.code'), ['code' => '9999']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_inactive_user_without_request_cannot_log_in(): void
    {
        User::factory()->create([
            'personal_code' => '5678',
            'is_active'     => false,
        ]);

        $response = $this->post(route('login.code'), ['code' => '5678']);

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

        $this->post(route('login.code'), ['code' => '7788'])
            ->assertRedirect(route('registration.status'));

        $this->assertAuthenticatedAs($applicant);
    }

    public function test_code_too_short_fails_validation(): void
    {
        $response = $this->post(route('login.code'), ['code' => '12']);

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
