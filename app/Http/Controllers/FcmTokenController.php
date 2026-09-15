<?php

namespace App\Http\Controllers;

use App\Models\PushDevice;
use App\Models\User;
use App\Services\PushDeviceSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function __construct(private PushDeviceSessionService $deviceSessions)
    {
        //
    }

    /**
     * Store or refresh one push device for the authenticated user.
     *
     * A user may have multiple browser/mobile installations. Tokens are kept in
     * push_devices instead of overwriting a single users.fcm_token value.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token'    => 'required|string|max:2048',
            'platform'     => 'nullable|string|max:32',
            'device_label' => 'nullable|string|max:120',
        ]);

        $token     = $validated['fcm_token'];
        $tokenHash = hash('sha256', $token);
        $user      = $request->user();

        PushDevice::updateOrCreate(
            ['token_hash' => $tokenHash],
            [
                'user_id'      => $user->id,
                'token'        => $token,
                'platform'     => $validated['platform']     ?? 'web',
                'device_label' => $validated['device_label'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        // A browser token may be registered again after a different person
        // signs in on the same device. The push_devices row is reassigned
        // above, and the transitional legacy field must follow it as well;
        // otherwise the prior account could keep receiving notifications on
        // this device until the legacy column is removed.
        User::query()
            ->whereKeyNot($user->id)
            ->where('fcm_token', $token)
            ->update(['fcm_token' => null]);

        $this->deviceSessions->rememberCurrent($request, $tokenHash);

        // Transitional compatibility for older notification commands. This
        // column will be removed after every sender has migrated to pushDevices.
        if ($user->fcm_token !== $token) {
            $user->update(['fcm_token' => $token]);
        }

        return response()->json(['message' => 'Push device registered successfully']);
    }
}
