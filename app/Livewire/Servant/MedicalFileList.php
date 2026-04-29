<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\Beneficiary;
use App\Models\MedicalFile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('servant.layouts.app')]
#[Title('الملفات الطبية')]
class MedicalFileList extends Component
{
    #[Url(except: 'all')]
    public string $filter = 'all';

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();

        $ownedBeneficiaryIds = Beneficiary::query()
            ->where(fn ($q) => $q
                ->where('assigned_servant_id', $user->id)
                ->when(
                    $user->service_group_id,
                    fn ($q2) => $q2->orWhere('service_group_id', $user->service_group_id),
                )
            )
            ->pluck('id');

        $medicalFiles = MedicalFile::whereIn('beneficiary_id', $ownedBeneficiaryIds)
            ->with('beneficiary', 'uploadedBy')
            ->when($this->filter !== 'all', fn ($q) => $q->where('file_type', $this->filter))
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('livewire.servant.medical-file-list', compact('medicalFiles'));
    }
}
