<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;

#[Layout('web-app.layouts.app')]
class MedicalFilesPage extends PlaceholderPage
{
    public function mount(string $section = 'medical-files'): void
    {
        $this->section = 'medical-files';
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $baseQuery = $this->medicalFilesQuery($user);
        $records = $this->applySort(
            $this->applyFilter(
                $this->applySearch(clone $baseQuery),
            )
        )->paginate(12);

        return view('livewire.web-app.placeholder-page', [
            'meta' => $this->meta(),
            'filters' => $this->filters($user),
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
            'medicalFileTypeOptions' => $this->medicalFileTypeOptions(),
            'visitTypeOptions' => [],
            'beneficiaryStatusOptions' => [],
        ]);
    }

    private function medicalFilesQuery(User $user): Builder
    {
        return WebAppScope::medicalFiles($user);
    }

    private function applySearch(Builder $query): Builder
    {
        if ($this->search === '') {
            return $query;
        }

        $term = '%' . $this->search . '%';

        return $query->where(fn (Builder $builder) => $builder
            ->where('title', 'like', $term)
            ->orWhere('file_type', 'like', $term)
            ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term)));
    }

    private function applyFilter(Builder $query): Builder
    {
        return match ($this->filter) {
            'recent' => $query->where('created_at', '>=', now()->subDays(30)),
            'all' => $query,
            default => $query->where('file_type', $this->filter),
        };
    }

    private function applySort(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    private function meta(): array
    {
        return [
            'title' => 'الملفات الطبية',
            'description' => 'الوصول السريع للملفات الطبية المصرح بها داخل نطاقك، مع تنظيم حسب النوع وتاريخ الرفع.',
            'icon' => 'ph-file-lock',
            'primaryAction' => ['label' => 'طلبات الصلاة', 'route' => route('app.prayer-requests'), 'icon' => 'ph-hands-praying'],
            'secondaryAction' => ['label' => 'التقارير', 'route' => route('app.reports'), 'icon' => 'ph-chart-line-up'],
        ];
    }

    private function filters(User $user): array
    {
        $base = array_merge(
            [
                ['value' => 'all', 'label' => 'الكل'],
                ['value' => 'recent', 'label' => 'آخر 30 يوم'],
            ],
            WebAppScope::medicalFiles($user)
                ->select('file_type')
                ->distinct()
                ->pluck('file_type')
                ->filter()
                ->map(fn (string $type) => ['value' => $type, 'label' => $type])
                ->values()
                ->all(),
        );

        return array_values(array_reduce($base, function (array $carry, array $item): array {
            $carry[$item['value']] = $item;

            return $carry;
        }, []));
    }

    private function stats(Builder $baseQuery): array
    {
        return [
            ['label' => 'إجمالي الملفات', 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
            ['label' => 'آخر 30 يوم', 'value' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count(), 'tone' => 'emerald'],
            ['label' => 'أنواع مختلفة', 'value' => (clone $baseQuery)->select('file_type')->distinct()->count('file_type'), 'tone' => 'amber'],
        ];
    }

    private function beneficiaryOptions(User $user): Collection
    {
        return WebAppScope::beneficiaries($user)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'code']);
    }

    private function medicalFileTypeOptions(): array
    {
        return [
            'report' => 'تقرير',
            'image' => 'صورة',
            'document' => 'مستند',
        ];
    }
}
