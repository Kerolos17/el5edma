<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class FirebaseWebConfigController extends Controller
{
    /**
     * Return only Firebase's browser-safe identifiers at runtime.
     *
     * Firebase API keys and the Web Push VAPID public key are intentionally
     * public identifiers. Server credentials, especially the service-account
     * JSON, are never part of this response.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json(config('services.firebase_web'), 200, [
            'Cache-Control'          => 'no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
