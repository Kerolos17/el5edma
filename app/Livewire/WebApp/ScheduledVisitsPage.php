<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\WebAppScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;

#[Layout('web-app.layouts.app')]
class ScheduledVisitsPage extends PlaceholderPage
{
    /** Calendar tab state: 0 = current week, -1 = last week, +1 = next… */
    public int $weekOffset = 0;

    /** List (default) vs weekly calendar view. */
    public bool $calendarView = false;

    public function mount(string $section = 'scheduled-visits'): void
    {
        $this->section = 'scheduled-visits';

        // Open on actionable appointments. The previous all/ascending view
        // put cancelled historical rows above visits created today, making a
        // newly saved appointment look as if it had been stored with an old date.
        if (! request()->has('filter')) {
            $this->filter = 'upcoming';
        }
    }

    public function render(): View
    {
        $user      = auth()->user();
        $baseQuery = $this->scheduledVisitsQuery($user);
        $records   = $this->applySort(
            $this->applyFilter(
                $this->applySearch(clone $baseQuery),
            ),
        )->paginate(12);

        return view('livewire.web-app.scheduled-visits-page', [
            'meta'                             => $this->meta(),
            'filters'                          => $this->filters(),
            'stats'                            => $this->stats(clone $baseQuery),
            'records'                          => $records,
            'weekDays'                         => $this->weekDays($user),
            'weekRangeLabel'                   => $this->weekRangeLabel(),
            'reportCards'                      => collect(),
            'beneficiaryOptions'               => $this->beneficiaryOptions($user),
            'servantOptions'                   => $this->servantOptions($user),
            'userRoleOptions'                  => collect(),
            'userServiceGroupOptions'          => collect(),
            'serviceGroupLeaderOptions'        => collect(),
            'serviceGroupServiceLeaderOptions' => collect(),
            'beneficiaryServiceGroupOptions'   => collect(),
            'beneficiaryServantOptions'        => collect(),
            'beneficiaryRecordStatusOptions'   => [],
            'medicalFileTypeOptions'           => [],
            'visitTypeOptions'                 => $this->visitTypeOptions(),
            'beneficiaryStatusOptions'         => $this->beneficiaryStatusOptions(),
        ]);
    }

    /**
     * The seven days of the displayed week (Saturday → Friday, the Egyptian
     * work week) with their scoped pending/completed visits grouped per day.
     *
     * @return Collection<int, array{date: Carbon, isToday: bool, visits: Collection}>
     */
    private function weekDays(User $user): Collection
    {
        $weekStart = now()->startOfWeek(Carbon::SATURDAY)->addWeeks($this->weekOffset);

        $visits = $this->scheduledVisitsQuery($user)
            ->whereBetween('scheduled_date', [
                $weekStart->toDateString(),
                $weekStart->copy()->addDays(6)->toDateString(),
            ])
            ->with(['beneficiary:id,full_name'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        return collect(range(0, 6))->map(function (int $offset) use ($weekStart, $visits) {
            $day = $weekStart->copy()->addDays($offset);

            return [
                'date'    => $day,
                'isToday' => $day->isToday(),
                'visits'  => $visits->filter(fn ($visit) => $visit->scheduled_date->isSameDay($day))->values(),
            ];
        });
    }

    private function weekRangeLabel(): string
    {
        $weekStart = now()->startOfWeek(Carbon::SATURDAY)->addWeeks($this->weekOffset);

        return $weekStart->isoFormat('D MMMM') . ' — ' . $weekStart->copy()->addDays(6)->isoFormat('D MMMM');
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
            'upcoming'  => $query->where('scheduled_date', '>=', now()->toDateString())->where('status', 'pending'),
            'completed' => $query->where('status', 'completed'),
            'past'      => $query->where('scheduled_date', '<', now()->toDateString()),
            default     => $query,
        };
    }

    private function applySort(Builder $query): Builder
    {
        $direction = $this->filter === 'upcoming' ? 'asc' : 'desc';

        return $query->orderBy('scheduled_date', $direction)
            ->orderBy('scheduled_time', $direction);
    }

    private function meta(): array
    {
        return [
            'title'           => __('web_app.resources.scheduled-visits.title'),
            'description'     => __('web_app.resources.scheduled-visits.description'),
            'icon'            => 'ph-calendar-check',
            'primaryAction'   => ['label' => __('web_app.actions.visits'), 'route' => route('app.visits'), 'icon' => 'ph-clipboard-text'],
            'secondaryAction' => ['label' => __('web_app.actions.prayer_requests'), 'route' => route('app.prayer-requests'), 'icon' => 'ph-hands-praying'],
        ];
    }

    private function filters(): array
    {
        return [
            ['value' => 'all', 'label' => __('web_app.filters.all')],
            ['value' => 'upcoming', 'label' => __('web_app.filters.upcoming')],
            ['value' => 'completed', 'label' => __('web_app.filters.completed')],
            ['value' => 'past', 'label' => __('web_app.filters.past')],
        ];
    }

    private function stats(Builder $baseQuery): array
    {
        return [
            ['label' => __('web_app.stats.upcoming'), 'value' => (clone $baseQuery)->where('scheduled_date', '>=', now()->toDateString())->where('status', 'pending')->count(), 'tone' => 'blue'],
            ['label' => __('web_app.stats.today'), 'value' => (clone $baseQuery)->whereDate('scheduled_date', now()->toDateString())->count(), 'tone' => 'emerald'],
            ['label' => __('web_app.stats.completed'), 'value' => (clone $baseQuery)->where('status', 'completed')->count(), 'tone' => 'amber'],
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
}
