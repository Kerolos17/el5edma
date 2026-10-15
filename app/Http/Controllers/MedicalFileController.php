<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MedicalFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MedicalFileController extends Controller
{
    public function download(MedicalFile $medicalFile)
    {
        // Authorize via MedicalFilePolicy (single source of truth)
        $medicalFile->loadMissing('beneficiary');
        Gate::authorize('view', $medicalFile);

        // تحقق من وجود الملف
        if (! Storage::disk('private')->exists($medicalFile->file_path)) {
            abort(404, __('medical.file_not_found'));
        }

        // Record the access only after authorization and the existence check.
        AuditLog::create([
            'user_id'    => Auth::id(),
            'model_type' => MedicalFile::class,
            'model_id'   => $medicalFile->id,
            'action'     => 'accessed',
            'old_values' => null,
            'new_values' => null,
            'ip_address' => request()->ip(),
        ]);

        return Storage::disk('private')->download(
            $medicalFile->file_path,
            $medicalFile->title . '.' . pathinfo($medicalFile->file_path, PATHINFO_EXTENSION),
        );
    }
}
