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
class PrayerRequestsPage extends PlaceholderPage
{
    public function mount(string $section = 'prayer-requests'): void
    {
        $this->section = 'prayer-requests';
    }

    public function render(): View
    {
        $user      = auth()->user();
        $baseQuery = $this->prayerRequestsQuery($user);
        $records   = $this->applySort(
            $this->applyFilter(
                $this->applySearch(clone $baseQuery),
            ),
        )->paginate(12);

        return view('livewire.web-app.prayer-requests-page', [
            'meta'                             => $this->meta(),
            'filters'                          => $this->filters(),
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
            'medicalFileTypeOptions'           => [],
            'visitTypeOptions'                 => [],
            'beneficiaryStatusOptions'         => [],
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

        // NOTE: body is encrypted at rest — search by title and beneficiary name only.
        return $query->where(fn (Builder $builder) => $builder
            ->where('title', 'like', $term)
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
            'title'           => __('web_app.resources.prayer-requests.title'),
            'description'     => __('web_app.resources.prayer-requests.description'),
            'icon'            => 'ph-hands-praying',
            'primaryAction'   => ['label' => __('web_app.actions.beneficiaries'), 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
            'secondaryAction' => ['label' => __('web_app.actions.medical_files'), 'route' => route('app.medical-files'), 'icon' => 'ph-file-lock'],
        ];
    }

    private function filters(): array
    {
        return [
            ['value' => 'all', 'label' => __('web_app.filters.all')],
            ['value' => 'open', 'label' => __('web_app.filters.open')],
            ['value' => 'answered', 'label' => __('web_app.filters.answered')],
            ['value' => 'closed', 'label' => __('web_app.filters.closed')],
        ];
    }

    private function stats(Builder $baseQuery): array
    {
        return [
            ['label' => __('web_app.stats.open'), 'value' => (clone $baseQuery)->where('status', 'open')->count(), 'tone' => 'blue'],
            ['label' => __('web_app.stats.answered'), 'value' => (clone $baseQuery)->where('status', 'answered')->count(), 'tone' => 'emerald'],
            ['label' => __('web_app.stats.last_7_days'), 'value' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count(), 'tone' => 'amber'],
        ];
    }

    private function beneficiaryOptions(User $user): Collection
    {
        return WebAppScope::beneficiaries($user)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'code']);
    }
}
