<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;

#[Layout('web-app.layouts.app')]
class ServiceGroupsPage extends PlaceholderPage
{
    public function mount(string $section = 'service-groups'): void
    {
        abort_unless(auth()->user()->can('viewAny', ServiceGroup::class), 403);

        $this->section = 'service-groups';
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $baseQuery = $this->serviceGroupsQuery($user);
        $records = $this->applySort(
            $this->applyFilter(
                $this->applySearch(clone $baseQuery),
            )
        )->paginate(12);

        return view('livewire.web-app.placeholder-page', [
            'meta' => $this->meta(),
            'filters' => $this->filters(),
            'stats' => $this->stats(clone $baseQuery),
            'records' => $records,
            'reportCards' => collect(),
            'beneficiaryOptions' => collect(),
            'servantOptions' => collect(),
            'userRoleOptions' => collect(),
            'userServiceGroupOptions' => collect(),
            'serviceGroupLeaderOptions' => $this->serviceGroupLeaderOptions($user),
            'serviceGroupServiceLeaderOptions' => $this->serviceGroupServiceLeaderOptions($user),
            'beneficiaryServiceGroupOptions' => collect(),
            'beneficiaryServantOptions' => collect(),
            'beneficiaryRecordStatusOptions' => [],
            'medicalFileTypeOptions' => [],
            'visitTypeOptions' => [],
            'beneficiaryStatusOptions' => [],
        ]);
    }

    private function serviceGroupsQuery(User $user): Builder
    {
        return WebAppScope::serviceGroups($user);
    }

    private function applySearch(Builder $query): Builder
    {
        if ($this->search === '') {
            return $query;
        }

        $term = '%' . $this->search . '%';

        return $query->where(fn (Builder $builder) => $builder
            ->where('name', 'like', $term)
            ->orWhere('description', 'like', $term));
    }

    private function applyFilter(Builder $query): Builder
    {
        return match ($this->filter) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default => $query,
        };
    }

    private function applySort(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    private function meta(): array
    {
        return [
            'title' => 'مجموعات الخدمة',
            'description' => 'عرض مجموعات الخدمة داخل نطاقك مع عدد المخدومين والخدام والمسؤولين عن كل مجموعة.',
            'icon' => 'ph-tree-structure',
            'primaryAction' => ['label' => 'الخدام', 'route' => route('app.users'), 'icon' => 'ph-identification-card'],
            'secondaryAction' => ['label' => 'التقارير', 'route' => route('app.reports'), 'icon' => 'ph-chart-line-up'],
        ];
    }

    private function filters(): array
    {
        return [
            ['value' => 'all', 'label' => 'الكل'],
            ['value' => 'active', 'label' => 'نشطة'],
            ['value' => 'inactive', 'label' => 'غير نشطة'],
        ];
    }

    private function stats(Builder $baseQuery): array
    {
        return [
            ['label' => 'المجموعات', 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
            ['label' => 'نشطة', 'value' => (clone $baseQuery)->where('is_active', true)->count(), 'tone' => 'emerald'],
            ['label' => 'غير نشطة', 'value' => (clone $baseQuery)->where('is_active', false)->count(), 'tone' => 'amber'],
        ];
    }

    private function serviceGroupLeaderOptions(User $actor): Collection
    {
        $query = User::query()
            ->where('role', UserRole::FamilyLeader)
            ->where('is_active', true)
            ->orderBy('name');

        if ($actor->role === UserRole::ServiceLeader) {
            $query->whereIn('service_group_id', $actor->managedServiceGroupIds());
        } elseif ($actor->role === UserRole::FamilyLeader) {
            $query->whereKey($actor->id);
        } elseif ($actor->role !== UserRole::SuperAdmin) {
            $query->whereRaw('1 = 0');
        }

        return $query->pluck('name', 'id');
    }

    private function serviceGroupServiceLeaderOptions(User $actor): Collection
    {
        if ($actor->isServiceLeader()) {
            return collect([$actor->id => $actor->name]);
        }

        if ($actor->role !== UserRole::SuperAdmin) {
            return collect();
        }

        return User::query()
            ->where('role', UserRole::ServiceLeader)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
