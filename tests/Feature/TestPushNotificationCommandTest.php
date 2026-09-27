<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\MulticastResult;
use App\Models\MinistryNotification;
use App\Models\PushDevice;
use App\Models\ServiceGroup;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class TestPushNotificationCommandTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function fakeService(bool $succeed = true): PushNotificationService
    {
        $fake = Mockery::mock(PushNotificationService::class);

        $fake->shouldReceive('sendMulticast')
            ->andReturnUsing(
                fn (array $tokens) => new MulticastResult(
                    successCount: $succeed ? count($tokens) : 0,
                    failureCount: $succeed ? 0 : count($tokens),
                ),
            );

        $this->swap(PushNotificationService::class, $fake);

        return $fake;
    }

    #[Test]
    public function token_mode_sends_directly_without_user_lookup_or_db_writes(): void
    {
        $this->fakeService();

        $this->artisan('pwa:test-push', ['--token' => 'direct-token-abc'])
            ->assertOk();

        $this->assertSame(0, MinistryNotification::count());
    }

    #[Test]
    public function user_mode_uses_registered_device_tokens(): void
    {
        $this->fakeService();

        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        PushDevice::create([
            'user_id'      => $servant->id,
            'token'        => 'device-token-xyz',
            'token_hash'   => hash('sha256', 'device-token-xyz'),
            'platform'     => 'web',
            'last_seen_at' => now(),
        ]);

        $this->artisan('pwa:test-push', ['uid_or_email' => $servant->email])
            ->assertOk();

        // No dashboard notification row is created by the push test command.
        $this->assertSame(0, MinistryNotification::count());
    }

    #[Test]
    public function device_mode_only_sends_to_a_device_owned_by_the_user(): void
    {
        $fake = Mockery::mock(PushNotificationService::class);
        $this->swap(PushNotificationService::class, $fake);
        $group  = ServiceGroup::factory()->create();
        $owner  = $this->createServant($group);
        $other  = $this->createServant($group);
        $device = PushDevice::create([
            'user_id'      => $owner->id,
            'token'        => 'owner-device-token',
            'token_hash'   => hash('sha256', 'owner-device-token'),
            'platform'     => 'web',
            'last_seen_at' => now(),
        ]);

        $fake->shouldReceive('sendMulticast')
            ->once()
            ->withArgs(fn (array $tokens) => $tokens === ['owner-device-token'])
            ->andReturn(new MulticastResult(successCount: 1));

        $this->artisan('pwa:test-push', [
            'uid_or_email' => $owner->email,
            '--device-id'  => $device->id,
        ])->assertOk();

        $this->artisan('pwa:test-push', [
            'uid_or_email' => $other->email,
            '--device-id'  => $device->id,
        ])->assertFailed();
    }

    #[Test]
    public function user_without_tokens_exits_nonzero_without_sending(): void
    {
        $fake = $this->fakeService();

        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $this->artisan('pwa:test-push', ['uid_or_email' => $servant->email])
            ->assertFailed();
    }

    #[Test]
    public function unknown_user_exits_nonzero(): void
    {
        $this->fakeService();

        $this->artisan('pwa:test-push', ['uid_or_email' => 'ghost@ministry.local'])
            ->assertFailed();
    }

    #[Test]
    public function missing_identifier_and_token_exits_nonzero(): void
    {
        $this->fakeService();

        $this->artisan('pwa:test-push')->assertFailed();
    }
}
