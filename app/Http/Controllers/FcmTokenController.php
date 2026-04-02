<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FcmTokenController extends Controller
{
    /**
     * Store or update the user's FCM token.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        
        if ($user) {
            $user->update([
                'fcm_token' => $request->input('fcm_token')
            ]);
            
            return response()->json(['message' => 'Token updated successfully']);
        }

        return response()->json(['message' => 'Unauthenticated'], 401);
    }
}
