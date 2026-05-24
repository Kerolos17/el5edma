<?php

declare(strict_types=1);

namespace App\Livewire\WebApp\Concerns;

use App\Models\PrayerRequest;
use App\Support\WebAppScope;

trait ManagesPrayerRequests
{
    public bool $showPrayerForm = false;

    public ?int $prayerBeneficiaryId = null;

    public string $prayerTitle = '';

    public string $prayerBody = '';

    public function openPrayerForm(?int $beneficiaryId = null): void
    {
        abort_unless(auth()->user()->can('create', PrayerRequest::class), 403);

        $this->resetPrayerForm();

        if ($beneficiaryId !== null && $this->beneficiaryOptionsQuery()->whereKey($beneficiaryId)->exists()) {
            $this->prayerBeneficiaryId = $beneficiaryId;
        }

        $this->showPrayerForm = true;
    }

    public function closePrayerForm(): void
    {
        $this->showPrayerForm = false;
        $this->resetPrayerForm();
    }

    public function savePrayer(): void
    {
        abort_unless(auth()->user()->can('create', PrayerRequest::class), 403);

        $data = $this->validate([
            'prayerBeneficiaryId' => ['required', 'integer'],
            'prayerTitle' => ['required', 'string', 'max:255'],
            'prayerBody' => ['nullable', 'string', 'max:2000'],
        ]);

        $beneficiary = $this->beneficiaryOptionsQuery()->whereKey($data['prayerBeneficiaryId'])->firstOrFail();

        PrayerRequest::create([
            'beneficiary_id' => $beneficiary->id,
            'title' => $data['prayerTitle'],
            'body' => $data['prayerBody'] ?: null,
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        $this->showPrayerForm = false;
        $this->resetPrayerForm();
        $this->dispatch('toast', message: __('web_app.toasts.prayer_created'), type: 'success');
    }

    public function markPrayerAnswered(int $prayerRequestId): void
    {
        $prayerRequest = WebAppScope::prayerRequests(auth()->user())
            ->whereKey($prayerRequestId)
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $prayerRequest), 403);

        if ($prayerRequest->status !== 'answered') {
            $prayerRequest->update([
                'status' => 'answered',
                'answered_at' => now(),
            ]);
        }

        $this->dispatch('toast', message: __('web_app.toasts.prayer_answered'), type: 'success');
    }

    public function closePrayerRequest(int $prayerRequestId): void
    {
        $prayerRequest = WebAppScope::prayerRequests(auth()->user())
            ->whereKey($prayerRequestId)
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $prayerRequest), 403);

        $prayerRequest->update([
            'status' => 'closed',
            'answered_at' => null,
        ]);

        $this->dispatch('toast', message: __('web_app.toasts.prayer_closed'), type: 'success');
    }

    public function reopenPrayerRequest(int $prayerRequestId): void
    {
        $prayerRequest = WebAppScope::prayerRequests(auth()->user())
            ->whereKey($prayerRequestId)
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $prayerRequest), 403);

        $prayerRequest->update([
            'status' => 'open',
            'answered_at' => null,
        ]);

        $this->dispatch('toast', message: __('web_app.toasts.prayer_reopened'), type: 'success');
    }

    private function resetPrayerForm(): void
    {
        $this->reset([
            'prayerBeneficiaryId',
            'prayerTitle',
            'prayerBody',
        ]);
    }
}
