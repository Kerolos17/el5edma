<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\MedicalFile;
use Illuminate\Support\Facades\Auth;

class MedicalFileObserver
{
    private array $excluded = ['updated_at'];

    public function created(MedicalFile $file): void
    {
        $this->log($file, 'created', null, collect($file->getAttributes())->except($this->excluded)->toArray());
    }

    public function deleted(MedicalFile $file): void
    {
        $this->log($file, 'deleted', collect($file->getOriginal())->except($this->excluded)->toArray(), null);
    }

    private function log($model, string $action, ?array $old, ?array $new): void
    {
        if (! Auth::check()) {
            return;
        }

        AuditLog::create([
            'user_id'    => Auth::id(),
            'model_type' => get_class($model),
            'model_id'   => $model->id,
            'action'     => $action,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
        ]);
    }
}
