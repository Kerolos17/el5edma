<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Livewire\WebApp\Concerns\ManagesVisits;
use App\Models\Visit;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('web-app.layouts.app')]
class VisitProfilePage extends Component
{
    use ManagesVisits;
    use WithFileUploads;

    #[Locked]
    public Visit $visit;

    public function mount(Visit $visit): void
    {
        $user = auth()->user();
        abort_unless($user->can('view', $visit), 403);
        $this->visit = $visit->load([
            'beneficiary.serviceGroup',
            'createdBy',
            'resolvedBy',
            'servants',
        ]);
    }

    public function render(): View
    {
        return view('livewire.web-app.visit-profile-page', [
            'beneficiaryOptions' => $this->beneficiaryOptionsQuery()
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'code']),
            'visitTypeOptions'         => $this->visitTypeOptions(),
            'beneficiaryStatusOptions' => $this->beneficiaryStatusOptions(),
        ]);
    }

    private function beneficiaryOptionsQuery(): Builder
    {
        return WebAppScope::beneficiaries(auth()->user());
    }

    private function visitTypeOptions(): array
    {
        return [
            'home_visit'     => __('visits.home_visit'),
            'phone_call'     => __('visits.phone_call'),
            'church_meeting' => __('visits.church_meeting'),
        ];
    }

    private function beneficiaryStatusOptions(): array
    {
        return [
            'great'        => __('visits.great'),
            'good'         => __('visits.good'),
            'needs_follow' => __('visits.needs_follow'),
            'critical'     => __('visits.critical'),
        ];
    }
}
