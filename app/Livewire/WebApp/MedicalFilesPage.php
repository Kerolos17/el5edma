<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;

#[Layout('web-app.layouts.app')]
class MedicalFilesPage extends PlaceholderPage
{
    public function mount(string $section = 'medical-files'): void
    {
        $this->section = 'medical-files';
    }

    public function render(): View
    {
        $user      = auth()->user();
        $baseQuery = $this->medicalFilesQuery($user);
        $records   = $this->applySort(
            $this->applyFilter(
                $this->applySearch(clone $baseQuery),
            ),
        )->paginate(12);

        return view('livewire.web-app.medical-files-page', [
            'meta'                             => $this->meta(),
            'filters'                          => $this->filters($user),
            'stats'                            => $this->stats(clone $baseQuery),
            'records'                          => $records,
            'reportCards'                      => collect(),
            'beneficiaryOptions'               => $this->beneficiaryOptions($user),
            'servantOptions'                   => collect(),
            'userRoleOptions'                  => collect(),
            'userServiceGroupOptions'          => collect(),
            'serviceGroupLeaderOptions'        => collect(),
            'serviceGroupServiceLeaderOptions' => collect(),
            'beneficiaryServiceGroupOptions'   => collect(),
            'beneficiaryServantOptions'        => collect(),
            'beneficiaryRecordStatusOptions'   => [],
            'medicalFileTypeOptions'           => $this->medicalFileTypeOptions(),
            'visitTypeOptions'                 => [],
            'beneficiaryStatusOptions'         => [],
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
            'all'    => $query,
            default  => $query->where('file_type', $this->filter),
        };
    }

    private function applySort(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    private function meta(): array
    {
        return [
            'title'           => __('web_app.resources.medical-files.title'),
            'description'     => __('web_app.resources.medical-files.description'),
            'icon'            => 'ph-file-lock',
            'primaryAction'   => ['label' => __('web_app.actions.prayer_requests'), 'route' => route('app.prayer-requests'), 'icon' => 'ph-hands-praying'],
            'secondaryAction' => ['label' => __('web_app.actions.reports'), 'route' => route('app.reports'), 'icon' => 'ph-chart-line-up'],
        ];
    }

    private function filters(User $user): array
    {
        $base = array_merge(
            [
                ['value' => 'all', 'label' => __('web_app.filters.all')],
                ['value' => 'recent', 'label' => __('web_app.filters.last_30_days')],
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
            ['label' => __('web_app.stats.total_files'), 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
            ['label' => __('web_app.stats.last_30_days'), 'value' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count(), 'tone' => 'emerald'],
            ['label' => __('web_app.stats.distinct_types'), 'value' => (clone $baseQuery)->select('file_type')->distinct()->count('file_type'), 'tone' => 'amber'],
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
            'report'   => __('medical.report'),
            'image'    => __('medical.image'),
            'document' => __('medical.document'),
        ];
    }
}
