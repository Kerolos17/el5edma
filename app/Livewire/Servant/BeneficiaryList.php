<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('servant.layouts.app')]
#[Title('المخدومون')]
class BeneficiaryList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $filter = 'all'; // all | mine | recent

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Beneficiary::query()
            ->with(['assignedServant', 'visits' => fn ($q) => $q->latest('visit_date')->limit(1)]);

        if ($this->search) {
            $query->where(fn ($q) => $q
                ->where('full_name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%"),
            );
        }

        // All filters are scoped to the user's ownership — never expose unrelated beneficiaries
        match ($this->filter) {
            'mine'   => $query->where('assigned_servant_id', $user->id),
            'recent' => $query->where($this->ownershipScope($user))->latest(),
            default  => $query->where($this->ownershipScope($user)),
        };

        $beneficiaries = $query->orderBy('full_name')->paginate(15);

        return view('livewire.servant.beneficiary-list', compact('beneficiaries'));
    }

    private function ownershipScope($user): \Closure
    {
        return fn (Builder $q) => $q
            ->where('assigned_servant_id', $user->id)
            ->when(
                $user->service_group_id,
                fn ($q2) => $q2->orWhere('service_group_id', $user->service_group_id),
            );
    }
}
