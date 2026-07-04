<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\ScheduledVisit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('servant.layouts.app')]
#[Title('الزيارات المجدولة')]
class ScheduledVisitList extends Component
{
    #[Url(except: 'upcoming')]
    public string $filter = 'upcoming';

    public function cancel(int $id): void
    {
        $sv = ScheduledVisit::query()
            ->assignedTo(auth()->id())
            ->where('id', $id)
            ->first();

        abort_unless($sv !== null, 404);  // 404 whether the record doesn't exist or isn't owned

        abort_unless($sv->status === 'pending', 403);

        $sv->update(['status' => 'cancelled']);

        $this->dispatch('toast', message: 'تم إلغاء الزيارة المجدولة', type: 'success');
    }

    public function render()
    {
        $user = auth()->user();

        // ScheduledVisits are personally assigned — we intentionally use personal-only
        // scope here, unlike BeneficiaryList which uses dual group scope.
        $query = ScheduledVisit::query()
            ->assignedTo($user)
            ->with(['beneficiary', 'servants']);

        $scheduledVisits = match ($this->filter) {
            'upcoming' => (clone $query)
                ->where('status', 'pending')
                ->where('scheduled_date', '>=', now()->toDateString())
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->limit(100)
                ->get(),
            'past' => (clone $query)
                ->where(fn ($q) => $q
                    ->where('scheduled_date', '<', now()->toDateString())
                    ->orWhere('status', 'completed'),
                )
                ->orderByDesc('scheduled_date')
                ->limit(50)
                ->get(),
            default => (clone $query)
                ->orderByDesc('scheduled_date')
                ->limit(100)
                ->get(),
        };

        return view('livewire.servant.scheduled-visit-list', compact('scheduledVisits'));
    }
}
