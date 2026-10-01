<?php

namespace Tests\Feature;

use App\Models\JoinRequest;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Notifications\ResetPasswordArabic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_form_renders_for_guests(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee(__('auth.reset.request_title'));
    }

    public function test_reset_link_is_sent_in_arabic_to_registered_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', __('auth.reset.link_sent'));

        Notification::assertSentTo($user, ResetPasswordArabic::class);
    }

    public function test_unknown_email_gets_the_same_response_without_any_mail(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'ghost@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', __('auth.reset.link_sent'));

        Notification::assertNothingSent();
    }

    public function test_reset_page_renders_with_token(): void
    {
        $this->get(route('password.reset', ['token' => 'abc123', 'email' => 'x@example.com']))
            ->assertOk()
            ->assertSee(__('auth.reset.reset_title'));
    }

    public function test_valid_token_resets_password_and_logs_the_user_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect('/servant/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_destroys_other_sessions(): void
    {
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            'id'            => 'stolen-session-id',
            'user_id'       => $user->id,
            'ip_address'    => '203.0.113.9',
            'user_agent'    => 'attacker',
            'payload'       => base64_encode('x'),
            'last_activity' => now()->timestamp,
        ]);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'stolen-session-id']);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token'                 => 'not-a-real-token',
            'email'                 => $user->email,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_rejected_applicant_resets_password_but_gets_no_session(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        JoinRequest::create([
            'user_id'          => $user->id,
            'service_group_id' => ServiceGroup::factory()->create()->id,
            'status'           => JoinRequest::STATUS_REJECTED,
        ]);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }
}
