<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\Visit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('servant.layouts.app')]
#[Title('لوحة التحكم')]
class Dashboard extends Component
{
    #[On('visit-saved')]
    public function refresh(): void {}

    public function render()
    {
        $user = auth()->user();

        $myBeneficiariesCount = Beneficiary::where('assigned_servant_id', $user->id)->count();

        $visitsThisMonth = Visit::where('created_by', $user->id)
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();

        $scheduledCount = ScheduledVisit::query()
            ->assignedTo($user)
            ->where('scheduled_date', '>=', now()->toDateString())
            ->where('status', '!=', 'completed')
            ->count();

        $criticalCount = Visit::where('is_critical', true)
            ->whereNull('critical_resolved_at')
            ->whereHas('beneficiary', fn ($q) => $q->where('assigned_servant_id', $user->id))
            ->count();

        $recentVisits = Visit::where('created_by', $user->id)
            ->with('beneficiary')
            ->latest('visit_date')
            ->limit(5)
            ->get();

        return view('livewire.servant.dashboard', compact(
            'myBeneficiariesCount',
            'visitsThisMonth',
            'scheduledCount',
            'criticalCount',
            'recentVisits',
        ));
    }
}
