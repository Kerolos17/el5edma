<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FcmTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_requires_auth(): void
    {
        $response = $this->postJson('/fcm-token', ['fcm_token' => 'abc123']);
        $response->assertUnauthorized();
    }

    public function test_store_saves_token(): void
    {
        $user     = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/fcm-token', ['fcm_token' => 'fcm-token-123']);
        $response->assertOk();
        $this->assertSame('fcm-token-123', $user->fresh()->fcm_token);
    }
}
