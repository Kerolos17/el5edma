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
        $sv = ScheduledVisit::findOrFail($id);

        abort_unless((int) $sv->assigned_servant_id === (int) auth()->id(), 403);
        abort_unless($sv->status === 'pending', 403);

        $sv->update(['status' => 'cancelled']);

        $this->dispatch('toast', message: 'تم إلغاء الزيارة المجدولة', type: 'success');
    }

    public function updatedFilter(): void {}

    public function render()
    {
        $user  = auth()->user();
        $query = ScheduledVisit::where('assigned_servant_id', $user->id)->with('beneficiary');

        $scheduledVisits = match ($this->filter) {
            'upcoming' => (clone $query)
                ->where('status', 'pending')
                ->where('scheduled_date', '>=', now()->toDateString())
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->get(),
            'past'     => (clone $query)
                ->where(fn ($q) => $q
                    ->where('scheduled_date', '<', now()->toDateString())
                    ->orWhere('status', 'completed')
                )
                ->orderByDesc('scheduled_date')
                ->limit(50)
                ->get(),
            default    => (clone $query)
                ->orderByDesc('scheduled_date')
                ->get(),
        };

        return view('livewire.servant.scheduled-visit-list', compact('scheduledVisits'));
    }
}
