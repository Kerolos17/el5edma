<?php

declare(strict_types=1);

namespace App\Livewire\WebApp\Concerns;

use App\Models\ScheduledVisit;
use App\Models\Visit;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait ManagesScheduledVisits
{
    public bool $showScheduledVisitForm = false;

    public ?int $editingScheduledVisitId = null;

    public ?int $scheduledVisitBeneficiaryId = null;

    public array $scheduledVisitAssignedServantIds = [];

    public string $scheduledVisitDate = '';

    public string $scheduledVisitTime = '';

    public string $scheduledVisitNotes = '';

    public ?int $scheduledVisitContextId = null;

    public function updatedScheduledVisitBeneficiaryId(): void
    {
        if ($this->showScheduledVisitForm) {
            $this->scheduledVisitAssignedServantIds = [];
        }
    }

    public function openScheduledVisitForm(?int $beneficiaryId = null): void
    {
        abort_unless(auth()->user()->can('create', ScheduledVisit::class), 403);

        $this->resetScheduledVisitForm();

        if ($beneficiaryId !== null && $this->beneficiaryOptionsQuery()->whereKey($beneficiaryId)->exists()) {
            $this->scheduledVisitBeneficiaryId = $beneficiaryId;
        }

        $this->scheduledVisitDate = now()->toDateString();
        $this->scheduledVisitFormDefaultAssignee();
        $this->showScheduledVisitForm = true;
    }

    public function closeScheduledVisitForm(): void
    {
        $this->showScheduledVisitForm = false;
        $this->resetScheduledVisitForm();
    }

    public function editScheduledVisit(int $scheduledVisitId): void
    {
        $scheduledVisit = WebAppScope::scheduledVisits(auth()->user())
            ->whereKey($scheduledVisitId)
            ->where('status', 'pending')
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $scheduledVisit), 403);

        $scheduledVisit->loadMissing('servants');

        $this->resetScheduledVisitForm();
        $this->editingScheduledVisitId = $scheduledVisit->id;
        $this->scheduledVisitBeneficiaryId = $scheduledVisit->beneficiary_id;
        $this->scheduledVisitAssignedServantIds = $scheduledVisit->servants
            ->pluck('id')
            ->whenEmpty(fn (Collection $ids) => $scheduledVisit->assigned_servant_id ? $ids->push($scheduledVisit->assigned_servant_id) : $ids)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $this->scheduledVisitDate = $scheduledVisit->scheduled_date?->toDateString() ?? now()->toDateString();
        $this->scheduledVisitTime = substr((string) $scheduledVisit->scheduled_time, 0, 5);
        $this->scheduledVisitNotes = (string) ($scheduledVisit->notes ?? '');
        $this->showScheduledVisitForm = true;
    }

    public function saveScheduledVisit(): void
    {
        $scheduledVisit = $this->editingScheduledVisitId
            ? WebAppScope::scheduledVisits(auth()->user())->whereKey($this->editingScheduledVisitId)->firstOrFail()
            : null;

        abort_unless(
            $scheduledVisit
                ? auth()->user()->can('update', $scheduledVisit)
                : auth()->user()->can('create', ScheduledVisit::class),
            403,
        );
        abort_unless(! $scheduledVisit || $scheduledVisit->status === 'pending', 403);

        $data = $this->validate([
            'scheduledVisitBeneficiaryId' => ['required', 'integer'],
            'scheduledVisitAssignedServantIds' => ['required', 'array', 'min:1'],
            'scheduledVisitAssignedServantIds.*' => ['integer'],
            'scheduledVisitDate' => ['required', 'date', 'after_or_equal:today'],
            'scheduledVisitTime' => ['required', 'date_format:H:i'],
            'scheduledVisitNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $beneficiary = $this->beneficiaryOptionsQuery()->whereKey($data['scheduledVisitBeneficiaryId'])->firstOrFail();
        $assignedServantIds = $this->servantOptionsQuery()
            ->where('service_group_id', $beneficiary->service_group_id)
            ->whereIn('id', $data['scheduledVisitAssignedServantIds'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($assignedServantIds) !== count(array_unique($data['scheduledVisitAssignedServantIds']))) {
            throw ValidationException::withMessages([
                'scheduledVisitAssignedServantIds' => __('web_app.validation.scheduled_servants_must_match_beneficiary_group'),
            ]);
        }

        $payload = [
            'beneficiary_id' => $beneficiary->id,
            'assigned_servant_id' => $assignedServantIds[0],
            'scheduled_date' => $data['scheduledVisitDate'],
            'scheduled_time' => $data['scheduledVisitTime'],
            'notes' => $data['scheduledVisitNotes'] ?: null,
            'status' => 'pending',
        ];

        if ($scheduledVisit) {
            $scheduledVisit->update($payload);
        } else {
            $scheduledVisit = ScheduledVisit::create($payload + [
                'created_by' => auth()->id(),
            ]);
        }

        $scheduledVisit->syncAssignedServants($assignedServantIds);

        $toastMessage = $this->editingScheduledVisitId
            ? __('web_app.toasts.scheduled_visit_updated')
            : __('web_app.toasts.scheduled_visit_created');

        $this->showScheduledVisitForm = false;
        $this->resetScheduledVisitForm();
        $this->dispatch('toast', message: $toastMessage, type: 'success');
    }

    public function cancelScheduledVisit(int $scheduledVisitId): void
    {
        $scheduledVisit = WebAppScope::scheduledVisits(auth()->user())->whereKey($scheduledVisitId)->firstOrFail();

        abort_unless($scheduledVisit->status === 'pending', 403);

        if (auth()->user()->can('delete', $scheduledVisit)) {
            $scheduledVisit->update(['status' => 'cancelled']);
            $this->dispatch('toast', message: __('web_app.toasts.scheduled_visit_cancelled'), type: 'success');

            return;
        }

        abort_unless(
            auth()->user()->isServant()
            && $scheduledVisit->isAssignedTo(auth()->id())
            && $scheduledVisit->status === 'pending',
            403
        );

        $scheduledVisit->update(['status' => 'cancelled']);
        $this->dispatch('toast', message: __('web_app.toasts.scheduled_visit_cancelled'), type: 'success');
    }

    public function openVisitFromScheduled(int $scheduledVisitId): void
    {
        $scheduledVisit = WebAppScope::scheduledVisits(auth()->user())->whereKey($scheduledVisitId)->firstOrFail();

        abort_unless($scheduledVisit->status === 'pending', 403);

        $this->openVisitForm($scheduledVisit->beneficiary_id);
        $this->scheduledVisitContextId = $scheduledVisit->id;
    }

    private function resetScheduledVisitForm(): void
    {
        $this->reset([
            'scheduledVisitBeneficiaryId',
            'editingScheduledVisitId',
            'scheduledVisitAssignedServantIds',
            'scheduledVisitDate',
            'scheduledVisitTime',
            'scheduledVisitNotes',
        ]);
    }

    private function scheduledVisitFormDefaultAssignee(): void
    {
        if (auth()->user()->isServant()) {
            $this->scheduledVisitAssignedServantIds = [auth()->id()];
        }
    }

    private function scheduledVisitServantOptionsQuery(): Builder
    {
        $query = $this->servantOptionsQuery();

        if ($this->scheduledVisitBeneficiaryId === null) {
            return $query;
        }

        $beneficiary = $this->beneficiaryOptionsQuery()
            ->whereKey($this->scheduledVisitBeneficiaryId)
            ->first();

        if (! $beneficiary) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('service_group_id', $beneficiary->service_group_id);
    }

    private function completeLinkedScheduledVisit(Visit $visit): void
    {
        if ($this->scheduledVisitContextId !== null) {
            $scheduledVisit = WebAppScope::scheduledVisits(auth()->user())
                ->whereKey($this->scheduledVisitContextId)
                ->where('status', 'pending')
                ->first();

            if ($scheduledVisit !== null) {
                $scheduledVisit->update([
                    'status' => 'completed',
                    'completed_visit_id' => $visit->id,
                ]);
            }

            return;
        }

        if (! auth()->user()->isServant()) {
            return;
        }

        ScheduledVisit::query()
            ->where('beneficiary_id', $visit->beneficiary_id)
            ->assignedTo(auth()->id())
            ->where('status', 'pending')
            ->whereDate('scheduled_date', today())
            ->update([
                'status' => 'completed',
                'completed_visit_id' => $visit->id,
            ]);
    }
}
