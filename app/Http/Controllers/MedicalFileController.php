<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\MedicalFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MedicalFileController extends Controller
{
    public function download(MedicalFile $medicalFile)
    {
        // تحقق من الصلاحية
        $user = Auth::user();

        if ($user->role === UserRole::Servant) {
            $beneficiary = $medicalFile->beneficiary;
            if ($beneficiary->assigned_servant_id !== $user->id) {
                abort(403);
            }
        }

        if ($user->role === UserRole::FamilyLeader) {
            $beneficiary = $medicalFile->beneficiary;
            if ($beneficiary->service_group_id !== $user->service_group_id) {
                abort(403);
            }
        }

        // تحقق من وجود الملف
        if (! Storage::disk('private')->exists($medicalFile->file_path)) {
            abort(404, app()->getLocale() === 'ar' ? 'الملف غير موجود' : 'File not found');
        }

        return Storage::disk('private')->download(
            $medicalFile->file_path,
            $medicalFile->title . '.' . pathinfo($medicalFile->file_path, PATHINFO_EXTENSION),
        );
    }
}
