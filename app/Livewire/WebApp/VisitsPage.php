<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;

#[Layout('web-app.layouts.app')]
class VisitsPage extends PlaceholderPage
{
    public function mount(string $section = 'visits'): void
    {
        $this->section = 'visits';
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $baseQuery = $this->visitsQuery($user);
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
            'beneficiaryOptions' => $this->beneficiaryOptions($user),
            'servantOptions' => collect(),
            'userRoleOptions' => collect(),
            'userServiceGroupOptions' => collect(),
            'serviceGroupLeaderOptions' => collect(),
            'serviceGroupServiceLeaderOptions' => collect(),
            'beneficiaryServiceGroupOptions' => collect(),
            'beneficiaryServantOptions' => collect(),
            'beneficiaryRecordStatusOptions' => [],
            'medicalFileTypeOptions' => [],
            'visitTypeOptions' => $this->visitTypeOptions(),
            'beneficiaryStatusOptions' => $this->beneficiaryStatusOptions(),
        ]);
    }

    private function visitsQuery(User $user): Builder
    {
        return WebAppScope::visits($user);
    }

    private function applySearch(Builder $query): Builder
    {
        if ($this->search === '') {
            return $query;
        }

        $term = '%' . $this->search . '%';

        return $query->where(fn (Builder $builder) => $builder
            ->where('type', 'like', $term)
            ->orWhere('feedback', 'like', $term)
            ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term)));
    }

    private function applyFilter(Builder $query): Builder
    {
        return match ($this->filter) {
            'month' => $query->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year),
            'critical' => $query->where('is_critical', true)->whereNull('critical_resolved_at'),
            'follow-up' => $query->where(fn (Builder $builder) => $builder->where('needs_family_leader', true)->orWhere('needs_service_leader', true)),
            default => $query,
        };
    }

    private function applySort(Builder $query): Builder
    {
        return $query->latest('visit_date');
    }

    private function meta(): array
    {
        return [
            'title' => 'الزيارات',
            'description' => 'سجل الزيارات اليومية مع إبراز الحالات الحرجة والزيارات التي تحتاج تصعيد أو متابعة أسرع.',
            'icon' => 'ph-clipboard-text',
            'primaryAction' => ['label' => 'المخدومون', 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
            'secondaryAction' => ['label' => 'المجدولة', 'route' => route('app.scheduled-visits'), 'icon' => 'ph-calendar-check'],
        ];
    }

    private function filters(): array
    {
        return [
            ['value' => 'all', 'label' => 'الكل'],
            ['value' => 'month', 'label' => 'هذا الشهر'],
            ['value' => 'critical', 'label' => 'حرجة'],
            ['value' => 'follow-up', 'label' => 'تحتاج متابعة'],
        ];
    }

    private function stats(Builder $baseQuery): array
    {
        return [
            ['label' => 'إجمالي الزيارات', 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
            ['label' => 'هذا الشهر', 'value' => (clone $baseQuery)->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year)->count(), 'tone' => 'emerald'],
            ['label' => 'حرجة مفتوحة', 'value' => (clone $baseQuery)->where('is_critical', true)->whereNull('critical_resolved_at')->count(), 'tone' => 'rose'],
        ];
    }

    private function beneficiaryOptions(User $user): Collection
    {
        return WebAppScope::beneficiaries($user)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'code']);
    }

    private function visitTypeOptions(): array
    {
        return [
            'home_visit' => 'زيارة منزلية',
            'phone_call' => 'مكالمة هاتفية',
            'church_meeting' => 'اجتماع كنيسة',
        ];
    }

    private function beneficiaryStatusOptions(): array
    {
        return [
            'great' => 'ممتاز',
            'good' => 'جيد',
            'needs_follow' => 'يحتاج متابعة',
            'critical' => 'حرج',
        ];
    }
}
