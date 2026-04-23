<?php

namespace Tests\Unit;

use App\Services\PushNotificationService;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Mockery;
use Tests\TestCase;

class PushNotificationServiceConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_critical_payload_is_serialized_with_high_priority_configs(): void
    {
        $messaging = Mockery::mock(Messaging::class);
        $messaging
            ->shouldReceive('sendMulticast')
            ->once()
            ->withArgs(function ($message, array $tokens): bool {
                $encoded = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                return $tokens === ['token-1']
                    && str_contains($encoded, '"Urgency":"high"')
                    && str_contains($encoded, '"channel_id":"critical-alerts"')
                    && str_contains($encoded, '"requireInteraction":true');
            })
            ->andReturn(MulticastSendReport::withItems([]));

        $service = new PushNotificationService($messaging);

        $service->sendMulticast(['token-1'], 'Critical Alert', 'Body', [
            'type'     => 'critical_case',
            'severity' => 'critical',
            'url'      => '/admin/visits/1',
        ]);

        $this->addToAssertionCount(1);
    }
}
