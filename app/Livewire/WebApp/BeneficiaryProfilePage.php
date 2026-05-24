<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Enums\UserRole;
use App\Livewire\WebApp\Concerns\ManagesBeneficiaries;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('web-app.layouts.app')]
class BeneficiaryProfilePage extends Component
{
    use WithFileUploads;
    use ManagesBeneficiaries {
        saveBeneficiary as traitSaveBeneficiary;
    }

    #[Locked]
    public Beneficiary $beneficiary;

    public function mount(Beneficiary $beneficiary): void
    {
        $user = auth()->user();
        abort_unless($user->can('view', $beneficiary), 403);
        $this->beneficiary = $beneficiary->load([
            'serviceGroup',
            'assignedServant',
            'visits' => fn ($q) => $q->with('createdBy')->latest('visit_date')->limit(10),
            'prayerRequests' => fn ($q) => $q->where('status', 'open')->latest()->limit(5),
            'activeMedications',
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();

        return view('livewire.web-app.beneficiary-profile-page', [
            'beneficiaryRecordStatusOptions' => [
                'active' => __('beneficiaries.active'),
                'inactive' => __('beneficiaries.inactive'),
                'moved' => __('beneficiaries.moved'),
                'deceased' => __('beneficiaries.deceased'),
            ],
            'beneficiaryServiceGroupOptions' => $this->profileServiceGroupOptions($user),
            'beneficiaryServantOptions' => $this->beneficiaryServantOptions($user),
        ]);
    }

    public function saveBeneficiary(): void
    {
        $this->traitSaveBeneficiary();
        $this->redirect(route('app.beneficiary-profile', $this->beneficiary->id), navigate: true);
    }

    private function profileServiceGroupOptions(User $actor): Collection
    {
        $query = ServiceGroup::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($actor->role === UserRole::ServiceLeader) {
            $query->where('service_leader_id', $actor->id);
        } elseif ($actor->role === UserRole::FamilyLeader) {
            $query->whereKey($actor->service_group_id);
        } elseif ($actor->role !== UserRole::SuperAdmin) {
            $query->whereRaw('1 = 0');
        }

        return $query->pluck('name', 'id');
    }
}
