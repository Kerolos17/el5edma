<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;

#[Layout('web-app.layouts.app')]
class PrayerRequestsPage extends PlaceholderPage
{
    public function mount(string $section = 'prayer-requests'): void
    {
        $this->section = 'prayer-requests';
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $baseQuery = $this->prayerRequestsQuery($user);
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
            'visitTypeOptions' => [],
            'beneficiaryStatusOptions' => [],
        ]);
    }

    private function prayerRequestsQuery(User $user): Builder
    {
        return WebAppScope::prayerRequests($user);
    }

    private function applySearch(Builder $query): Builder
    {
        if ($this->search === '') {
            return $query;
        }

        $term = '%' . $this->search . '%';

        return $query->where(fn (Builder $builder) => $builder
            ->where('title', 'like', $term)
            ->orWhere('body', 'like', $term)
            ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term)));
    }

    private function applyFilter(Builder $query): Builder
    {
        return $this->filter === 'all' ? $query : $query->where('status', $this->filter);
    }

    private function applySort(Builder $query): Builder
    {
        return $query->latest();
    }

    private function meta(): array
    {
        return [
            'title' => 'طلبات الصلاة',
            'description' => 'مساحة هادئة لمتابعة الطلبات المفتوحة والمجابة بدون ضوضاء، مع ربط واضح بالمخدوم وصاحب الطلب.',
            'icon' => 'ph-hands-praying',
            'primaryAction' => ['label' => 'المخدومون', 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
            'secondaryAction' => ['label' => 'الملفات الطبية', 'route' => route('app.medical-files'), 'icon' => 'ph-file-lock'],
        ];
    }

    private function filters(): array
    {
        return [
            ['value' => 'all', 'label' => 'الكل'],
            ['value' => 'open', 'label' => 'مفتوحة'],
            ['value' => 'answered', 'label' => 'مستجابة'],
            ['value' => 'closed', 'label' => 'مغلقة'],
        ];
    }

    private function stats(Builder $baseQuery): array
    {
        return [
            ['label' => 'مفتوحة', 'value' => (clone $baseQuery)->where('status', 'open')->count(), 'tone' => 'blue'],
            ['label' => 'مستجابة', 'value' => (clone $baseQuery)->where('status', 'answered')->count(), 'tone' => 'emerald'],
            ['label' => 'آخر 7 أيام', 'value' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count(), 'tone' => 'amber'],
        ];
    }

    private function beneficiaryOptions(User $user): Collection
    {
        return WebAppScope::beneficiaries($user)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'code']);
    }
}
