<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Livewire\WebApp\Concerns\ManagesServiceGroups;
use App\Models\ServiceGroup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('web-app.layouts.app')]
class ServiceGroupProfilePage extends Component
{
    use WithFileUploads;
    use ManagesServiceGroups {
        saveServiceGroup as traitSaveServiceGroup;
    }

    #[Locked]
    public ServiceGroup $serviceGroup;

    public function mount(ServiceGroup $serviceGroup): void
    {
        $user = auth()->user();
        abort_unless($user->can('view', $serviceGroup), 403);
        $this->serviceGroup = $serviceGroup->load([
            'leader',
            'serviceLeader',
            'servants',
            'beneficiaries',
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();

        return view('livewire.web-app.service-group-profile-page', [
            'serviceGroupLeaderOptions' => $this->serviceGroupLeaderOptions($user),
            'serviceGroupServiceLeaderOptions' => $this->serviceGroupServiceLeaderOptions($user),
        ]);
    }

    public function saveServiceGroup(): void
    {
        $this->traitSaveServiceGroup();
        $this->redirect(route('app.service-group-profile', $this->serviceGroup->id), navigate: true);
    }
}
