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
 * code-login attempts regardless of the per-minute throttle.
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
    }

    public function test_failed_attempts_accumulate_and_trigger_lockout(): void
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

        $response->assertRedirect('/app/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
