<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\JoinRequest;
use App\Models\JoinRequestReview;
use App\Models\User;
use App\Policies\JoinRequestPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * كل قرارات المراجعة الإدارية على طلبات الانضمام تمر من هنا: تحقق صلاحيّ
 * المراجع ونطاقه من الخادم، تعديل الحالة والحساب في معاملة واحدة، تسجيل
 * مراجعة غير قابل للتعديل، وسجل تدقيق. لا شيء من هذا يعتمد على مدخلات
 * المتصفح.
 */
class JoinRequestReviewService
{
    /**
     * Approve the request: grants the role + group chosen by the reviewer
     * (within their span) and activates the account.
     */
    public function approve(JoinRequest $joinRequest, User $reviewer, string $role, int $serviceGroupId): JoinRequest
    {
        if (! $reviewer->can('review', $joinRequest)) {
            throw ValidationException::withMessages(['review' => __('join_requests.errors.not_allowed')]);
        }

        $assignable = app(JoinRequestPolicy::class)->assignableRoles($reviewer);

        if (! in_array($role, $assignable, true)) {
            throw ValidationException::withMessages(['review_role' => __('join_requests.errors.role_not_assignable')]);
        }

        if (! $reviewer->managesServiceGroup($serviceGroupId)) {
            throw ValidationException::withMessages(['review_group' => __('join_requests.errors.group_out_of_scope')]);
        }

        if (! $joinRequest->isOpen()) {
            throw ValidationException::withMessages(['review' => __('join_requests.errors.already_decided')]);
        }

        return DB::transaction(function () use ($joinRequest, $reviewer, $role, $serviceGroupId) {
            $old = ['role' => $joinRequest->user->role->value, 'service_group_id' => $joinRequest->user->service_group_id, 'is_active' => $joinRequest->user->is_active];

            $joinRequest->user->forceFill([
                'role'             => $role,
                'service_group_id' => $serviceGroupId,
                'is_active'        => true,
                'suspended_at'     => null,
            ])->save();

            $joinRequest->forceFill([
                'status'                 => JoinRequest::STATUS_APPROVED,
                'reviewed_by'            => $reviewer->id,
                'reviewed_at'            => now(),
                'decision_note'          => null,
                'final_role'             => $role,
                'final_service_group_id' => $serviceGroupId,
            ])->save();

            JoinRequestReview::create([
                'join_request_id' => $joinRequest->id,
                'reviewer_id'     => $reviewer->id,
                'action'          => JoinRequest::ACTION_APPROVED,
            ]);

            $this->audit($reviewer, $joinRequest, 'join_request_approved', $old, [
                'role'             => $role,
                'service_group_id' => $serviceGroupId,
                'is_active'        => true,
            ]);

            return $joinRequest->refresh();
        });
    }

    /** Reject the request with a mandatory reason; the account stays gated. */
    public function reject(JoinRequest $joinRequest, User $reviewer, string $note): JoinRequest
    {
        $this->authorizeOpen($joinRequest, $reviewer);

        if (trim($note) === '') {
            throw ValidationException::withMessages(['reviewNote' => __('join_requests.errors.note_required')]);
        }

        return DB::transaction(function () use ($joinRequest, $reviewer, $note) {
            $joinRequest->forceFill([
                'status'        => JoinRequest::STATUS_REJECTED,
                'reviewed_by'   => $reviewer->id,
                'reviewed_at'   => now(),
                'decision_note' => $note,
            ])->save();

            $this->record($joinRequest, $reviewer, JoinRequest::ACTION_REJECTED, $note);
            $this->audit($reviewer, $joinRequest, 'join_request_rejected', ['status' => $joinRequest->getOriginal('status')], ['status' => JoinRequest::STATUS_REJECTED, 'note' => $note]);

            return $joinRequest->refresh();
        });
    }

    /** Ask the applicant for corrected/missing data: status → incomplete. */
    public function requestChanges(JoinRequest $joinRequest, User $reviewer, string $note): JoinRequest
    {
        $this->authorizeOpen($joinRequest, $reviewer);

        if (trim($note) === '') {
            throw ValidationException::withMessages(['reviewNote' => __('join_requests.errors.note_required')]);
        }

        return DB::transaction(function () use ($joinRequest, $reviewer, $note) {
            $joinRequest->forceFill([
                'status'        => JoinRequest::STATUS_INCOMPLETE,
                'reviewed_by'   => $reviewer->id,
                'reviewed_at'   => now(),
                'decision_note' => $note,
            ])->save();

            $this->record($joinRequest, $reviewer, JoinRequest::ACTION_CHANGES_REQUESTED, $note);
            $this->audit($reviewer, $joinRequest, 'join_request_changes_requested', ['status' => $joinRequest->getOriginal('status')], ['status' => JoinRequest::STATUS_INCOMPLETE, 'note' => $note]);

            return $joinRequest->refresh();
        });
    }

    /** Suspend an approved account (server-side state, audited). */
    public function suspend(User $member, User $reviewer, ?string $note = null): void
    {
        $this->authorizeSuspend($member, $reviewer);

        DB::transaction(function () use ($member, $reviewer, $note) {
            $member->forceFill(['is_active' => false, 'suspended_at' => now()])->save();

            $this->record($member->joinRequest, $reviewer, JoinRequest::ACTION_SUSPENDED, $note);
            $this->audit($reviewer, $member->joinRequest, 'account_suspended', ['is_active' => true], ['is_active' => false]);
        });
    }

    /** Reactivate a suspended account (server-side state, audited). */
    public function reactivate(User $member, User $reviewer, ?string $note = null): void
    {
        $this->authorizeSuspend($member, $reviewer);

        DB::transaction(function () use ($member, $reviewer, $note) {
            $member->forceFill(['is_active' => true, 'suspended_at' => null])->save();

            $this->record($member->joinRequest, $reviewer, JoinRequest::ACTION_REACTIVATED, $note);
            $this->audit($reviewer, $member->joinRequest, 'account_reactivated', ['is_active' => false], ['is_active' => true]);
        });
    }

    // ── Internals ──

    private function authorizeOpen(JoinRequest $joinRequest, User $reviewer): void
    {
        if (! $reviewer->can('review', $joinRequest) || ! $joinRequest->isOpen()) {
            throw ValidationException::withMessages(['review' => __('join_requests.errors.not_allowed')]);
        }
    }

    private function authorizeSuspend(User $member, User $reviewer): void
    {
        $joinRequest = $member->joinRequest()->first();

        if (! $joinRequest || ! $reviewer->can('suspend', $joinRequest)) {
            throw ValidationException::withMessages(['review' => __('join_requests.errors.not_allowed')]);
        }
    }

    private function record(JoinRequest $joinRequest, User $reviewer, string $action, ?string $note): void
    {
        JoinRequestReview::create([
            'join_request_id' => $joinRequest->id,
            'reviewer_id'     => $reviewer->id,
            'action'          => $action,
            'note'            => $note,
        ]);
    }

    private function audit(User $reviewer, JoinRequest $joinRequest, string $action, array $old, array $new): void
    {
        AuditLog::create([
            'user_id'    => $reviewer->id,
            'model_type' => JoinRequest::class,
            'model_id'   => $joinRequest->id,
            'action'     => $action,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()?->ip(),
        ]);
    }
}
