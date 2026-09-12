<?php

namespace App\Observers;

use App\Jobs\SendFcmNotificationJob;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Visit;
use App\Services\InternalNotificationService;
use App\Support\NotificationMetadata;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VisitObserver
{
    private array $excluded = ['updated_at'];

    public function created(Visit $visit): void
    {
        $this->log($visit, 'created', null, $visit->getAttributes());
        $this->invalidateDashboardCache($visit);

        if ($visit->is_critical) {
            $this->sendCriticalCaseNotification($visit);
        }
    }

    public function updated(Visit $visit): void
    {
        $old = collect($visit->getOriginal())->except($this->excluded)->toArray();
        $new = collect($visit->getDirty())->except($this->excluded)->toArray();

        if (! empty($new)) {
            $this->log($visit, 'updated', $old, $new);
            $this->invalidateDashboardCache($visit);
        }

        if ($visit->isDirty('is_critical') && $visit->is_critical) {
            $this->sendCriticalCaseNotification($visit);
        }
    }

    public function deleted(Visit $visit): void
    {
        $this->log($visit, 'deleted', $visit->getOriginal(), null);
        $this->invalidateDashboardCache($visit);
    }

    private function sendCriticalCaseNotification(Visit $visit): void
    {
        $visit->loadMissing('beneficiary.serviceGroup');
        $beneficiary = $visit->beneficiary;

        if (! $beneficiary) {
            return;
        }

        $notifier = app(InternalNotificationService::class);
        $title    = __('notifications.critical_case_title');
        $body     = __('notifications.critical_case_body', ['name' => $beneficiary->full_name]);
        $data     = NotificationMetadata::enrich('critical_case', [
            'beneficiary_id' => $beneficiary->id,
            'visit_id'       => $visit->id,
            'url'            => '/app/visit/' . $visit->id,
        ]);

        $notifier->notifyRelatedUsers($beneficiary, 'critical_case', $title, $body, $data);

        $userIds = collect([
            $beneficiary->assigned_servant_id,
            $beneficiary->serviceGroup?->leader_id,
        ])->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $tokens = User::query()
            ->whereIn('id', $userIds)
            ->where('is_active', true)
            ->with('pushDevices:id,user_id,token')
            ->get(['id', 'fcm_token'])
            ->flatMap(fn (User $user) => $user->pushTokens())
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens !== []) {
            SendFcmNotificationJob::dispatch($tokens, $title, $body, $data);
        }
    }

    private function invalidateDashboardCache(Visit $visit): void
    {
        $userId = $visit->created_by;

        if (! $userId) {
            return;
        }

        Cache::forget("dashboard:stats:{$userId}");
        Cache::forget("dashboard:secondary:{$userId}");
        Cache::forget("dashboard:chart:{$userId}");
        Cache::forget("dashboard:birthdays:{$userId}");
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
