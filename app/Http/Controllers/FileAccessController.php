<?php

namespace App\Http\Controllers;

use App\Models\MedicalFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class FileAccessController extends Controller
{
    public function show($path)
    {
        $decodedPath = base64_decode($path, true);

        // Reject invalid base64 or path traversal attempts
        if ($decodedPath === false
            || str_contains($decodedPath, '..')
            || str_starts_with($decodedPath, '/')
            || str_starts_with($decodedPath, '\\')
        ) {
            abort(403);
        }

        // Normalize and ensure the resolved path stays within the private disk root
        $normalizedPath = str_replace('\\', '/', $decodedPath);
        $realBase = realpath(Storage::disk('private')->path(''));

        if ($realBase === false) {
            abort(500); // Private disk root is misconfigured
        }

        $realFile = realpath(Storage::disk('private')->path($normalizedPath));

        if ($realFile === false || !str_starts_with($realFile, $realBase)) {
            abort(403);
        }

        // Resolve the file record to enforce authorization
        $medicalFile = MedicalFile::where('file_path', $normalizedPath)->firstOrFail();

        $user = Auth::user();

        if ($user->role === 'servant') {
            $beneficiary = $medicalFile->beneficiary;
            if ($beneficiary->assigned_servant_id !== $user->id) {
                abort(403);
            }
        }

        if ($user->role === 'family_leader') {
            $beneficiary = $medicalFile->beneficiary;
            if ($beneficiary->service_group_id !== $user->service_group_id) {
                abort(403);
            }
        }

        if (!Storage::disk('private')->exists($normalizedPath)) {
            abort(404);
        }

        $file = Storage::disk('private')->get($normalizedPath);
        $type = Storage::disk('private')->mimeType($normalizedPath);

        return Response::make($file, 200)->header('Content-Type', $type);
    }
}
