<?php

namespace App\Services;

use App\Models\PushDevice;
use App\Models\User;
use Illuminate\Http\Request;

class PushDeviceSessionService
{
    private const SESSION_KEY = 'push_device_token_hash';

    public function rememberCurrent(Request $request, string $tokenHash): void
    {
        $request->session()->put(self::SESSION_KEY, $tokenHash);
    }

    public function revokeCurrent(User $user, Request $request): void
    {
        $tokenHash = $request->session()->pull(self::SESSION_KEY);

        if (! is_string($tokenHash) || strlen($tokenHash) !== 64) {
            return;
        }

        PushDevice::query()
            ->where('user_id', $user->id)
            ->where('token_hash', $tokenHash)
            ->delete();

        // Remove the transitional legacy token only when it represents this
        // browser session. Other devices remain registered in push_devices.
        if (is_string($user->fcm_token)
            && hash_equals($tokenHash, hash('sha256', $user->fcm_token))) {
            $user->forceFill(['fcm_token' => null])->saveQuietly();
        }
    }
}
