<?php

// Feature: notifications-optimization, Property 5: تقسيم Multicast إلى دفعات 500

namespace Tests\Unit;

use App\DTOs\MulticastResult;
use App\Services\PushNotificationService;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Property 5: تقسيم Multicast إلى دفعات 500
 *
 * Validates: Requirements 4.1
 *
 * For any N unique tokens, the number of Firebase sendMulticast calls = ceil(N / 500).
 */
class PushNotificationServicePropertyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Property 5: لأي N unique token (1 ≤ N ≤ 1500)، عدد استدعاءات sendMulticast = ceil(N / 500)
     *
     * Validates: Requirements 4.1
     *
     * Manual property-based test: 100 iterations with random N values.
     */
    public function test_multicast_batching_calls_firebase_ceil_n_over_500_times(): void
    {
        $emptyReport = MulticastSendReport::withItems([]);

        mt_srand(42);

        for ($i = 0; $i < 100; $i++) {
            $n             = mt_rand(1, 1500);
            $expectedCalls = (int) ceil($n / 500);

            $messagingMock = Mockery::mock(Messaging::class);
            $messagingMock
                ->shouldReceive('sendMulticast')
                ->times($expectedCalls)
                ->andReturn($emptyReport);

            $service = new PushNotificationService($messagingMock);
            $tokens  = $this->uniqueTokens($n);

            $result = $service->sendMulticast($tokens, 'Title', 'Body');

            $this->assertInstanceOf(
                MulticastResult::class,
                $result,
                "Iteration {$i}: N={$n} — sendMulticast should return a MulticastResult",
            );

            $messagingMock->mockery_verify();
            Mockery::resetContainer();
        }
    }

    public function test_multicast_with_empty_tokens_makes_no_firebase_calls(): void
    {
        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock->shouldNotReceive('sendMulticast');

        $service = new PushNotificationService($messagingMock);
        $result  = $service->sendMulticast([], 'Title', 'Body');

        $this->assertInstanceOf(MulticastResult::class, $result);
        $this->assertSame(0, $result->successCount);
        $this->assertSame(0, $result->failureCount);
    }

    public function test_exactly_500_unique_tokens_makes_exactly_one_firebase_call(): void
    {
        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock
            ->shouldReceive('sendMulticast')
            ->once()
            ->andReturn(MulticastSendReport::withItems([]));

        $service = new PushNotificationService($messagingMock);
        $service->sendMulticast($this->uniqueTokens(500), 'T', 'B');
        $this->addToAssertionCount(1);
    }

    public function test_501_unique_tokens_makes_exactly_two_firebase_calls(): void
    {
        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock
            ->shouldReceive('sendMulticast')
            ->twice()
            ->andReturn(MulticastSendReport::withItems([]));

        $service = new PushNotificationService($messagingMock);
        $service->sendMulticast($this->uniqueTokens(501), 'T', 'B');
        $this->addToAssertionCount(1);
    }

    public function test_1000_unique_tokens_makes_exactly_two_firebase_calls(): void
    {
        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock
            ->shouldReceive('sendMulticast')
            ->twice()
            ->andReturn(MulticastSendReport::withItems([]));

        $service = new PushNotificationService($messagingMock);
        $service->sendMulticast($this->uniqueTokens(1000), 'T', 'B');
        $this->addToAssertionCount(1);
    }

    public function test_duplicate_tokens_are_deduplicated_before_batching(): void
    {
        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock
            ->shouldReceive('sendMulticast')
            ->once()
            ->andReturn(MulticastSendReport::withItems([]));

        $service = new PushNotificationService($messagingMock);
        $service->sendMulticast(array_fill(0, 1000, 'same-token'), 'T', 'B');
        $this->addToAssertionCount(1);
    }

    public function test_single_token_makes_exactly_one_firebase_call(): void
    {
        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock
            ->shouldReceive('sendMulticast')
            ->once()
            ->andReturn(MulticastSendReport::withItems([]));

        $service = new PushNotificationService($messagingMock);
        $service->sendMulticast(['single-token'], 'T', 'B');
        $this->addToAssertionCount(1);
    }

    private function uniqueTokens(int $count): array
    {
        return array_map(
            static fn (int $index): string => "token-{$index}",
            range(1, $count),
        );
    }
}
