<?php

namespace Tests\Feature;

use App\Models\PushDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FcmTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_multiple_push_devices(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('fcm-token.store'), [
                'fcm_token'    => 'token-device-one',
                'platform'     => 'web',
                'device_label' => 'Chrome Android',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('fcm-token.store'), [
                'fcm_token'    => 'token-device-two',
                'platform'     => 'web',
                'device_label' => 'Desktop Chrome',
            ])
            ->assertOk();

        $this->assertCount(2, $user->fresh()->pushDevices);
        $this->assertEqualsCanonicalizing(
            ['token-device-one', 'token-device-two'],
            $user->fresh()->pushDevices->pluck('token')->all(),
        );
    }

    public function test_registering_same_token_refreshes_existing_device_instead_of_duplicating_it(): void
    {
        $user = User::factory()->create();

        $payload = [
            'fcm_token' => 'same-token',
            'platform'  => 'web',
        ];

        $this->actingAs($user)->postJson(route('fcm-token.store'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('fcm-token.store'), $payload)->assertOk();

        $this->assertSame(1, PushDevice::where('user_id', $user->id)->count());
    }

    public function test_registering_a_device_for_another_user_removes_the_previous_legacy_token(): void
    {
        $token        = 'shared-browser-token';
        $previousUser = User::factory()->create(['fcm_token' => $token]);
        $currentUser  = User::factory()->create();

        PushDevice::create([
            'user_id'      => $previousUser->id,
            'token'        => $token,
            'token_hash'   => hash('sha256', $token),
            'platform'     => 'web',
            'last_seen_at' => now(),
        ]);

        $this->actingAs($currentUser)
            ->postJson(route('fcm-token.store'), ['fcm_token' => $token])
            ->assertOk();

        $this->assertNull($previousUser->fresh()->fcm_token);
        $this->assertSame([], $previousUser->fresh()->pushTokens());
        $this->assertSame(
            $currentUser->id,
            PushDevice::where('token_hash', hash('sha256', $token))->value('user_id'),
        );
    }

    public function test_push_device_token_is_encrypted_at_rest(): void
    {
        $user  = User::factory()->create();
        $token = 'sensitive-fcm-token';

        $this->actingAs($user)
            ->postJson(route('fcm-token.store'), ['fcm_token' => $token])
            ->assertOk();

        $rawToken = DB::table('push_devices')
            ->where('token_hash', hash('sha256', $token))
            ->value('token');

        $this->assertIsString($rawToken);
        $this->assertNotSame($token, $rawToken);
        $this->assertSame(
            $token,
            PushDevice::where('token_hash', hash('sha256', $token))->firstOrFail()->token,
        );
    }

    public function test_registering_push_device_tracks_current_device_in_session(): void
    {
        $user  = User::factory()->create();
        $token = 'session-device-token';

        $this->actingAs($user)
            ->postJson(route('fcm-token.store'), ['fcm_token' => $token])
            ->assertOk()
            ->assertSessionHas('push_device_token_hash', hash('sha256', $token));
    }

    public function test_logout_revokes_only_current_push_device(): void
    {
        $user         = User::factory()->create();
        $currentToken = 'current-session-device';
        $otherToken   = 'another-device';

        $this->actingAs($user)
            ->postJson(route('fcm-token.store'), ['fcm_token' => $currentToken])
            ->assertOk();

        PushDevice::create([
            'user_id'      => $user->id,
            'token'        => $otherToken,
            'token_hash'   => hash('sha256', $otherToken),
            'platform'     => 'web',
            'last_seen_at' => now(),
        ]);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertDatabaseMissing('push_devices', [
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', $currentToken),
        ]);
        $this->assertDatabaseHas('push_devices', [
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', $otherToken),
        ]);
        $this->assertNull($user->fresh()->fcm_token);
        $this->assertGuest();
    }

    public function test_push_tokens_include_device_tokens_and_legacy_token_without_duplicates(): void
    {
        $user = User::factory()->create(['fcm_token' => 'legacy-token']);

        PushDevice::create([
            'user_id'      => $user->id,
            'token'        => 'device-token',
            'token_hash'   => hash('sha256', 'device-token'),
            'platform'     => 'web',
            'last_seen_at' => now(),
        ]);

        PushDevice::create([
            'user_id'      => $user->id,
            'token'        => 'legacy-token',
            'token_hash'   => hash('sha256', 'legacy-token'),
            'platform'     => 'web',
            'last_seen_at' => now(),
        ]);

        $this->assertEqualsCanonicalizing(
            ['device-token', 'legacy-token'],
            $user->fresh()->pushTokens(),
        );
    }
}
