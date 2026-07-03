<?php

declare(strict_types=1);

namespace App\Livewire\WebApp\Concerns;

use App\Models\PrayerRequest;
use App\Support\WebAppScope;

trait ManagesPrayerRequests
{
    public bool $showPrayerForm = false;

    public ?int $editingPrayerId = null;

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

    public function editPrayer(int $id): void
    {
        $record = WebAppScope::prayerRequests(auth()->user())->whereKey($id)->firstOrFail();
        abort_unless(auth()->user()->can('update', $record), 403);

        $this->resetPrayerForm();
        $this->editingPrayerId = $record->id;
        $this->prayerBeneficiaryId = $record->beneficiary_id;
        $this->prayerTitle = $record->title;
        $this->prayerBody = $record->body ?? '';
        $this->showPrayerForm = true;
    }

    public function closePrayerForm(): void
    {
        $this->showPrayerForm = false;
        $this->resetPrayerForm();
    }

    public function savePrayer(): void
    {
        $actor = auth()->user();
        $record = $this->editingPrayerId
            ? WebAppScope::prayerRequests($actor)->whereKey($this->editingPrayerId)->firstOrFail()
            : null;

        abort_unless($record ? $actor->can('update', $record) : $actor->can('create', PrayerRequest::class), 403);

        $rules = [
            'prayerTitle' => ['required', 'string', 'max:255'],
            'prayerBody' => ['nullable', 'string', 'max:2000'],
        ];

        if (! $record) {
            $rules['prayerBeneficiaryId'] = ['required', 'integer'];
        }

        $data = $this->validate($rules);

        if ($record) {
            $record->update([
                'title' => $data['prayerTitle'],
                'body' => $data['prayerBody'] ?: null,
            ]);
            $message = __('web_app.toasts.prayer_updated');
        } else {
            $beneficiary = $this->beneficiaryOptionsQuery()->whereKey($data['prayerBeneficiaryId'])->firstOrFail();
            PrayerRequest::create([
                'beneficiary_id' => $beneficiary->id,
                'title' => $data['prayerTitle'],
                'body' => $data['prayerBody'] ?: null,
                'status' => 'open',
                'created_by' => $actor->id,
            ]);
            $message = __('web_app.toasts.prayer_created');
        }

        $this->showPrayerForm = false;
        $this->resetPrayerForm();
        $this->dispatch('toast', message: $message, type: 'success');
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
            'editingPrayerId',
            'prayerBeneficiaryId',
            'prayerTitle',
            'prayerBody',
        ]);
    }
}
