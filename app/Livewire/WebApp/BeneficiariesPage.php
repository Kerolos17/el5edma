<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;

#[Layout('web-app.layouts.app')]
class BeneficiariesPage extends PlaceholderPage
{
    public function mount(string $section = 'beneficiaries'): void
    {
        $this->section = 'beneficiaries';
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $baseQuery = $this->beneficiariesQuery($user);
        $records = $this->applySort(
            $this->applyFilter(
                $this->applySearch(clone $baseQuery),
            )
        )->paginate(12);

        return view('livewire.web-app.beneficiaries-page', [
            'meta' => $this->meta(),
            'filters' => $this->filters(),
            'stats' => $this->stats($user, clone $baseQuery),
            'records' => $records,
            'reportCards' => collect(),
            'beneficiaryOptions' => $this->beneficiaryOptions($user),
            'servantOptions' => collect(),
            'userRoleOptions' => collect(),
            'userServiceGroupOptions' => collect(),
            'serviceGroupLeaderOptions' => collect(),
            'serviceGroupServiceLeaderOptions' => collect(),
            'beneficiaryServiceGroupOptions' => $this->beneficiaryServiceGroupOptions($user),
            'beneficiaryServantOptions' => $this->beneficiaryServantOptions($user),
            'beneficiaryRecordStatusOptions' => $this->beneficiaryRecordStatusOptions(),
            'medicalFileTypeOptions' => $this->medicalFileTypeOptions(),
            'visitTypeOptions' => $this->visitTypeOptions(),
            'beneficiaryStatusOptions' => $this->beneficiaryStatusOptions(),
        ]);
    }

    private function beneficiariesQuery(User $user): Builder
    {
        return WebAppScope::beneficiaries($user)
            ->with(['serviceGroup', 'assignedServant'])
            ->withCount('visits');
    }

    private function applySearch(Builder $query): Builder
    {
        if ($this->search === '') {
            return $query;
        }

        $term = '%' . $this->search . '%';

        return $query->where(fn (Builder $builder) => $builder
            ->where('full_name', 'like', $term)
            ->orWhere('code', 'like', $term)
            ->orWhere('phone', 'like', $term));
    }

    private function applyFilter(Builder $query): Builder
    {
        return match ($this->filter) {
            'mine' => $query->where('assigned_servant_id', auth()->id()),
            'recent' => $query->whereHas('visits', fn (Builder $builder) => $builder->where('visit_date', '>=', now()->subDays(30))),
            'needs-visit' => $query->whereDoesntHave('visits'),
            default => $query,
        };
    }

    private function applySort(Builder $query): Builder
    {
        return $query->orderBy('full_name');
    }

    private function meta(): array
    {
        return [
            'title' => __('web_app.resources.beneficiaries.title'),
            'description' => __('web_app.resources.beneficiaries.description'),
            'icon' => 'ph-users-three',
            'primaryAction' => ['label' => __('web_app.actions.dashboard'), 'route' => route('app.dashboard'), 'icon' => 'ph-squares-four'],
            'secondaryAction' => ['label' => __('web_app.actions.visits'), 'route' => route('app.visits'), 'icon' => 'ph-clipboard-text'],
        ];
    }

    private function filters(): array
    {
        return [
            ['value' => 'all', 'label' => __('web_app.filters.all')],
            ['value' => 'mine', 'label' => __('web_app.filters.mine')],
            ['value' => 'recent', 'label' => __('web_app.filters.recent')],
            ['value' => 'needs-visit', 'label' => __('web_app.filters.needs_visit')],
        ];
    }

    private function stats(User $user, Builder $baseQuery): array
    {
        return [
            ['label' => __('web_app.stats.total_beneficiaries'), 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
            ['label' => __('web_app.stats.direct_assignments'), 'value' => (clone $baseQuery)->where('assigned_servant_id', $user->id)->count(), 'tone' => 'emerald'],
            ['label' => __('web_app.stats.no_visits'), 'value' => (clone $baseQuery)->whereDoesntHave('visits')->count(), 'tone' => 'amber'],
        ];
    }

    private function beneficiaryOptions(User $user): Collection
    {
        return WebAppScope::beneficiaries($user)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'code']);
    }

    private function beneficiaryServiceGroupOptions(User $actor): Collection
    {
        $query = ServiceGroup::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($actor->role === UserRole::ServiceLeader) {
            $query->where('service_leader_id', $actor->id);
        } elseif ($actor->role === UserRole::FamilyLeader) {
            $query->whereKey($actor->service_group_id);
        } elseif ($actor->role !== UserRole::SuperAdmin) {
            $query->whereRaw('1 = 0');
        }

        return $query->pluck('name', 'id');
    }

    private function beneficiaryServantOptions(User $actor): Collection
    {
        if (! $this->beneficiaryServiceGroupId) {
            return collect();
        }

        $serviceGroupId = (int) $this->beneficiaryServiceGroupId;
        $allowedGroupIds = $this->beneficiaryServiceGroupOptions($actor)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! in_array($serviceGroupId, $allowedGroupIds, true)) {
            return collect();
        }

        return User::query()
            ->where('role', UserRole::Servant)
            ->where('is_active', true)
            ->where('service_group_id', $serviceGroupId)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    private function beneficiaryRecordStatusOptions(): array
    {
        return [
            'active' => __('beneficiaries.active'),
            'inactive' => __('beneficiaries.inactive'),
            'moved' => __('beneficiaries.moved'),
            'deceased' => __('beneficiaries.deceased'),
        ];
    }

    private function medicalFileTypeOptions(): array
    {
        return [
            'report' => __('medical.report'),
            'image' => __('medical.image'),
            'document' => __('medical.document'),
        ];
    }

    private function visitTypeOptions(): array
    {
        return [
            'home_visit' => __('visits.home_visit'),
            'phone_call' => __('visits.phone_call'),
            'church_meeting' => __('visits.church_meeting'),
        ];
    }

    private function beneficiaryStatusOptions(): array
    {
        return [
            'great' => __('visits.great'),
            'good' => __('visits.good'),
            'needs_follow' => __('visits.needs_follow'),
            'critical' => __('visits.critical'),
        ];
    }
}
