<?php

namespace Tests\Feature;

use App\Models\JoinRequest;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as OAuthUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    // ── Existing accounts ──

    public function test_existing_active_member_logs_in_via_google(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->mockGoogleUser($user->email, $user->name);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/servant/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_pending_applicant_via_google_lands_on_waiting_page(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        JoinRequest::create([
            'user_id' => $user->id,
            'status'  => JoinRequest::STATUS_PENDING,
        ]);

        $this->mockGoogleUser($user->email, $user->name);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('registration.status'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_suspended_member_gets_no_session_via_google(): void
    {
        $user = User::factory()->create(['is_active' => false, 'suspended_at' => now()]);
        JoinRequest::create([
            'user_id' => $user->id,
            'status'  => JoinRequest::STATUS_APPROVED,
        ]);

        $this->mockGoogleUser($user->email, $user->name);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
    }

    // ── New visitors: same review path ──

    public function test_new_google_visitor_is_sent_to_the_completion_form(): void
    {
        $this->mockGoogleUser('newcomer@example.com', 'Newcomer');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect();
        $this->assertStringContainsString('/register/google/', $response->headers->get('Location'));
    }

    public function test_completion_form_renders_with_verified_identity(): void
    {
        $token = $this->googleToken('newcomer@example.com', 'Newcomer');

        $this->get(route('registration.google.form', ['token' => $token]))
            ->assertOk()
            ->assertSee('newcomer@example.com');
    }

    public function test_completion_creates_pending_account_and_confines_to_waiting_page(): void
    {
        $group = ServiceGroup::factory()->create(['is_active' => true]);
        $token = $this->googleToken('completer@example.com', 'Completer');

        $this->post(route('registration.google.complete', ['token' => $token]), [
            'phone'            => '01234567899',
            'password'         => 'strong-password',
            'service_group_id' => $group->id,
            'desired_role'     => 'servant',
            'privacy_consent'  => true,
        ])
            ->assertRedirect(route('registration.status'));

        $user = User::query()->where('email', 'completer@example.com')->firstOrFail();

        $this->assertFalse($user->is_active);
        $this->assertDatabaseHas('join_requests', [
            'user_id' => $user->id,
            'status'  => JoinRequest::STATUS_PENDING,
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_or_tampered_token_is_rejected(): void
    {
        $token = Crypt::encrypt([
            'email'   => 'late@example.com',
            'name'    => 'Late',
            'exp'     => now()->subMinutes(5)->getTimestamp(),
            'purpose' => 'google-registration',
        ]);

        $this->get(route('registration.google.form', ['token' => $token]))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    // ── Helpers ──

    private function mockGoogleUser(string $email, string $name): void
    {
        $oauthUser = new OAuthUser;
        $oauthUser->map(['id' => 'g-1', 'nickname' => null, 'name' => $name, 'email' => $email, 'avatar' => null]);

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($oauthUser);
    }

    private function googleToken(string $email, string $name): string
    {
        return Crypt::encrypt([
            'email'   => $email,
            'name'    => $name,
            'exp'     => now()->addMinutes(30)->getTimestamp(),
            'purpose' => 'google-registration',
        ]);
    }
}
