<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class CreateVisitWizard extends Component
{
    public bool $open = false;

    // #[Locked] prevents direct client wire-setting; nextStep/prevStep still work server-side
    #[Locked]
    public int $step = 1;

    // Step 1
    public string $beneficiarySearch   = '';
    public ?int $selectedBeneficiaryId = null;

    // Step 2
    public string $visitType = '';

    // Step 3
    public ?int $durationMinutes     = null;
    public string $beneficiaryStatus = '';
    public string $feedback          = '';
    public bool $isCritical          = false;
    public bool $needsFamilyLeader   = false;
    public bool $needsServiceLeader  = false;

    // Draft flag — true if user touched any field beyond initial load
    public bool $hasDraft = false;

    // Offline pending visits count (from shared offlineQueue module)
    public int $offlineCount = 0;

    #[On('open-wizard')]
    public function openWizard(): void
    {
        $this->resetWizardState();
        $this->open = true;
        $this->dispatch('wizard-open');
    }

    #[On('open-wizard-for')]
    public function openWizardFor(int $beneficiaryId): void
    {
        $this->resetWizardState();

        // Pre-select only if the servant owns this beneficiary; otherwise open at step 1
        if ($this->ownedBeneficiaryQuery()->where('id', $beneficiaryId)->exists()) {
            $this->selectedBeneficiaryId = $beneficiaryId;
            $this->step                  = 2;
        }

        $this->open = true;
        $this->dispatch('wizard-open');
    }

    #[On('offlineQueueCount')]
    public function handleOfflineQueueCount(array $payload): void
    {
        $this->dispatch('offlineQueueCount', $payload);
    }

    public function close(): void
    {
        if ($this->hasDraft) {
            $this->dispatch('confirm-close');
        } else {
            $this->forceClose();
        }
    }

    public function forceClose(): void
    {
        $this->open = false;
        $this->clearDraft();
    }

    public function selectBeneficiary(int $id): void
    {
        abort_unless($this->ownedBeneficiaryQuery()->where('id', $id)->exists(), 403);

        $this->selectedBeneficiaryId = $id;
        $this->beneficiarySearch     = '';
        $this->hasDraft              = true;
        $this->saveDraft();
    }

    public function clearBeneficiary(): void
    {
        $this->selectedBeneficiaryId = null;
        $this->beneficiarySearch     = '';
        $this->saveDraft();
    }

    public function updatedBeneficiarySearch(): void
    {
        if ($this->selectedBeneficiaryId) {
            $this->selectedBeneficiaryId = null;
            $this->saveDraft();
        }
    }

    public function nextStep(): void
    {
        if ($this->step >= 4) {
            return;
        }

        try {
            $this->validateCurrentStep();
        } catch (ValidationException $e) {
            $this->dispatch('wizard-validation-failed');

            throw $e;
        }
        $this->step++;
        $this->hasDraft = true;
        $this->saveDraft();
    }

    public function prevStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit(): void
    {
        $this->validate([
            'selectedBeneficiaryId' => 'required|integer',
            'visitType'             => 'required|in:home_visit,phone_call,church_meeting',
            'beneficiaryStatus'     => 'required|in:great,good,needs_follow,critical',
            'durationMinutes'       => 'nullable|integer|min:1|max:480',
            'feedback'              => 'nullable|string|max:2000',
        ]);

        // Ownership check — prevents forged Livewire property payloads
        $beneficiary = $this->ownedBeneficiaryQuery()
            ->where('id', $this->selectedBeneficiaryId)
            ->firstOrFail();

        $visit = Visit::create([
            'beneficiary_id'       => $beneficiary->id,
            'type'                 => $this->visitType,
            'visit_date'           => now(),
            'duration_minutes'     => $this->durationMinutes ?: null,
            'beneficiary_status'   => $this->beneficiaryStatus,
            'feedback'             => $this->feedback ?: null,
            'is_critical'          => $this->isCritical,
            'needs_family_leader'  => $this->needsFamilyLeader,
            'needs_service_leader' => $this->needsServiceLeader,
            'created_by'           => auth()->id(),
        ]);

        $visit->servants()->attach(auth()->id());

        $this->clearDraft();
        $this->open = false;
        $this->dispatch('visit-saved');
        $this->dispatch('toast', message: __('web_app.forms.wizard.saved_success'), type: 'success');
    }

    public function render()
    {
        $user = auth()->user();

        $beneficiaries = ! $this->selectedBeneficiaryId
            ? $this->ownedBeneficiaryQuery()
                ->when($this->beneficiarySearch, fn ($q) => $q->where(
                    fn ($q2) => $q2->where('full_name', 'like', "%{$this->beneficiarySearch}%")
                        ->orWhere('code', 'like', "%{$this->beneficiarySearch}%"),
                ))
                ->orderBy('full_name')
                ->limit(15)
                ->get()
            : collect();

        // Scoped fetch — never trusts the raw public property
        $selectedBeneficiary = $this->selectedBeneficiaryId
            ? $this->ownedBeneficiaryQuery()->find($this->selectedBeneficiaryId)
            : null;

        return view('livewire.servant.create-visit-wizard', compact(
            'beneficiaries',
            'selectedBeneficiary',
        ));
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    private function resetWizardState(): void
    {
        $this->reset([
            'step', 'beneficiarySearch', 'selectedBeneficiaryId',
            'visitType', 'durationMinutes', 'beneficiaryStatus',
            'feedback', 'isCritical', 'needsFamilyLeader', 'needsServiceLeader',
            'hasDraft',
        ]);
        $this->step = 1;
        // Do NOT clear draft here — allow resume on reopen
    }

    private function ownedBeneficiaryQuery(): Builder
    {
        $user = auth()->user();

        if ($user->role === UserRole::SuperAdmin || $user->role === UserRole::ServiceLeader) {
            return Beneficiary::query();
        }

        return Beneficiary::query()->where(
            fn ($q) => $q
                ->where('assigned_servant_id', $user->id)
                ->when(
                    $user->service_group_id,
                    fn ($q2) => $q2->orWhere('service_group_id', $user->service_group_id),
                ),
        );
    }

    private function validateCurrentStep(): void
    {
        match ($this->step) {
            1 => $this->validate(
                ['selectedBeneficiaryId' => 'required|integer'],
                ['selectedBeneficiaryId.required' => __('web_app.forms.wizard.validation_beneficiary')],
            ),
            2 => $this->validate(
                ['visitType' => 'required|in:home_visit,phone_call,church_meeting'],
                ['visitType.required' => __('web_app.forms.wizard.validation_visit_type')],
            ),
            3 => $this->validate(
                [
                    'beneficiaryStatus' => 'required|in:great,good,needs_follow,critical',
                    'durationMinutes'   => 'nullable|integer|min:1|max:480',
                ],
                ['beneficiaryStatus.required' => __('web_app.forms.wizard.validation_status')],
            ),
            default => null,
        };
    }

    // ── Draft Persistence (client-side via dispatch) ──────────────────────────

    public function saveDraft(): void
    {
        $this->dispatch('save-wizard-draft', draft: $this->collectDraft());
    }

    private function collectDraft(): array
    {
        return [
            'step'                  => $this->step,
            'beneficiarySearch'     => $this->beneficiarySearch,
            'selectedBeneficiaryId' => $this->selectedBeneficiaryId,
            'visitType'             => $this->visitType,
            'durationMinutes'       => $this->durationMinutes,
            'beneficiaryStatus'     => $this->beneficiaryStatus,
            'feedback'              => $this->feedback,
            'isCritical'            => $this->isCritical,
            'needsFamilyLeader'     => $this->needsFamilyLeader,
            'needsServiceLeader'    => $this->needsServiceLeader,
            'hasDraft'              => $this->hasDraft,
        ];
    }

    private function clearDraft(): void
    {
        $this->dispatch('clear-wizard-draft');
    }

    #[On('restore-draft')]
    public function restoreDraft(array $draft): void
    {
        $this->step                  = $draft['step']                  ?? 1;
        $this->beneficiarySearch     = $draft['beneficiarySearch']     ?? '';
        $this->selectedBeneficiaryId = $draft['selectedBeneficiaryId'] ?? null;
        $this->visitType             = $draft['visitType']             ?? '';
        $this->durationMinutes       = $draft['durationMinutes']       ?? null;
        $this->beneficiaryStatus     = $draft['beneficiaryStatus']     ?? '';
        $this->feedback              = $draft['feedback']              ?? '';
        $this->isCritical            = $draft['isCritical']            ?? false;
        $this->needsFamilyLeader     = $draft['needsFamilyLeader']     ?? false;
        $this->needsServiceLeader    = $draft['needsServiceLeader']    ?? false;
        $this->hasDraft              = $draft['hasDraft']              ?? false;
    }
}
