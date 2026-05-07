<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;

#[Layout('web-app.layouts.app')]
class ScheduledVisitsPage extends PlaceholderPage
{
    public function mount(string $section = 'scheduled-visits'): void
    {
        $this->section = 'scheduled-visits';
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $baseQuery = $this->scheduledVisitsQuery($user);
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
            'servantOptions' => $this->servantOptions($user),
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

    private function scheduledVisitsQuery(User $user): Builder
    {
        return WebAppScope::scheduledVisits($user);
    }

    private function applySearch(Builder $query): Builder
    {
        if ($this->search === '') {
            return $query;
        }

        $term = '%' . $this->search . '%';

        return $query->where(fn (Builder $builder) => $builder
            ->where('notes', 'like', $term)
            ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term))
            ->orWhereHas('servants', fn (Builder $servant) => $servant->where('name', 'like', $term))
            ->orWhereHas('assignedServant', fn (Builder $servant) => $servant->where('name', 'like', $term)));
    }

    private function applyFilter(Builder $query): Builder
    {
        return match ($this->filter) {
            'upcoming' => $query->where('scheduled_date', '>=', now()->toDateString())->where('status', 'pending'),
            'completed' => $query->where('status', 'completed'),
            'past' => $query->where('scheduled_date', '<', now()->toDateString()),
            default => $query,
        };
    }

    private function applySort(Builder $query): Builder
    {
        return $query->orderBy('scheduled_date')->orderBy('scheduled_time');
    }

    private function meta(): array
    {
        return [
            'title' => 'الزيارات المجدولة',
            'description' => 'ترتيب المواعيد القادمة مع رؤية واضحة للمكلفين والحالات المكتملة أو المؤجلة.',
            'icon' => 'ph-calendar-check',
            'primaryAction' => ['label' => 'الزيارات', 'route' => route('app.visits'), 'icon' => 'ph-clipboard-text'],
            'secondaryAction' => ['label' => 'طلبات الصلاة', 'route' => route('app.prayer-requests'), 'icon' => 'ph-hands-praying'],
        ];
    }

    private function filters(): array
    {
        return [
            ['value' => 'all', 'label' => 'الكل'],
            ['value' => 'upcoming', 'label' => 'قادمة'],
            ['value' => 'completed', 'label' => 'مكتملة'],
            ['value' => 'past', 'label' => 'سابقة'],
        ];
    }

    private function stats(Builder $baseQuery): array
    {
        return [
            ['label' => 'قادمة', 'value' => (clone $baseQuery)->where('scheduled_date', '>=', now()->toDateString())->where('status', 'pending')->count(), 'tone' => 'blue'],
            ['label' => 'اليوم', 'value' => (clone $baseQuery)->whereDate('scheduled_date', now()->toDateString())->count(), 'tone' => 'emerald'],
            ['label' => 'مكتملة', 'value' => (clone $baseQuery)->where('status', 'completed')->count(), 'tone' => 'amber'],
        ];
    }

    private function beneficiaryOptions(User $user): Collection
    {
        return WebAppScope::beneficiaries($user)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'code']);
    }

    private function servantOptions(User $user): Collection
    {
        $query = WebAppScope::users($user)
            ->where('role', UserRole::Servant)
            ->orderBy('name');

        if ($this->scheduledVisitBeneficiaryId !== null) {
            $beneficiary = WebAppScope::beneficiaries($user)
                ->whereKey($this->scheduledVisitBeneficiaryId)
                ->first();

            if (! $beneficiary) {
                return collect();
            }

            $query->where('service_group_id', $beneficiary->service_group_id);
        }

        return $query->get(['id', 'name', 'service_group_id']);
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
