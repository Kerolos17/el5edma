<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\Beneficiary;
use App\Models\PrayerRequest;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('servant.layouts.app')]
#[Title('طلبات الصلاة')]
class PrayerRequestList extends Component
{
    #[Url(except: 'open')]
    public string $filter = 'open';

    public bool $showForm = false;

    // Authorization enforced via ownedBeneficiaryQuery() in save() — no #[Locked] needed
    // because this is a form select field, not a route-bound model ID
    public ?int $beneficiaryId = null;

    public string $title = '';
    public string $body  = '';

    public function openForm(): void
    {
        $this->reset(['beneficiaryId', 'title', 'body']);
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->reset(['beneficiaryId', 'title', 'body']);
    }

    public function save(): void
    {
        $this->validate([
            'beneficiaryId' => ['required', 'integer'],
            'title'         => ['required', 'string', 'max:255'],
            'body'          => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless(
            $this->ownedBeneficiaryQuery()->where('id', $this->beneficiaryId)->exists(),
            403
        );

        PrayerRequest::create([
            'beneficiary_id' => $this->beneficiaryId,
            'title'          => $this->title,
            'body'           => $this->body ?: null,
            'status'         => 'open',
            'created_by'     => auth()->id(),
        ]);

        $this->showForm = false;
        $this->reset(['beneficiaryId', 'title', 'body']);
        $this->dispatch('toast', message: 'تم حفظ طلب الصلاة', type: 'success');
    }

    public function render()
    {
        $ownedBeneficiaryIds = $this->ownedBeneficiaryQuery()->pluck('id');

        $prayerRequests = PrayerRequest::whereIn('beneficiary_id', $ownedBeneficiaryIds)
            ->with('beneficiary')
            ->when($this->filter !== 'all', fn ($q) => $q->where('status', $this->filter))
            ->latest()
            ->limit(100)
            ->get();

        $myBeneficiaries = $this->ownedBeneficiaryQuery()->orderBy('full_name')->get(['id', 'full_name']);

        return view('livewire.servant.prayer-request-list', compact('prayerRequests', 'myBeneficiaries'));
    }

    private function ownedBeneficiaryQuery(): Builder
    {
        $user = auth()->user();

        return Beneficiary::query()->where(
            fn ($q) => $q
                ->where('assigned_servant_id', $user->id)
                ->when(
                    $user->service_group_id,
                    fn ($q2) => $q2->orWhere('service_group_id', $user->service_group_id),
                )
        );
    }
}
