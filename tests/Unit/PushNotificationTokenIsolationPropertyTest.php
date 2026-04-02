<?php

// Feature: notifications-optimization, Property 6: عزل فشل token واحد عن الباقي

namespace Tests\Unit;

use App\DTOs\MulticastResult;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\MessagingError;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use Tests\TestCase;

/**
 * Property 6: عزل فشل token واحد عن الباقي
 *
 * Validates: Requirements 4.2, 9.2
 *
 * For any group of N tokens (2 ≤ N ≤ 50) with exactly one failing token,
 * the service must complete sending to the rest without throwing an exception,
 * with successCount = N-1 and failureCount = 1.
 */
class PushNotificationTokenIsolationPropertyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Property 6: لأي مجموعة من N token (2 ≤ N ≤ 50) مع token فاشل واحد،
     * يجب أن تُكمل الخدمة إرسال الباقي بنجاح دون رمي استثناء،
     * وأن يكون successCount = N-1 و failureCount = 1.
     *
     * Validates: Requirements 4.2, 9.2
     *
     * 100 iterations with random group sizes (2 to 50 tokens, 1 always failing).
     */
    public function test_single_token_failure_is_isolated_from_rest(): void
    {
        // Feature: notifications-optimization, Property 6: عزل فشل token واحد عن الباقي

        Log::spy();

        mt_srand(12345);

        for ($i = 0; $i < 100; $i++) {
            $n = mt_rand(2, 50);

            // Build N unique tokens; the first one will fail
            $tokens = array_map(fn($j) => "token_{$i}_{$j}", range(0, $n - 1));

            // Build a MulticastSendReport with 1 failure + (N-1) successes
            $items = [];

            // Failure for the first token
            $failTarget = MessageTarget::with(MessageTarget::TOKEN, $tokens[0]);
            $error      = new MessagingError('FCM_ERROR', 0);
            $items[]    = SendReport::failure($failTarget, $error);

            // Successes for the remaining tokens
            for ($j = 1; $j < $n; $j++) {
                $successTarget = MessageTarget::with(MessageTarget::TOKEN, $tokens[$j]);
                $items[]       = SendReport::success($successTarget, ['name' => 'projects/test/messages/' . $j]);
            }

            $report = MulticastSendReport::withItems($items);

            $messagingMock = Mockery::mock(Messaging::class);
            $messagingMock
                ->shouldReceive('sendMulticast')
                ->once()
                ->andReturn($report);

            $service = new PushNotificationService($messagingMock);

            // The service must NOT throw an exception
            $threw  = false;
            $result = null;
            try {
                $result = $service->sendMulticast($tokens, 'Title', 'Body');
            } catch (\Throwable $e) {
                $threw = true;
            }

            $this->assertFalse(
                $threw,
                "Iteration {$i}: N={$n} — service threw an exception when one token failed"
            );

            $this->assertInstanceOf(
                MulticastResult::class,
                $result,
                "Iteration {$i}: N={$n} — sendMulticast should return a MulticastResult"
            );

            $this->assertSame(
                $n - 1,
                $result->successCount,
                "Iteration {$i}: N={$n} — successCount should be N-1 = " . ($n - 1)
            );

            $this->assertSame(
                1,
                $result->failureCount,
                "Iteration {$i}: N={$n} — failureCount should be exactly 1"
            );

            $messagingMock->mockery_verify();
            Mockery::resetContainer();
        }
    }

    /**
     * Edge case: all tokens fail — service should not throw, failureCount = N
     *
     * Validates: Requirements 4.2, 9.2
     */
    public function test_all_tokens_failing_does_not_throw_exception(): void
    {
        Log::spy();

        $n     = 5;
        $items = [];

        for ($j = 0; $j < $n; $j++) {
            $target  = MessageTarget::with(MessageTarget::TOKEN, "token_{$j}");
            $error   = new MessagingError('FCM_ERROR', 0);
            $items[] = SendReport::failure($target, $error);
        }

        $report = MulticastSendReport::withItems($items);

        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock->shouldReceive('sendMulticast')->once()->andReturn($report);

        $service = new PushNotificationService($messagingMock);

        $threw  = false;
        $result = null;
        try {
            $result = $service->sendMulticast(
                array_map(fn($j) => "token_{$j}", range(0, $n - 1)),
                'T',
                'B'
            );
        } catch (\Throwable $e) {
            $threw = true;
        }

        $this->assertFalse($threw, 'Service should not throw when all tokens fail');
        $this->assertSame(0, $result->successCount);
        $this->assertSame($n, $result->failureCount);
    }
}
