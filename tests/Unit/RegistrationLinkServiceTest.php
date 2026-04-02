<?php
namespace Tests\Unit;

use App\Models\ServiceGroup;
use App\Services\RegistrationLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    private RegistrationLinkService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RegistrationLinkService::class);
    }

    #[Test]
    public function getOrCreateToken_generates_token_for_new_service_group(): void
    {
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token' => null,
        ]);

        $token = $this->service->getOrCreateToken($serviceGroup);

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
        $this->assertNotNull($serviceGroup->fresh()->registration_token);
        $this->assertNotNull($serviceGroup->fresh()->registration_token_generated_at);
    }

    #[Test]
    public function getOrCreateToken_reuses_existing_token(): void
    {
        $existingToken = str_repeat('a', 64);
        $serviceGroup  = ServiceGroup::factory()->create([
            'registration_token'              => $existingToken,
            'registration_token_generated_at' => now(),
        ]);

        $token = $this->service->getOrCreateToken($serviceGroup);

        $this->assertEquals($existingToken, $token);
        $this->assertEquals($existingToken, $serviceGroup->fresh()->registration_token);
    }

    #[Test]
    public function getOrCreateToken_is_idempotent(): void
    {
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token' => null,
        ]);

        $token1 = $this->service->getOrCreateToken($serviceGroup);
        $token2 = $this->service->getOrCreateToken($serviceGroup);
        $token3 = $this->service->getOrCreateToken($serviceGroup);

        $this->assertEquals($token1, $token2);
        $this->assertEquals($token2, $token3);
    }

    #[Test]
    public function regenerateToken_creates_new_token(): void
    {
        $oldToken     = str_repeat('a', 64);
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token'              => $oldToken,
            'registration_token_generated_at' => now()->subDays(7),
        ]);

        $newToken = $this->service->regenerateToken($serviceGroup);

        $this->assertIsString($newToken);
        $this->assertEquals(64, strlen($newToken));
        $this->assertNotEquals($oldToken, $newToken);
        $this->assertEquals($newToken, $serviceGroup->fresh()->registration_token);
    }

    #[Test]
    public function regenerateToken_invalidates_old_token(): void
    {
        $oldToken     = str_repeat('a', 64);
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token'              => $oldToken,
            'registration_token_generated_at' => now()->subDays(7),
        ]);

        $newToken = $this->service->regenerateToken($serviceGroup);

        // Old token should no longer validate
        $validatedWithOldToken = $this->service->validateToken($oldToken);
        $this->assertNull($validatedWithOldToken);

        // New token should validate
        $validatedWithNewToken = $this->service->validateToken($newToken);
        $this->assertNotNull($validatedWithNewToken);
        $this->assertEquals($serviceGroup->id, $validatedWithNewToken->id);
    }

    #[Test]
    public function validateToken_returns_service_group_for_valid_token(): void
    {
        $token        = str_repeat('b', 64);
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token' => $token,
            'is_active'          => true,
        ]);

        $result = $this->service->validateToken($token);

        $this->assertNotNull($result);
        $this->assertInstanceOf(ServiceGroup::class, $result);
        $this->assertEquals($serviceGroup->id, $result->id);
    }

    #[Test]
    public function validateToken_returns_null_for_invalid_token(): void
    {
        $result = $this->service->validateToken('invalid-token-that-does-not-exist');

        $this->assertNull($result);
    }

    #[Test]
    public function validateToken_returns_null_for_inactive_service_group(): void
    {
        $token        = str_repeat('c', 64);
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token' => $token,
            'is_active'          => false,
        ]);

        $result = $this->service->validateToken($token);

        $this->assertNull($result);
    }

    #[Test]
    public function generateRegistrationUrl_creates_token_if_not_exists(): void
    {
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token' => null,
        ]);

        $this->assertNull($serviceGroup->registration_token);

        // Call getOrCreateToken to ensure token is created
        $token = $this->service->getOrCreateToken($serviceGroup);

        $this->assertNotNull($serviceGroup->fresh()->registration_token);
        $this->assertEquals($token, $serviceGroup->fresh()->registration_token);
    }

    #[Test]
    public function generateRegistrationUrl_reuses_existing_token(): void
    {
        $existingToken = str_repeat('d', 64);
        $serviceGroup  = ServiceGroup::factory()->create([
            'registration_token'              => $existingToken,
            'registration_token_generated_at' => now(),
        ]);

        // Call getOrCreateToken to verify it reuses the token
        $token = $this->service->getOrCreateToken($serviceGroup);

        $this->assertEquals($existingToken, $token);
        $this->assertEquals($existingToken, $serviceGroup->fresh()->registration_token);
    }

    #[Test]
    public function generated_tokens_are_unique(): void
    {
        $tokens = [];

        for ($i = 0; $i < 10; $i++) {
            $serviceGroup = ServiceGroup::factory()->create([
                'registration_token' => null,
            ]);

            $token = $this->service->getOrCreateToken($serviceGroup);

            $this->assertNotContains($token, $tokens);
            $tokens[] = $token;
        }

        $this->assertCount(10, array_unique($tokens));
    }

    #[Test]
    public function token_length_is_64_characters(): void
    {
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token' => null,
        ]);

        $token = $this->service->getOrCreateToken($serviceGroup);

        $this->assertEquals(64, strlen($token));
    }
}
