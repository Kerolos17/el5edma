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
        $decodedPath = base64_decode($path);

        // Resolve the file record to enforce authorization
        $medicalFile = MedicalFile::where('file_path', $decodedPath)->firstOrFail();

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

        if (!Storage::disk('private')->exists($decodedPath)) {
            abort(404);
        }

        $file = Storage::disk('private')->get($decodedPath);
        $type = Storage::disk('private')->mimeType($decodedPath);

        return Response::make($file, 200)->header("Content-Type", $type);
    }
}
