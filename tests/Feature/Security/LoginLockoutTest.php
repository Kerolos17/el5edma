<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Verifies the hard lockout that kicks in after accumulating failed
 * code-login attempts (per IP, in the controller) regardless of the
 * per-minute route throttle.
 */
class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable the route-level burst throttle so we can test the
        // application-level hard lockout in isolation.
        $this->withoutMiddleware(ThrottleRequests::class);
        RateLimiter::clear('code-login|127.0.0.1');
        RateLimiter::clear('code-login-global');
    }

    public function test_failed_attempts_accumulate_and_trigger_ip_lockout(): void
    {
        // 10 failed attempts should trigger lockout
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('login.code'), ['code' => '000000']);
        }

        $response = $this->post(route('login.code'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');

        $errors = session('errors')?->get('code') ?? [];
        $this->assertNotEmpty($errors);
        // The error key should reference a lockout (not just invalid code)
        $firstError = $errors[0];
        $this->assertNotEquals(__('auth.invalid_code'), $firstError);
    }

    public function test_successful_login_resets_failed_attempt_counter(): void
    {
        $user = User::factory()->create(['personal_code' => '123456', 'is_active' => true]);

        // Several failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.code'), ['code' => '000000']);
        }

        // Successful login resets the counter
        $this->post(route('login.code'), ['code' => '123456']);

        // Counter cleared — another batch of failures should not yet lock out
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login.code'), ['code' => '000000']);
            $response->assertSessionHasErrors('code');
            $errors = session('errors')?->get('code') ?? [];
            $this->assertEquals(__('auth.invalid_code'), $errors[0]);
        }
    }

    public function test_valid_code_succeeds_even_after_some_failures(): void
    {
        $user = User::factory()->create(['personal_code' => '654321', 'is_active' => true]);

        // 5 failed attempts — still under the lockout threshold
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.code'), ['code' => '000000']);
        }

        $response = $this->post(route('login.code'), ['code' => '654321']);

        // Factory users are servants — routed to their own dashboard.
        $response->assertRedirect('/servant/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_failures_from_different_ips_trigger_a_global_lockout(): void
    {
        $user = User::factory()->create(['personal_code' => '654321', 'is_active' => true]);

        // 30 failures, each from a different IP, so no single IP is locked.
        for ($i = 0; $i < 30; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.' . ($i + 1)])
                ->post(route('login.code'), ['code' => '000000']);
        }

        // A fresh IP, even with a valid code, is refused while the global cap holds.
        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])
            ->post(route('login.code'), ['code' => '654321'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_successful_login_does_not_reset_the_global_counter(): void
    {
        User::factory()->create(['personal_code' => '654321', 'is_active' => true]);

        for ($i = 0; $i < 29; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.' . ($i + 1)])
                ->post(route('login.code'), ['code' => '000000']);
        }

        // One valid login in the middle must not wipe the global budget.
        $this->withServerVariables(['REMOTE_ADDR' => '10.8.8.8'])
            ->post(route('login.code'), ['code' => '654321']);
        auth()->logout();

        $this->withServerVariables(['REMOTE_ADDR' => '10.7.7.7'])
            ->post(route('login.code'), ['code' => '000000']);

        $this->assertTrue(RateLimiter::tooManyAttempts('code-login-global', 30));
    }
}
