<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Verifies the hard lockouts that kick in after accumulating failed
 * code-login attempts regardless of the per-minute throttle: one per IP
 * (controller) and one per account (CodeLoginService).
 */
class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable the route-level burst throttle so we can test the
        // application-level hard lockouts in isolation.
        $this->withoutMiddleware(ThrottleRequests::class);
        RateLimiter::clear('code-login|127.0.0.1');
    }

    public function test_failed_attempts_accumulate_and_trigger_ip_lockout(): void
    {
        // 10 failed attempts should trigger lockout
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('login.code'), ['code' => '000000', 'password' => 'wrong']);
        }

        $response = $this->post(route('login.code'), ['code' => '000000', 'password' => 'wrong']);

        $response->assertSessionHasErrors('code');

        $errors = session('errors')?->get('code') ?? [];
        $this->assertNotEmpty($errors);
        // The error key should reference a lockout (not just invalid code)
        $firstError = $errors[0];
        $this->assertNotEquals(__('auth.code_credentials'), $firstError);
    }

    public function test_successful_login_resets_failed_attempt_counter(): void
    {
        $user = User::factory()->create(['personal_code' => '123456', 'is_active' => true]);

        // Several failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.code'), ['code' => '000000', 'password' => 'wrong']);
        }

        // Successful login resets the counter
        $this->post(route('login.code'), ['code' => '123456', 'password' => 'password']);

        // Counter cleared — another batch of failures should not yet lock out
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login.code'), ['code' => '000000', 'password' => 'wrong']);
            $response->assertSessionHasErrors('code');
            $errors = session('errors')?->get('code') ?? [];
            $this->assertEquals(__('auth.code_credentials'), $errors[0]);
        }
    }

    public function test_valid_code_succeeds_even_after_some_failures(): void
    {
        $user = User::factory()->create(['personal_code' => '654321', 'is_active' => true]);

        // 5 failed attempts — still under the lockout threshold
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.code'), ['code' => '000000', 'password' => 'wrong']);
        }

        $response = $this->post(route('login.code'), ['code' => '654321', 'password' => 'password']);

        // Factory users are servants — routed to their own dashboard.
        $response->assertRedirect('/servant/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_repeated_wrong_passwords_lock_the_account(): void
    {
        $user = User::factory()->create(['personal_code' => '775511', 'is_active' => true]);

        // The correct code with wrong passwords — 5 times hits the
        // per-account limiter inside CodeLoginService.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.code'), ['code' => '775511', 'password' => 'guess-' . $i]);
        }

        // Even the CORRECT password is now refused while locked.
        $this->post(route('login.code'), ['code' => '775511', 'password' => 'password'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }
}
