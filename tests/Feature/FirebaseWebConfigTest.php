<?php

namespace Tests\Feature;

use Tests\TestCase;

class FirebaseWebConfigTest extends TestCase
{
    public function test_it_returns_only_browser_safe_firebase_configuration(): void
    {
        config()->set('services.firebase_web', [
            'apiKey'            => 'browser-api-key',
            'authDomain'        => 'ministry.example.firebaseapp.com',
            'projectId'         => 'ministry-example',
            'storageBucket'     => 'ministry-example.firebasestorage.app',
            'messagingSenderId' => '123456789',
            'appId'             => '1:123456789:web:example',
            'measurementId'     => 'G-EXAMPLE',
            'vapidKey'          => 'browser-vapid-public-key',
        ]);

        $response = $this->getJson('/firebase-config')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertExactJson(config('services.firebase_web'));

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_it_is_rate_limited(): void
    {
        foreach (range(1, 60) as $attempt) {
            $this->getJson('/firebase-config')->assertOk();
        }

        $this->getJson('/firebase-config')->assertStatus(429);
    }
}
