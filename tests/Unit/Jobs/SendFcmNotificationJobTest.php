<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendFcmNotificationJob;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Unit tests for SendFcmNotificationJob
 *
 * Validates: Requirements 8.4
 *
 * Verifies that the job is recorded in failed_jobs after 3 failed attempts
 * and that the failed() method logs the failure properly.
 */
class SendFcmNotificationJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * The job has $tries = 3 configured.
     *
     * Validates: Requirements 8.4
     */
    public function test_job_has_three_tries_configured(): void
    {
        $job = new SendFcmNotificationJob(
            tokens: ['token1', 'token2'],
            title: 'Test Title',
            body: 'Test Body',
        );

        $this->assertSame(3, $job->tries);
    }

    /**
     * The job has $backoff = 60 seconds configured.
     *
     * Validates: Requirements 8.4
     */
    public function test_job_has_backoff_of_60_seconds(): void
    {
        $job = new SendFcmNotificationJob(
            tokens: ['token1'],
            title: 'Title',
            body: 'Body',
        );

        $this->assertSame(60, $job->backoff);
    }

    /**
     * The failed() method logs an error with job details including title, tokens_count, and timestamp.
     *
     * Validates: Requirements 8.4
     */
    public function test_failed_method_logs_error_with_job_details(): void
    {
        $loggedMessage = null;
        $loggedContext = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use (&$loggedMessage, &$loggedContext) {
                $loggedMessage = $message;
                $loggedContext = $context;

                return true;
            });

        $tokens    = ['token_a', 'token_b', 'token_c'];
        $title     = 'إشعار تجريبي';
        $exception = new RuntimeException('Firebase connection failed');

        $job = new SendFcmNotificationJob(
            tokens: $tokens,
            title: $title,
            body: 'نص الإشعار',
        );

        $job->failed($exception);

        $this->assertNotNull($loggedMessage, 'Log::error should have been called');
        $this->assertStringContainsString('SendFcmNotificationJob', $loggedMessage);
        $this->assertStringContainsString('فشل', $loggedMessage);
        $this->assertSame($exception->getMessage(), $loggedContext['exception']);
        $this->assertSame($title, $loggedContext['title']);
        $this->assertArrayHasKey('tokens_count', $loggedContext);
        $this->assertArrayHasKey('timestamp', $loggedContext);
    }

    /**
     * The failed() method logs the correct tokens count.
     *
     * Validates: Requirements 8.4
     */
    public function test_failed_method_logs_correct_tokens_count(): void
    {
        $loggedContext = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use (&$loggedContext) {
                $loggedContext = $context;

                return true;
            });

        $tokens = ['t1', 't2', 't3', 't4', 't5'];
        $job    = new SendFcmNotificationJob(
            tokens: $tokens,
            title: 'Title',
            body: 'Body',
        );

        $job->failed(new RuntimeException('error'));

        $this->assertSame(\count($tokens), $loggedContext['tokens_count']);
    }

    /**
     * The job is dispatched to the queue with tries = 3, confirming it will be
     * recorded in failed_jobs after exhausting all retry attempts.
     *
     * Laravel automatically records a job in failed_jobs when all retry attempts
     * are exhausted. This test verifies the job is queued with the correct
     * configuration ($tries = 3) that triggers this behavior.
     *
     * Validates: Requirements 8.4
     */
    public function test_job_is_dispatched_to_queue_with_three_tries(): void
    {
        Queue::fake();

        $tokens = ['token1', 'token2'];
        $title  = 'Test';
        $body   = 'Body';

        SendFcmNotificationJob::dispatch($tokens, $title, $body);

        Queue::assertPushed(SendFcmNotificationJob::class, fn (SendFcmNotificationJob $job) => $job->tokens === $tokens
            && $job->title                                                                                  === $title
            && $job->body                                                                                   === $body
            && $job->tries                                                                                  === 3);
    }

    /**
     * The failed() method receives and logs the exception message that caused the final failure.
     *
     * Validates: Requirements 8.4
     */
    public function test_failed_method_logs_the_exception_message(): void
    {
        $loggedContext = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use (&$loggedContext) {
                $loggedContext = $context;

                return true;
            });

        $exception = new RuntimeException('FCM service unavailable after 3 attempts');

        $job = new SendFcmNotificationJob(
            tokens: ['token1'],
            title: 'Title',
            body: 'Body',
        );

        $job->failed($exception);

        $this->assertSame($exception->getMessage(), $loggedContext['exception']);
    }

    /**
     * The handle() method delegates to PushNotificationService::sendMulticast().
     *
     * Validates: Requirements 8.1, 8.3
     */
    public function test_handle_calls_push_service_send_multicast(): void
    {
        $tokens = ['token1', 'token2'];
        $title  = 'Title';
        $body   = 'Body';
        $data   = ['key' => 'value'];

        $pushServiceMock = Mockery::mock(PushNotificationService::class);
        $pushServiceMock
            ->shouldReceive('sendMulticast')
            ->once()
            ->with($tokens, $title, $body, $data);

        $job = new SendFcmNotificationJob(
            tokens: $tokens,
            title: $title,
            body: $body,
            data: $data,
        );

        $job->handle($pushServiceMock);

        $this->addToAssertionCount(1); // Mockery expectation verified on tearDown
    }

    /**
     * The handle() method skips sending when tokens list is empty.
     *
     * Validates: Requirements 8.1
     */
    public function test_handle_skips_sending_when_tokens_are_empty(): void
    {
        $pushServiceMock = Mockery::mock(PushNotificationService::class);
        $pushServiceMock->shouldNotReceive('sendMulticast');

        $job = new SendFcmNotificationJob(
            tokens: [],
            title: 'Title',
            body: 'Body',
        );

        $job->handle($pushServiceMock);

        $this->addToAssertionCount(1); // Mockery expectation verified on tearDown
    }
}
