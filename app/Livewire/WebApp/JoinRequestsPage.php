<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Enums\UserRole;
use App\Models\JoinRequest;
use App\Policies\JoinRequestPolicy;
use App\Services\JoinRequestReviewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * صفحة مراجعة طلبات الانضمام: لا يراها إلا المسؤولون المخولون، والنطاق
 * يُفرض من الخادم (مدير النظام = الكل، أمين الخدمة = مجموعاته المدارة،
 * أمين الأسرة = أسرته فقط). كل قرار يمر عبر JoinRequestReviewService.
 */
#[Layout('web-app.layouts.app')]
#[Title('طلبات الانضمام')]
class JoinRequestsPage extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'open')]
    public string $filter = 'open';

    /** Review modal state. */
    public ?int $reviewingId = null;

    public string $reviewAction = 'approve';

    public string $reviewRole = 'servant';

    public string $reviewGroupId = '';

    public string $reviewNote = '';

    public function render(): View
    {
        $user = auth()->user();
        abort_unless($user && $user->can('viewAny', JoinRequest::class), 403);

        $query = $this->scopedQuery($user);
        $stats = $this->scopedStats($user);

        return view('livewire.web-app.join-requests-page', [
            'records'      => $query->paginate(12),
            'stats'        => $stats,
            'roleOptions'  => app(JoinRequestPolicy::class)->assignableRoles($user),
            'groupOptions' => $user->managedServiceGroups()->sortBy('name')->values(),
        ]);
    }

    public function openReview(int $id, string $action): void
    {
        $user        = auth()->user();
        $joinRequest = JoinRequest::query()->with('user')->findOrFail($id);

        abort_unless($user->can('review', $joinRequest), 403);
        abort_unless(in_array($action, ['approve', 'reject', 'changes'], true), 403);

        $this->reviewingId  = $joinRequest->id;
        $this->reviewAction = $action;
        $this->reviewNote   = '';

        $assignable       = app(JoinRequestPolicy::class)->assignableRoles($user);
        $this->reviewRole = in_array($joinRequest->desired_role, $assignable, true)
            ? $joinRequest->desired_role
            : ($assignable[0] ?? 'servant');

        $managedIds          = $user->managedServiceGroupIds();
        $this->reviewGroupId = (string) (in_array($joinRequest->service_group_id, $managedIds, true)
            ? $joinRequest->service_group_id
            : ($managedIds[0] ?? ''));
    }

    public function closeReview(): void
    {
        $this->reviewingId  = null;
        $this->reviewAction = 'approve';
        $this->reviewNote   = '';
        $this->resetErrorBag();
    }

    public function submitReview(): void
    {
        $user        = auth()->user();
        $joinRequest = JoinRequest::query()->findOrFail($this->reviewingId);
        $service     = app(JoinRequestReviewService::class);

        match ($this->reviewAction) {
            'approve' => $service->approve(
                $joinRequest,
                $user,
                $this->reviewRole,
                (int) $this->reviewGroupId,
            ),
            'reject'  => $service->reject($joinRequest, $user, trim($this->reviewNote)),
            'changes' => $service->requestChanges($joinRequest, $user, trim($this->reviewNote)),
            default   => abort(403),
        };

        $this->dispatch('toast', message: __('join_requests.toasts.decision_saved'), type: 'success');
        $this->closeReview();
    }

    public function toggleSuspension(int $id): void
    {
        $user        = auth()->user();
        $joinRequest = JoinRequest::query()->findOrFail($id);

        abort_unless($joinRequest->status === JoinRequest::STATUS_APPROVED, 403);

        $service = app(JoinRequestReviewService::class);

        if ($joinRequest->user->is_active) {
            $service->suspend($joinRequest->user, $user);
            $this->dispatch('toast', message: __('join_requests.toasts.member_suspended'), type: 'success');
        } else {
            $service->reactivate($joinRequest->user, $user);
            $this->dispatch('toast', message: __('join_requests.toasts.member_reactivated'), type: 'success');
        }
    }

    // ── Internals ──

    private function scopedQuery($user): Builder
    {
        $query = JoinRequest::query()
            ->with(['user', 'serviceGroup', 'reviewer'])
            ->orderByDesc('created_at');

        if ($user->role !== UserRole::SuperAdmin) {
            // ServiceLeader → their managed groups; FamilyLeader → own group only.
            $ids = $user->role === UserRole::ServiceLeader
                ? $user->managedServiceGroupIds()
                : [$user->service_group_id];

            $query->whereIn('service_group_id', $ids);
        }

        if ($this->filter === 'open') {
            $query->open();
        } elseif ($this->filter === 'suspended') {
            $query->where('status', JoinRequest::STATUS_APPROVED)
                ->whereHas('user', fn (Builder $q) => $q->where('is_active', false));
        } elseif (in_array($this->filter, ['approved', 'rejected'], true)) {
            $query->where('status', $this->filter);
            if ($this->filter === 'approved') {
                $query->whereHas('user', fn (Builder $q) => $q->where('is_active', true));
            }
        }

        if (trim($this->search) !== '') {
            $term = '%' . str_replace('%', '\%', trim($this->search)) . '%';
            $query->whereHas('user', fn (Builder $q) => $q
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term));
        }

        return $query;
    }

    private function scopedStats($user): array
    {
        $base = fn (): Builder => $this->scopeOnly($user);

        return [
            ['label' => __('join_requests.statuses.pending'), 'value' => (clone $base())->open()->count()],
            ['label' => __('join_requests.statuses.approved'), 'value' => (clone $base())->where('status', JoinRequest::STATUS_APPROVED)->whereHas('user', fn ($q) => $q->where('is_active', true))->count()],
            ['label' => __('join_requests.statuses.rejected'), 'value' => (clone $base())->where('status', JoinRequest::STATUS_REJECTED)->count()],
            ['label' => __('join_requests.filters.suspended'), 'value' => (clone $base())->where('status', JoinRequest::STATUS_APPROVED)->whereHas('user', fn ($q) => $q->where('is_active', false))->count()],
        ];
    }

    private function scopeOnly($user): Builder
    {
        $query = JoinRequest::query();

        if ($user->role !== UserRole::SuperAdmin) {
            $ids = $user->role === UserRole::ServiceLeader
                ? $user->managedServiceGroupIds()
                : [$user->service_group_id];

            $query->whereIn('service_group_id', $ids);
        }

        return $query;
    }
}
