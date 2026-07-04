<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('servant.layouts.app')]
#[Title('الزيارات')]
class VisitList extends Component
{
    use WithPagination;

    #[Url(except: 'all')]
    public string $filter = 'all'; // all | month | critical

    #[On('visit-saved')]
    public function refresh(): void {}

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        // Scope by beneficiary ownership — consistent with BeneficiaryList.
        // This allows leaders to see all visits in their managed groups,
        // while servants see visits on their accessible beneficiaries.
        $query = Visit::query()
            ->with('beneficiary')
            ->whereHas('beneficiary', fn (Builder $q) => $q->where(
                fn (Builder $inner) => $inner
                    ->where('assigned_servant_id', $user->id)
                    ->when(
                        $user->service_group_id,
                        fn (Builder $q2) => $q2->orWhere('service_group_id', $user->service_group_id),
                    ),
            ))
            ->latest('visit_date');

        match ($this->filter) {
            'month' => $query->whereMonth('visit_date', now()->month)
                ->whereYear('visit_date', now()->year),
            'critical' => $query->where('is_critical', true),
            default    => null,
        };

        $visits = $query->paginate(20);

        return view('livewire.servant.visit-list', compact('visits'));
    }
}
