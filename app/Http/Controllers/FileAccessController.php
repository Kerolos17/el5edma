<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MedicalFile;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class FileAccessController extends Controller
{
    public function show(string $path): HttpResponse
    {
        $decodedPath = base64_decode($path, true);

        // Reject invalid base64 or path traversal attempts.
        if ($decodedPath === false
            || str_contains($decodedPath, '..')
            || str_starts_with($decodedPath, '/')
            || str_starts_with($decodedPath, '\\')
        ) {
            abort(403);
        }

        // Normalize and ensure the resolved path stays within the private disk root.
        $normalizedPath = str_replace('\\', '/', $decodedPath);
        $realBase       = realpath(Storage::disk('private')->path(''));

        if ($realBase === false) {
            abort(500); // Private disk root is misconfigured
        }

        $realFile = realpath(Storage::disk('private')->path($normalizedPath));

        if ($realFile === false || ! str_starts_with($realFile, $realBase)) {
            abort(403);
        }

        // Resolve the file record and authorize via policy.
        $medicalFile = MedicalFile::where('file_path', $normalizedPath)
            ->with('beneficiary')
            ->firstOrFail();

        Gate::authorize('view', $medicalFile);

        if (! Storage::disk('private')->exists($normalizedPath)) {
            abort(404);
        }

        // Record audit trail: every access to a private medical file is logged.
        AuditLog::create([
            'user_id'    => Auth::id(),
            'model_type' => MedicalFile::class,
            'model_id'   => $medicalFile->id,
            'action'     => 'accessed',
            'old_values' => null,
            'new_values' => [
                'file_path'      => $normalizedPath,
                'beneficiary_id' => $medicalFile->beneficiary_id,
                'title'          => $medicalFile->title,
            ],
            'ip_address' => request()->ip(),
        ]);

        $file = Storage::disk('private')->get($normalizedPath);
        $type = Storage::disk('private')->mimeType($normalizedPath);

        return Response::make($file, 200)->header('Content-Type', $type);
    }
}
