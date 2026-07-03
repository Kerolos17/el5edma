<?php

declare(strict_types=1);

namespace App\Livewire\WebApp\Concerns;

use App\Models\Visit;
use App\Support\WebAppScope;

trait ManagesVisits
{
    public bool $showVisitForm = false;

    public ?int $editingVisitId = null;

    public ?int $visitBeneficiaryId = null;

    public string $visitType = '';

    public string $visitDate = '';

    public ?int $durationMinutes = null;

    public string $beneficiaryStatus = '';

    public string $visitFeedback = '';

    public bool $isCritical = false;

    public bool $needsFamilyLeader = false;

    public bool $needsServiceLeader = false;

    public function openVisitForm(?int $beneficiaryId = null): void
    {
        abort_unless(auth()->user()->can('create', Visit::class), 403);

        $this->resetVisitForm();

        $this->visitDate = now()->format('Y-m-d\TH:i');

        if ($beneficiaryId !== null && $this->beneficiaryOptionsQuery()->whereKey($beneficiaryId)->exists()) {
            $this->visitBeneficiaryId = $beneficiaryId;
        }

        $this->showVisitForm = true;
    }

    public function closeVisitForm(): void
    {
        $this->showVisitForm = false;
        $this->resetVisitForm();
    }

    public function editVisit(int $visitId): void
    {
        $visit = WebAppScope::visits(auth()->user())
            ->whereKey($visitId)
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $visit), 403);

        $this->resetVisitForm();
        $this->editingVisitId = $visit->id;
        $this->visitBeneficiaryId = $visit->beneficiary_id;
        $this->visitType = (string) ($visit->type ?? '');
        $this->visitDate = $visit->visit_date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->durationMinutes = $visit->duration_minutes;
        $this->beneficiaryStatus = (string) ($visit->beneficiary_status ?? '');
        $this->visitFeedback = (string) ($visit->feedback ?? '');
        $this->isCritical = (bool) $visit->is_critical;
        $this->needsFamilyLeader = (bool) $visit->needs_family_leader;
        $this->needsServiceLeader = (bool) $visit->needs_service_leader;
        $this->showVisitForm = true;
    }

    public function saveVisit(): void
    {
        $visit = $this->editingVisitId
            ? WebAppScope::visits(auth()->user())->whereKey($this->editingVisitId)->firstOrFail()
            : null;

        abort_unless($visit ? auth()->user()->can('update', $visit) : auth()->user()->can('create', Visit::class), 403);

        $data = $this->validate([
            'visitBeneficiaryId' => ['required', 'integer'],
            'visitType' => ['required', 'in:home_visit,phone_call,church_meeting'],
            'visitDate' => ['required', 'date'],
            'beneficiaryStatus' => ['required', 'in:great,good,needs_follow,critical'],
            'durationMinutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'visitFeedback' => ['nullable', 'string', 'max:2000'],
            'isCritical' => ['boolean'],
            'needsFamilyLeader' => ['boolean'],
            'needsServiceLeader' => ['boolean'],
        ]);

        $beneficiary = $this->beneficiaryOptionsQuery()->whereKey($data['visitBeneficiaryId'])->firstOrFail();

        $payload = [
            'beneficiary_id' => $beneficiary->id,
            'type' => $data['visitType'],
            'visit_date' => $data['visitDate'],
            'duration_minutes' => $data['durationMinutes'] ?: null,
            'beneficiary_status' => $data['beneficiaryStatus'],
            'feedback' => $data['visitFeedback'] ?: null,
            'is_critical' => (bool) $data['isCritical'],
            'needs_family_leader' => (bool) $data['needsFamilyLeader'],
            'needs_service_leader' => (bool) $data['needsServiceLeader'],
        ];

        if ($visit) {
            $visit->update($payload);
        } else {
            $visit = Visit::create($payload + [
                'created_by' => auth()->id(),
            ]);
        }

        if (auth()->user()->isServant()) {
            $visit->servants()->syncWithoutDetaching([auth()->id()]);
        }

        $this->completeLinkedScheduledVisit($visit);

        $message = $this->editingVisitId
            ? __('web_app.toasts.visit_updated')
            : __('web_app.toasts.visit_created');
        $this->showVisitForm = false;
        $this->resetVisitForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function resolveVisitFollowUp(int $visitId): void
    {
        $visit = WebAppScope::visits(auth()->user())
            ->whereKey($visitId)
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $visit), 403);

        $visit->update([
            'critical_resolved_at' => now(),
            'critical_resolved_by' => auth()->id(),
            'is_critical' => false,
            'needs_family_leader' => false,
            'needs_service_leader' => false,
        ]);

        $this->dispatch('toast', message: __('web_app.toasts.visit_follow_up_closed'), type: 'success');
    }

    public function deleteVisit(int $id): void
    {
        $visit = WebAppScope::visits(auth()->user())->whereKey($id)->firstOrFail();
        abort_unless(auth()->user()->can('delete', $visit), 403);
        $visit->delete();
        $this->dispatch('toast', message: __('web_app.toasts.visit_deleted'), type: 'success');
    }

    private function resetVisitForm(): void
    {
        $this->reset([
            'editingVisitId',
            'visitBeneficiaryId',
            'visitType',
            'visitDate',
            'durationMinutes',
            'beneficiaryStatus',
            'visitFeedback',
            'isCritical',
            'needsFamilyLeader',
            'needsServiceLeader',
            'scheduledVisitContextId',
        ]);
    }
}
