<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Livewire\WebApp\Concerns\ManagesBeneficiaries;
use App\Livewire\WebApp\Concerns\ManagesMedicalFiles;
use App\Livewire\WebApp\Concerns\ManagesPrayerRequests;
use App\Livewire\WebApp\Concerns\ManagesResourceListing;
use App\Livewire\WebApp\Concerns\ManagesScheduledVisits;
use App\Livewire\WebApp\Concerns\ManagesServiceGroups;
use App\Livewire\WebApp\Concerns\ManagesUsers;
use App\Livewire\WebApp\Concerns\ManagesVisits;
use App\Models\ServiceGroup;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('web-app.layouts.app')]
class PlaceholderPage extends Component
{
    use ManagesBeneficiaries;
    use ManagesMedicalFiles;
    use ManagesPrayerRequests;
    use ManagesResourceListing;
    use ManagesScheduledVisits;
    use ManagesServiceGroups;
    use ManagesUsers;
    use ManagesVisits;
    use WithFileUploads;
    use WithPagination;

    public string $section;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $filter = 'all';

    public function mount(string $section): void
    {
        $this->section = $section;

        match ($section) {
            'users'          => abort_unless(auth()->user()->can('viewAny', User::class), 403),
            'service-groups' => abort_unless(auth()->user()->can('viewAny', ServiceGroup::class), 403),
            default          => null,
        };
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }
}
