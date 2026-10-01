<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\JoinRequest;
use App\Models\JoinRequestReview;
use App\Models\User;
use App\Policies\JoinRequestPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * كل قرارات المراجعة الإدارية على طلبات الانضمام تمر من هنا: تحقق صلاحيّ
 * المراجع ونطاقه من الخادم، تعديل الحالة والحساب في معاملة واحدة، تسجيل
 * مراجعة غير قابل للتعديل، وسجل تدقيق، ثم إشعار للمتقدم. لا شيء من هذا
 * يعتمد على مدخلات المتصفح، وفشل الإشعار لا يُبطل القرار.
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

        $saved = DB::transaction(function () use ($joinRequest, $reviewer, $role, $serviceGroupId) {
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

        $this->notifyApplicant(
            $saved->user,
            'approved',
            __('notifications.join_request_decision.approved_title'),
            __('notifications.join_request_decision.approved_body', ['name' => $saved->user->name]),
            route('registration.status'),
            "join_request:{$saved->id}:decision:approved",
        );

        return $saved;
    }

    /** Reject the request with a mandatory reason; the account stays gated. */
    public function reject(JoinRequest $joinRequest, User $reviewer, string $note): JoinRequest
    {
        $this->authorizeOpen($joinRequest, $reviewer);

        if (trim($note) === '') {
            throw ValidationException::withMessages(['reviewNote' => __('join_requests.errors.note_required')]);
        }

        $saved = DB::transaction(function () use ($joinRequest, $reviewer, $note) {
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

        $this->notifyApplicant(
            $saved->user,
            'rejected',
            __('notifications.join_request_decision.rejected_title'),
            __('notifications.join_request_decision.rejected_body', ['name' => $saved->user->name, 'reason' => $note]),
            route('registration.status'),
            "join_request:{$saved->id}:decision:rejected",
        );

        return $saved;
    }

    /** Ask the applicant for corrected/missing data: status → incomplete. */
    public function requestChanges(JoinRequest $joinRequest, User $reviewer, string $note): JoinRequest
    {
        $this->authorizeOpen($joinRequest, $reviewer);

        if (trim($note) === '') {
            throw ValidationException::withMessages(['reviewNote' => __('join_requests.errors.note_required')]);
        }

        $saved = DB::transaction(function () use ($joinRequest, $reviewer, $note) {
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

        $this->notifyApplicant(
            $saved->user,
            'changes',
            __('notifications.join_request_decision.changes_title'),
            __('notifications.join_request_decision.changes_body', ['name' => $saved->user->name, 'note' => $note]),
            route('registration.status'),
            "join_request:{$saved->id}:decision:changes",
        );

        return $saved;
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

        $this->notifyApplicant(
            $member,
            'suspended',
            __('notifications.join_request_decision.suspended_title'),
            __('notifications.join_request_decision.suspended_body'),
            '/',
            "join_request:{$member->joinRequest->id}:decision:suspended",
        );
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

        $this->notifyApplicant(
            $member,
            'reactivated',
            __('notifications.join_request_decision.reactivated_title'),
            __('notifications.join_request_decision.reactivated_body', ['name' => $member->name]),
            route($member->homeRoute()),
            "join_request:{$member->joinRequest->id}:decision:reactivated",
        );
    }

    // ── Internals ──

    /**
     * Notify the applicant about a decision. Runs AFTER the decision is
     * committed and swallows every failure — a notification outage must
     * never invalidate the decision itself. The dedupe key keeps replays
     * (same request, same decision) from notifying twice.
     */
    private function notifyApplicant(User $applicant, string $subKey, string $title, string $body, string $url, string $dedupeKey): void
    {
        try {
            $previousLocale = app()->getLocale();
            app()->setLocale($applicant->locale ?? 'ar');

            try {
                app(InternalNotificationService::class)->notifyUser(
                    $applicant,
                    'join_request_decision',
                    $title,
                    $body,
                    ['url' => $url, 'join_request_subkey' => $subKey],
                    $dedupeKey,
                );
            } finally {
                app()->setLocale($previousLocale);
            }
        } catch (Throwable $e) {
            Log::warning('Join-request decision notification failed', [
                'join_request_id' => $applicant->joinRequest?->id,
                'user_id'         => $applicant->id,
                'sub_key'         => $subKey,
                'error'           => $e->getMessage(),
            ]);
        }
    }

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
