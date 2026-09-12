<?php

namespace Tests\Feature;

use App\Models\PushDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            $user->fresh()->pushDevices()->pluck('token')->all(),
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
