<?php

declare(strict_types=1);

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * A minimal broadcaster for use in automated tests.
 * It enforces authentication and channel authorization without
 * requiring real Pusher/Reverb credentials.
 */
class TestBroadcaster extends Broadcaster
{
    /**
     * Authenticate the incoming request for a given channel.
     *
     * Returns 401 for unauthenticated requests and 403 for unauthorized channel access.
     *
     * @throws UnauthorizedHttpException
     * @throws AccessDeniedHttpException
     */
    public function auth($request): mixed
    {
        $channelName = $this->normalizeChannelName($request->channel_name ?? '');

        if (! $this->isGuardedChannel($request->channel_name ?? '')) {
            return $this->validAuthenticationResponse($request, true);
        }

        if (! $this->retrieveUser($request, $channelName)) {
            throw new UnauthorizedHttpException('', 'Unauthenticated.');
        }

        return $this->verifyUserCanAccessChannel($request, $channelName);
    }

    /**
     * Return a minimal valid auth response for tests.
     */
    public function validAuthenticationResponse($request, $result): array
    {
        return ['auth' => 'test:signature'];
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        // No-op in tests.
    }

    /**
     * Determine if the channel is a guarded (private/presence) channel.
     */
    protected function isGuardedChannel(string $channelName): bool
    {
        return str_starts_with($channelName, 'private-')
            || str_starts_with($channelName, 'presence-');
    }

    /**
     * Normalize a channel name by removing the type prefix used on the client side.
     */
    protected function normalizeChannelName(string $channelName): string
    {
        foreach (['private-encrypted-', 'private-', 'presence-'] as $prefix) {
            if (str_starts_with($channelName, $prefix)) {
                return substr($channelName, strlen($prefix));
            }
        }

        return $channelName;
    }
}
