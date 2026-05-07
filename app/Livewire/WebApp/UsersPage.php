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
class UsersPage extends PlaceholderPage
{
    public function mount(string $section = 'users'): void
    {
        abort_unless(auth()->user()->can('viewAny', User::class), 403);

        $this->section = 'users';
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $baseQuery = $this->usersQuery($user);
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
            'userRoleOptions' => $this->userRoleOptions($user),
            'userServiceGroupOptions' => $this->userServiceGroupOptions($user),
            'serviceGroupLeaderOptions' => collect(),
            'serviceGroupServiceLeaderOptions' => collect(),
            'beneficiaryServiceGroupOptions' => collect(),
            'beneficiaryServantOptions' => collect(),
            'beneficiaryRecordStatusOptions' => [],
            'medicalFileTypeOptions' => [],
            'visitTypeOptions' => [],
            'beneficiaryStatusOptions' => [],
        ]);
    }

    private function usersQuery(User $user): Builder
    {
        return WebAppScope::users($user);
    }

    private function applySearch(Builder $query): Builder
    {
        if ($this->search === '') {
            return $query;
        }

        $term = '%' . $this->search . '%';

        return $query->where(fn (Builder $builder) => $builder
            ->where('name', 'like', $term)
            ->orWhere('email', 'like', $term)
            ->orWhere('phone', 'like', $term));
    }

    private function applyFilter(Builder $query): Builder
    {
        return match ($this->filter) {
            'inactive' => $query->where('is_active', false),
            'active' => $query->where('is_active', true),
            'service_leader', 'family_leader', 'servant', 'super_admin' => $query->where('role', $this->filter),
            default => $query,
        };
    }

    private function applySort(Builder $query): Builder
    {
        return $query->orderByDesc('is_active')->orderBy('name');
    }

    private function meta(): array
    {
        return [
            'title' => 'الخدام والمستخدمون',
            'description' => 'رؤية عملية للمستخدمين داخل نطاقك: من النشط، أدوار الخدمة، ومجموعات الخدمة المرتبطة.',
            'icon' => 'ph-identification-card',
            'primaryAction' => ['label' => 'مجموعات الخدمة', 'route' => route('app.service-groups'), 'icon' => 'ph-tree-structure'],
            'secondaryAction' => ['label' => 'التقارير', 'route' => route('app.reports'), 'icon' => 'ph-chart-line-up'],
        ];
    }

    private function filters(): array
    {
        return [
            ['value' => 'all', 'label' => 'الكل'],
            ['value' => 'active', 'label' => 'نشط'],
            ['value' => 'inactive', 'label' => 'غير مفعل'],
            ['value' => 'service_leader', 'label' => 'أمين خدمة'],
            ['value' => 'family_leader', 'label' => 'أمين أسرة'],
            ['value' => 'servant', 'label' => 'خادم'],
        ];
    }

    private function stats(Builder $baseQuery): array
    {
        return [
            ['label' => 'إجمالي المستخدمين', 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
            ['label' => 'نشطون', 'value' => (clone $baseQuery)->where('is_active', true)->count(), 'tone' => 'emerald'],
            ['label' => 'خدام', 'value' => (clone $baseQuery)->where('role', UserRole::Servant->value)->count(), 'tone' => 'amber'],
        ];
    }

    private function userRoleOptions(User $actor): Collection
    {
        if ($actor->role === UserRole::SuperAdmin) {
            return collect(UserRole::options());
        }

        if ($actor->role === UserRole::ServiceLeader) {
            return collect([
                UserRole::FamilyLeader->value => UserRole::FamilyLeader->label(),
                UserRole::Servant->value => UserRole::Servant->label(),
            ]);
        }

        return collect();
    }

    private function userServiceGroupOptions(User $actor): Collection
    {
        $query = ServiceGroup::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($actor->role === UserRole::ServiceLeader) {
            $query->where('service_leader_id', $actor->id);
        } elseif ($actor->role !== UserRole::SuperAdmin) {
            $query->whereRaw('1 = 0');
        }

        return $query->pluck('name', 'id');
    }
}
