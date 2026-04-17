<?php

// Feature: notifications-optimization, Property 7: حذف token غير الصالح تلقائياً

namespace Tests\Unit;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\MessagingError;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use Tests\TestCase;

/**
 * Property 7: حذف token غير الصالح تلقائياً
 *
 * Validates: Requirements 4.3
 *
 * For any user with an fcm_token, when Firebase returns UNREGISTERED or
 * INVALID_ARGUMENT for that token, the user's fcm_token must become null
 * in the database. Users with valid tokens must NOT be affected.
 */
class PushNotificationInvalidTokenPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Property 7: لأي مجموعة من المستخدمين يملكون fcm_token،
     * عند إرجاع Firebase استجابة UNREGISTERED أو INVALID_ARGUMENT لبعض الـ tokens،
     * يجب أن يصبح fcm_token = null للمستخدمين المتأثرين فقط،
     * بينما يبقى fcm_token سليماً للمستخدمين الآخرين.
     *
     * Validates: Requirements 4.3
     *
     * 100 iterations with random user counts and random invalid token subsets.
     */
    public function test_invalid_tokens_are_nulled_in_db_after_firebase_unregistered_or_invalid_argument(): void
    {
        // Feature: notifications-optimization, Property 7: حذف token غير الصالح تلقائياً

        mt_srand(77777);

        for ($i = 0; $i < 100; $i++) {
            // Generate between 2 and 20 users each with a unique fcm_token
            $totalUsers   = mt_rand(2, 20);
            $invalidCount = mt_rand(1, $totalUsers);

            $users  = [];
            $tokens = [];

            for ($j = 0; $j < $totalUsers; $j++) {
                $token = "prop7_token_{$i}_{$j}_" . uniqid();
                $user  = User::factory()->create([
                    'fcm_token' => $token,
                    'email'     => "prop7_{$i}_{$j}@example.com",
                ]);
                $users[]  = $user;
                $tokens[] = $token;
            }

            // First $invalidCount tokens are invalid; the rest are valid
            $invalidTokens = array_slice($tokens, 0, $invalidCount);
            $validTokens   = array_slice($tokens, $invalidCount);

            // Build Firebase report: alternate UNREGISTERED / INVALID_ARGUMENT for invalid tokens
            $items = [];
            foreach ($invalidTokens as $k => $token) {
                $errorCode = ($k % 2 === 0) ? 'UNREGISTERED' : 'INVALID_ARGUMENT';
                $target    = MessageTarget::with(MessageTarget::TOKEN, $token);
                $error     = new MessagingError($errorCode, 0);
                $items[]   = SendReport::failure($target, $error);
            }
            foreach ($validTokens as $token) {
                $target  = MessageTarget::with(MessageTarget::TOKEN, $token);
                $items[] = SendReport::success($target, ['name' => 'projects/test/messages/ok']);
            }

            $report = MulticastSendReport::withItems($items);

            $messagingMock = Mockery::mock(Messaging::class);
            $messagingMock
                ->shouldReceive('sendMulticast')
                ->once()
                ->andReturn($report);

            $service = new PushNotificationService($messagingMock);
            $service->sendMulticast($tokens, 'Title', 'Body');

            $userIds = collect($users)->pluck('id')->toArray();

            // Assert: exactly $invalidCount users now have fcm_token = null
            $nullCount = User::whereIn('id', $userIds)->whereNull('fcm_token')->count();
            $this->assertSame(
                $invalidCount,
                $nullCount,
                "Iteration {$i}: expected {$invalidCount} users with null fcm_token, got {$nullCount}",
            );

            // Assert: valid token users still have their tokens intact
            $validUsers = array_slice($users, $invalidCount);
            foreach ($validUsers as $idx => $user) {
                $expectedToken = $tokens[$invalidCount + $idx];
                $this->assertDatabaseHas('users', [
                    'id'        => $user->id,
                    'fcm_token' => $expectedToken,
                ]);
            }

            $messagingMock->mockery_verify();
            Mockery::resetContainer();

            // Clean up users created in this iteration
            User::whereIn('id', $userIds)->delete();
        }
    }

    /**
     * Edge case: UNREGISTERED error alone nulls the token
     *
     * Validates: Requirements 4.3
     */
    public function test_unregistered_error_nulls_fcm_token(): void
    {
        $token = 'unregistered_token_' . uniqid();
        $user  = User::factory()->create(['fcm_token' => $token]);

        $target = MessageTarget::with(MessageTarget::TOKEN, $token);
        $error  = new MessagingError('UNREGISTERED', 0);
        $report = MulticastSendReport::withItems([SendReport::failure($target, $error)]);

        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock->shouldReceive('sendMulticast')->once()->andReturn($report);

        $service = new PushNotificationService($messagingMock);
        $service->sendMulticast([$token], 'T', 'B');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'fcm_token' => null]);
    }

    /**
     * Edge case: INVALID_ARGUMENT error alone nulls the token
     *
     * Validates: Requirements 4.3
     */
    public function test_invalid_argument_error_nulls_fcm_token(): void
    {
        $token = 'invalid_arg_token_' . uniqid();
        $user  = User::factory()->create(['fcm_token' => $token]);

        $target = MessageTarget::with(MessageTarget::TOKEN, $token);
        $error  = new MessagingError('INVALID_ARGUMENT', 0);
        $report = MulticastSendReport::withItems([SendReport::failure($target, $error)]);

        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock->shouldReceive('sendMulticast')->once()->andReturn($report);

        $service = new PushNotificationService($messagingMock);
        $service->sendMulticast([$token], 'T', 'B');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'fcm_token' => null]);
    }

    /**
     * Edge case: non-invalid error (e.g. INTERNAL) does NOT null the token
     *
     * Validates: Requirements 4.3
     */
    public function test_non_invalid_error_does_not_null_fcm_token(): void
    {
        $token = 'internal_error_token_' . uniqid();
        $user  = User::factory()->create(['fcm_token' => $token]);

        $target = MessageTarget::with(MessageTarget::TOKEN, $token);
        $error  = new MessagingError('INTERNAL', 0);
        $report = MulticastSendReport::withItems([SendReport::failure($target, $error)]);

        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock->shouldReceive('sendMulticast')->once()->andReturn($report);

        $service = new PushNotificationService($messagingMock);
        $service->sendMulticast([$token], 'T', 'B');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'fcm_token' => $token]);
    }
}
