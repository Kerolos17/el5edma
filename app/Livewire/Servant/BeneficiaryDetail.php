<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\Beneficiary;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('servant.layouts.app')]
#[Title('ملف المخدوم')]
class BeneficiaryDetail extends Component
{
    // #[Locked] prevents client-side wire payloads from swapping the beneficiary ID
    // between requests — without this, any authenticated user could read medical
    // and disability data belonging to any beneficiary in the database.
    #[Locked]
    public Beneficiary $beneficiary;

    public function mount(Beneficiary $beneficiary): void
    {
        $user = auth()->user();

        abort_if(
            ! $user->managesServiceGroup($beneficiary->service_group_id)
            && $beneficiary->assigned_servant_id !== $user->id,
            403,
        );

        $this->beneficiary = $beneficiary;
    }

    public function visitThis(): void
    {
        $this->dispatch('open-wizard-for', beneficiaryId: $this->beneficiary->id);
    }

    #[On('visit-saved')]
    public function refresh(): void {}

    public function render()
    {
        $b = $this->beneficiary;

        return view('livewire.servant.beneficiary-detail', [
            'recentVisits'       => $b->visits()->with('createdBy')->latest('visit_date')->limit(5)->get(),
            'activeMedications'  => $b->activeMedications()->get(),
            'openPrayerRequests' => $b->prayerRequests()->where('status', 'open')->latest()->limit(5)->get(),
        ]);
    }
}
