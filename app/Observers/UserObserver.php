<?php
namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    // لا نسجّل هذه الحقول أبداً
    private array $excluded = [
        'password', 'personal_code', 'fcm_token',
        'remember_token', 'updated_at', 'last_login_at',
    ];

    public function created(User $user): void
    {
        $data = collect($user->getAttributes())->except($this->excluded)->toArray();
        $this->log($user, 'created', null, $data);
    }

    public function updated(User $user): void
    {
        $dirty = collect($user->getDirty())->except($this->excluded)->toArray();

        if (empty($dirty)) {
            return;
        }

        $old = collect($user->getOriginal())->only(array_keys($dirty))->toArray();
        $this->log($user, 'updated', $old, $dirty);

        CacheService::invalidateUserCaches();
    }

    public function deleted(User $user): void
    {
        $data = collect($user->getOriginal())->except($this->excluded)->toArray();
        $this->log($user, 'deleted', $data, null);

        CacheService::invalidateUserCaches();
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
