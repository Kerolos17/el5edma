<?php

declare(strict_types=1);

namespace App\Livewire\WebApp\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

trait ManagesResourceListing
{
    public function render(): View
    {
        $user               = auth()->user();
        $meta               = $this->meta();
        $beneficiaryOptions = $this->beneficiaryOptionsQuery()
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'code']);
        $servantOptions = $this->scheduledVisitServantOptionsQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'service_group_id']);

        $baseQuery = $this->queryForSection($user);
        $records   = $this->applySort(
            $this->applyFilter(
                $this->applySearch(clone $baseQuery),
            ),
        )->paginate($this->perPage());

        return view('livewire.web-app.placeholder-page', [
            'meta'                             => $meta,
            'filters'                          => $this->filters($user),
            'stats'                            => $this->stats($user, clone $baseQuery),
            'records'                          => $records,
            'reportCards'                      => collect(),
            'beneficiaryOptions'               => $beneficiaryOptions,
            'servantOptions'                   => $servantOptions,
            'userRoleOptions'                  => $this->userRoleOptionsForActor($user),
            'userServiceGroupOptions'          => $this->userServiceGroupOptionsForActor($user),
            'serviceGroupLeaderOptions'        => $this->serviceGroupLeaderOptions($user),
            'serviceGroupServiceLeaderOptions' => $this->serviceGroupServiceLeaderOptions($user),
            'beneficiaryServiceGroupOptions'   => $this->beneficiaryServiceGroupOptions($user),
            'beneficiaryServantOptions'        => $this->beneficiaryServantOptions($user),
            'beneficiaryRecordStatusOptions'   => $this->beneficiaryRecordStatusOptions(),
            'medicalFileTypeOptions'           => $this->medicalFileTypeOptions(),
            'visitTypeOptions'                 => $this->visitTypeOptions(),
            'beneficiaryStatusOptions'         => $this->beneficiaryStatusOptions(),
        ]);
    }

    private function queryForSection(User $user): Builder
    {
        return match ($this->section) {
            'beneficiaries' => WebAppScope::beneficiaries($user)
                ->with(['serviceGroup', 'assignedServant'])
                ->withCount('visits'),
            'visits'           => WebAppScope::visits($user),
            'scheduled-visits' => WebAppScope::scheduledVisits($user),
            'prayer-requests'  => WebAppScope::prayerRequests($user),
            'medical-files'    => WebAppScope::medicalFiles($user),
            'users'            => WebAppScope::users($user),
            'service-groups'   => WebAppScope::serviceGroups($user),
            default            => WebAppScope::beneficiaries($user),
        };
    }

    private function applySearch(Builder $query): Builder
    {
        if ($this->search === '') {
            return $query;
        }

        $term = '%' . $this->search . '%';

        return match ($this->section) {
            'beneficiaries' => $query->where(fn (Builder $builder) => $builder
                ->where('full_name', 'like', $term)
                ->orWhere('code', 'like', $term)
                ->orWhere('phone', 'like', $term)),
            'visits' => $query->where(fn (Builder $builder) => $builder
                ->where('type', 'like', $term)
                ->orWhere('feedback', 'like', $term)
                ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term))),
            'scheduled-visits' => $query->where(fn (Builder $builder) => $builder
                ->where('notes', 'like', $term)
                ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term))
                ->orWhereHas('servants', fn (Builder $servant) => $servant->where('name', 'like', $term))
                ->orWhereHas('assignedServant', fn (Builder $servant) => $servant->where('name', 'like', $term))),
            // NOTE: body is encrypted at rest — search by title and beneficiary name only.
            'prayer-requests' => $query->where(fn (Builder $builder) => $builder
                ->where('title', 'like', $term)
                ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term))),
            'medical-files' => $query->where(fn (Builder $builder) => $builder
                ->where('title', 'like', $term)
                ->orWhere('file_type', 'like', $term)
                ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term))),
            'users' => $query->where(fn (Builder $builder) => $builder
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('phone', 'like', $term)),
            'service-groups' => $query->where(fn (Builder $builder) => $builder
                ->where('name', 'like', $term)
                ->orWhere('description', 'like', $term)),
            default => $query,
        };
    }

    private function applyFilter(Builder $query): Builder
    {
        return match ($this->section) {
            'beneficiaries' => match ($this->filter) {
                'mine'        => $query->where('assigned_servant_id', auth()->id()),
                'recent'      => $query->whereHas('visits', fn (Builder $builder) => $builder->where('visit_date', '>=', now()->subDays(30))),
                'needs-visit' => $query->whereDoesntHave('visits'),
                default       => $query,
            },
            'visits' => match ($this->filter) {
                'month'     => $query->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year),
                'critical'  => $query->where('is_critical', true)->whereNull('critical_resolved_at'),
                'follow-up' => $query->where(fn (Builder $builder) => $builder->where('needs_family_leader', true)->orWhere('needs_service_leader', true)),
                default     => $query,
            },
            'scheduled-visits' => match ($this->filter) {
                'upcoming'  => $query->where('scheduled_date', '>=', now()->toDateString())->where('status', 'pending'),
                'completed' => $query->where('status', 'completed'),
                'past'      => $query->where('scheduled_date', '<', now()->toDateString()),
                default     => $query,
            },
            'prayer-requests' => $this->filter === 'all' ? $query : $query->where('status', $this->filter),
            'medical-files'   => match ($this->filter) {
                'recent' => $query->where('created_at', '>=', now()->subDays(30)),
                'all'    => $query,
                default  => $query->where('file_type', $this->filter),
            },
            'users' => match ($this->filter) {
                'inactive' => $query->where('is_active', false),
                'active'   => $query->where('is_active', true),
                'service_leader', 'family_leader', 'servant', 'super_admin' => $query->where('role', $this->filter),
                default => $query,
            },
            'service-groups' => match ($this->filter) {
                'active'   => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                default    => $query,
            },
            default => $query,
        };
    }

    private function applySort(Builder $query): Builder
    {
        return match ($this->section) {
            'beneficiaries'    => $query->orderBy('full_name'),
            'visits'           => $query->latest('visit_date'),
            'scheduled-visits' => $query->orderBy('scheduled_date')->orderBy('scheduled_time'),
            'prayer-requests'  => $query->latest(),
            'medical-files'    => $query->orderByDesc('created_at')->orderByDesc('id'),
            'users'            => $query->orderByDesc('is_active')->orderBy('name'),
            'service-groups'   => $query->orderBy('name'),
            default            => $query,
        };
    }

    private function perPage(): int
    {
        return match ($this->section) {
            'service-groups' => 8,
            'users'          => 10,
            default          => 12,
        };
    }

    private function meta(): array
    {
        return [
            'beneficiaries' => [
                'title'           => __('web_app.resources.beneficiaries.title'),
                'description'     => __('web_app.resources.beneficiaries.description'),
                'icon'            => 'ph-users-three',
                'primaryAction'   => ['label' => __('web_app.actions.dashboard'), 'route' => route('app.dashboard'), 'icon' => 'ph-squares-four'],
                'secondaryAction' => ['label' => __('web_app.actions.visits'), 'route' => route('app.visits'), 'icon' => 'ph-clipboard-text'],
            ],
            'visits' => [
                'title'           => __('web_app.resources.visits.title'),
                'description'     => __('web_app.resources.visits.description'),
                'icon'            => 'ph-clipboard-text',
                'primaryAction'   => ['label' => __('web_app.actions.beneficiaries'), 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
                'secondaryAction' => ['label' => __('web_app.actions.scheduled_visits'), 'route' => route('app.scheduled-visits'), 'icon' => 'ph-calendar-check'],
            ],
            'scheduled-visits' => [
                'title'           => __('web_app.resources.scheduled-visits.title'),
                'description'     => __('web_app.resources.scheduled-visits.description'),
                'icon'            => 'ph-calendar-check',
                'primaryAction'   => ['label' => __('web_app.actions.visits'), 'route' => route('app.visits'), 'icon' => 'ph-clipboard-text'],
                'secondaryAction' => ['label' => __('web_app.actions.prayer_requests'), 'route' => route('app.prayer-requests'), 'icon' => 'ph-hands-praying'],
            ],
            'prayer-requests' => [
                'title'           => __('web_app.resources.prayer-requests.title'),
                'description'     => __('web_app.resources.prayer-requests.description'),
                'icon'            => 'ph-hands-praying',
                'primaryAction'   => ['label' => __('web_app.actions.beneficiaries'), 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
                'secondaryAction' => ['label' => __('web_app.actions.medical_files'), 'route' => route('app.medical-files'), 'icon' => 'ph-file-lock'],
            ],
            'medical-files' => [
                'title'           => __('web_app.resources.medical-files.title'),
                'description'     => __('web_app.resources.medical-files.description'),
                'icon'            => 'ph-file-lock',
                'primaryAction'   => ['label' => __('web_app.actions.prayer_requests'), 'route' => route('app.prayer-requests'), 'icon' => 'ph-hands-praying'],
                'secondaryAction' => ['label' => __('web_app.actions.reports'), 'route' => route('app.reports'), 'icon' => 'ph-chart-line-up'],
            ],
            'reports' => [
                'title'           => __('web_app.resources.reports.title'),
                'description'     => __('web_app.resources.reports.description'),
                'icon'            => 'ph-chart-line-up',
                'primaryAction'   => ['label' => __('web_app.actions.dashboard'), 'route' => route('app.dashboard'), 'icon' => 'ph-squares-four'],
                'secondaryAction' => ['label' => __('web_app.actions.beneficiaries'), 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
            ],
            'users' => [
                'title'           => __('web_app.resources.users.title'),
                'description'     => __('web_app.resources.users.description'),
                'icon'            => 'ph-identification-card',
                'primaryAction'   => ['label' => __('web_app.actions.service_groups'), 'route' => route('app.service-groups'), 'icon' => 'ph-tree-structure'],
                'secondaryAction' => ['label' => __('web_app.actions.reports'), 'route' => route('app.reports'), 'icon' => 'ph-chart-line-up'],
            ],
            'service-groups' => [
                'title'           => __('web_app.resources.service-groups.title'),
                'description'     => __('web_app.resources.service-groups.description'),
                'icon'            => 'ph-tree-structure',
                'primaryAction'   => ['label' => __('web_app.actions.users'), 'route' => route('app.users'), 'icon' => 'ph-identification-card'],
                'secondaryAction' => ['label' => __('web_app.actions.reports'), 'route' => route('app.reports'), 'icon' => 'ph-chart-line-up'],
            ],
        ][$this->section];
    }

    private function filters(User $user): array
    {
        $base = match ($this->section) {
            'beneficiaries' => [
                ['value' => 'all', 'label' => __('web_app.filters.all')],
                ['value' => 'mine', 'label' => __('web_app.filters.mine')],
                ['value' => 'recent', 'label' => __('web_app.filters.recent')],
                ['value' => 'needs-visit', 'label' => __('web_app.filters.needs_visit')],
            ],
            'visits' => [
                ['value' => 'all', 'label' => __('web_app.filters.all')],
                ['value' => 'month', 'label' => __('web_app.filters.month')],
                ['value' => 'critical', 'label' => __('web_app.filters.critical')],
                ['value' => 'follow-up', 'label' => __('web_app.filters.follow_up')],
            ],
            'scheduled-visits' => [
                ['value' => 'all', 'label' => __('web_app.filters.all')],
                ['value' => 'upcoming', 'label' => __('web_app.filters.upcoming')],
                ['value' => 'completed', 'label' => __('web_app.filters.completed')],
                ['value' => 'past', 'label' => __('web_app.filters.past')],
            ],
            'prayer-requests' => [
                ['value' => 'all', 'label' => __('web_app.filters.all')],
                ['value' => 'open', 'label' => __('web_app.filters.open')],
                ['value' => 'answered', 'label' => __('web_app.filters.answered')],
                ['value' => 'closed', 'label' => __('web_app.filters.closed')],
            ],
            'medical-files' => array_merge(
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
            ),
            'users' => [
                ['value' => 'all', 'label' => __('web_app.filters.all')],
                ['value' => 'active', 'label' => __('web_app.filters.active')],
                ['value' => 'inactive', 'label' => __('web_app.filters.inactive')],
                ['value' => 'service_leader', 'label' => __('web_app.filters.service_leader')],
                ['value' => 'family_leader', 'label' => __('web_app.filters.family_leader')],
                ['value' => 'servant', 'label' => __('web_app.filters.servant')],
            ],
            'service-groups' => [
                ['value' => 'all', 'label' => __('web_app.filters.all')],
                ['value' => 'active', 'label' => __('web_app.filters.active')],
                ['value' => 'inactive', 'label' => __('web_app.filters.inactive')],
            ],
            default => [],
        };

        return array_values(array_reduce($base, function (array $carry, array $item): array {
            $carry[$item['value']] = $item;

            return $carry;
        }, []));
    }

    private function stats(User $user, Builder $baseQuery): array
    {
        return match ($this->section) {
            'beneficiaries' => [
                ['label' => __('web_app.stats.total_beneficiaries'), 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
                ['label' => __('web_app.stats.direct_assignments'), 'value' => (clone $baseQuery)->where('assigned_servant_id', $user->id)->count(), 'tone' => 'emerald'],
                ['label' => __('web_app.stats.no_visits'), 'value' => (clone $baseQuery)->whereDoesntHave('visits')->count(), 'tone' => 'amber'],
            ],
            'visits' => [
                ['label' => __('web_app.stats.total_visits'), 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
                ['label' => __('web_app.stats.this_month'), 'value' => (clone $baseQuery)->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year)->count(), 'tone' => 'emerald'],
                ['label' => __('web_app.stats.open_critical'), 'value' => (clone $baseQuery)->where('is_critical', true)->whereNull('critical_resolved_at')->count(), 'tone' => 'rose'],
            ],
            'scheduled-visits' => [
                ['label' => __('web_app.stats.upcoming'), 'value' => (clone $baseQuery)->where('scheduled_date', '>=', now()->toDateString())->where('status', 'pending')->count(), 'tone' => 'blue'],
                ['label' => __('web_app.stats.today'), 'value' => (clone $baseQuery)->whereDate('scheduled_date', now()->toDateString())->count(), 'tone' => 'emerald'],
                ['label' => __('web_app.stats.completed'), 'value' => (clone $baseQuery)->where('status', 'completed')->count(), 'tone' => 'amber'],
            ],
            'prayer-requests' => [
                ['label' => __('web_app.stats.open'), 'value' => (clone $baseQuery)->where('status', 'open')->count(), 'tone' => 'blue'],
                ['label' => __('web_app.stats.answered'), 'value' => (clone $baseQuery)->where('status', 'answered')->count(), 'tone' => 'emerald'],
                ['label' => __('web_app.stats.last_7_days'), 'value' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count(), 'tone' => 'amber'],
            ],
            'medical-files' => [
                ['label' => __('web_app.stats.total_files'), 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
                ['label' => __('web_app.stats.last_30_days'), 'value' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count(), 'tone' => 'emerald'],
                ['label' => __('web_app.stats.distinct_types'), 'value' => (clone $baseQuery)->select('file_type')->distinct()->count('file_type'), 'tone' => 'amber'],
            ],
            'users' => [
                ['label' => __('web_app.stats.total_users'), 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
                ['label' => __('web_app.stats.active_users'), 'value' => (clone $baseQuery)->where('is_active', true)->count(), 'tone' => 'emerald'],
                ['label' => __('web_app.stats.servants'), 'value' => (clone $baseQuery)->where('role', 'servant')->count(), 'tone' => 'amber'],
            ],
            'service-groups' => [
                ['label' => __('web_app.stats.service_groups'), 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
                ['label' => __('web_app.stats.active'), 'value' => (clone $baseQuery)->where('is_active', true)->count(), 'tone' => 'emerald'],
                ['label' => __('web_app.stats.inactive'), 'value' => (clone $baseQuery)->where('is_active', false)->count(), 'tone' => 'amber'],
            ],
            default => [],
        };
    }

    private function beneficiaryOptionsQuery(): Builder
    {
        return WebAppScope::beneficiaries(auth()->user());
    }

    private function visitTypeOptions(): array
    {
        return [
            'home_visit'     => __('visits.home_visit'),
            'phone_call'     => __('visits.phone_call'),
            'church_meeting' => __('visits.church_meeting'),
        ];
    }

    private function beneficiaryStatusOptions(): array
    {
        return [
            'great'        => __('visits.great'),
            'good'         => __('visits.good'),
            'needs_follow' => __('visits.needs_follow'),
            'critical'     => __('visits.critical'),
        ];
    }

    private function beneficiaryRecordStatusOptions(): array
    {
        return [
            'active'   => __('beneficiaries.active'),
            'inactive' => __('beneficiaries.inactive'),
            'moved'    => __('beneficiaries.moved'),
            'deceased' => __('beneficiaries.deceased'),
        ];
    }

    private function medicalFileTypeOptions(): array
    {
        return [
            'report'   => __('medical.report'),
            'image'    => __('medical.image'),
            'document' => __('medical.document'),
        ];
    }

    private function servantOptionsQuery(): Builder
    {
        return WebAppScope::users(auth()->user())->where('role', UserRole::Servant);
    }
}
