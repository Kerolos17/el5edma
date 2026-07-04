<?php

declare(strict_types=1);

namespace App\Livewire\WebApp\Concerns;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait ManagesBeneficiaries
{
    public bool $showBeneficiaryForm = false;

    public ?int $editingBeneficiaryId = null;

    public mixed $beneficiaryPhoto = null;

    public string $beneficiaryFullName = '';

    public string $beneficiaryBirthDate = '';

    public string $beneficiaryGender = '';

    public string $beneficiaryRecordStatus = 'active';

    public string $beneficiaryPhone = '';

    public string $beneficiaryWhatsapp = '';

    public string $beneficiaryFacebookUrl = '';

    public string $beneficiaryInstagramUrl = '';

    public string $beneficiaryGuardianName = '';

    public string $beneficiaryGuardianPhone = '';

    public string $beneficiaryGuardianRelation = '';

    public string $beneficiaryFatherStatus = '';

    public string $beneficiaryFatherDeathDate = '';

    public string $beneficiaryMotherStatus = '';

    public string $beneficiaryMotherDeathDate = '';

    public ?int $beneficiarySiblingsCount = null;

    public string $beneficiarySiblingsNote = '';

    public string $beneficiaryFinancialStatus = '';

    public string $beneficiaryFinancialNotes = '';

    public string $beneficiaryAddressText = '';

    public string $beneficiaryArea = '';

    public string $beneficiaryGovernorate = '';

    public string $beneficiaryGoogleMapsUrl = '';

    public ?int $beneficiaryServiceGroupId = null;

    public ?int $beneficiaryAssignedServantId = null;

    public string $beneficiaryDisabilityType = '';

    public string $beneficiaryDisabilityDegree = '';

    public string $beneficiaryDoctorName = '';

    public string $beneficiaryHospitalName = '';

    public string $beneficiaryLastMedicalUpdate = '';

    public string $beneficiaryHealthStatus = '';

    public string $beneficiaryMedicalNotes = '';

    public function updatedBeneficiaryServiceGroupId(): void
    {
        $this->beneficiaryAssignedServantId = null;
    }

    public function openBeneficiaryForm(?int $beneficiaryId = null): void
    {
        $actor = auth()->user();

        $this->resetBeneficiaryForm();

        if ($beneficiaryId === null) {
            abort_unless($actor->can('create', Beneficiary::class), 403);

            $this->beneficiaryServiceGroupId = $this->defaultBeneficiaryServiceGroupId($actor);
            $this->showBeneficiaryForm       = true;

            return;
        }

        $record = WebAppScope::beneficiaries($actor)->whereKey($beneficiaryId)->firstOrFail();

        abort_unless($actor->can('update', $record), 403);

        $this->editingBeneficiaryId         = $record->id;
        $this->beneficiaryPhoto             = null;
        $this->beneficiaryFullName          = $record->full_name;
        $this->beneficiaryBirthDate         = $record->birth_date?->toDateString() ?? '';
        $this->beneficiaryGender            = (string) ($record->gender ?? '');
        $this->beneficiaryRecordStatus      = $record->status ?: 'active';
        $this->beneficiaryPhone             = (string) ($record->phone ?? '');
        $this->beneficiaryWhatsapp          = (string) ($record->whatsapp ?? '');
        $this->beneficiaryFacebookUrl       = (string) ($record->facebook_url ?? '');
        $this->beneficiaryInstagramUrl      = (string) ($record->instagram_url ?? '');
        $this->beneficiaryGuardianName      = (string) ($record->guardian_name ?? '');
        $this->beneficiaryGuardianPhone     = (string) ($record->guardian_phone ?? '');
        $this->beneficiaryGuardianRelation  = (string) ($record->guardian_relation ?? '');
        $this->beneficiaryFatherStatus      = (string) ($record->father_status ?? '');
        $this->beneficiaryFatherDeathDate   = $record->father_death_date?->toDateString() ?? '';
        $this->beneficiaryMotherStatus      = (string) ($record->mother_status ?? '');
        $this->beneficiaryMotherDeathDate   = $record->mother_death_date?->toDateString() ?? '';
        $this->beneficiarySiblingsCount     = $record->siblings_count;
        $this->beneficiarySiblingsNote      = (string) ($record->siblings_note ?? '');
        $this->beneficiaryFinancialStatus   = (string) ($record->financial_status ?? '');
        $this->beneficiaryFinancialNotes    = (string) ($record->financial_notes ?? '');
        $this->beneficiaryAddressText       = (string) ($record->address_text ?? '');
        $this->beneficiaryArea              = (string) ($record->area ?? '');
        $this->beneficiaryGovernorate       = (string) ($record->governorate ?? '');
        $this->beneficiaryGoogleMapsUrl     = (string) ($record->google_maps_url ?? '');
        $this->beneficiaryServiceGroupId    = $record->service_group_id;
        $this->beneficiaryAssignedServantId = $record->assigned_servant_id;
        $this->beneficiaryDisabilityType    = (string) ($record->disability_type ?? '');
        $this->beneficiaryDisabilityDegree  = (string) ($record->disability_degree ?? '');
        $this->beneficiaryDoctorName        = (string) ($record->doctor_name ?? '');
        $this->beneficiaryHospitalName      = (string) ($record->hospital_name ?? '');
        $this->beneficiaryLastMedicalUpdate = $record->last_medical_update?->toDateString() ?? '';
        $this->beneficiaryHealthStatus      = (string) ($record->health_status ?? '');
        $this->beneficiaryMedicalNotes      = (string) ($record->medical_notes ?? '');
        $this->showBeneficiaryForm          = true;
    }

    public function closeBeneficiaryForm(): void
    {
        $this->showBeneficiaryForm = false;
        $this->resetBeneficiaryForm();
    }

    public function deleteBeneficiary(int $id): void
    {
        $record = WebAppScope::beneficiaries(auth()->user())->whereKey($id)->firstOrFail();
        abort_unless(auth()->user()->can('delete', $record), 403);

        if ($record->photo) {
            Storage::disk('public')->delete($record->photo);
        }

        $record->delete();
        $this->dispatch('toast', message: __('web_app.toasts.beneficiary_deleted'), type: 'success');
    }

    public function saveBeneficiary(): void
    {
        $actor  = auth()->user();
        $record = $this->editingBeneficiaryId
            ? WebAppScope::beneficiaries($actor)->whereKey($this->editingBeneficiaryId)->firstOrFail()
            : null;

        abort_unless($record ? $actor->can('update', $record) : $actor->can('create', Beneficiary::class), 403);

        $data = $this->validate([
            'beneficiaryPhoto'             => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,bmp'],
            'beneficiaryFullName'          => ['required', 'string', 'max:255'],
            'beneficiaryBirthDate'         => ['required', 'date', 'before_or_equal:today'],
            'beneficiaryGender'            => ['required', Rule::in(['male', 'female'])],
            'beneficiaryRecordStatus'      => ['required', Rule::in(['active', 'inactive', 'moved', 'deceased'])],
            'beneficiaryPhone'             => ['nullable', 'string', 'max:20'],
            'beneficiaryWhatsapp'          => ['nullable', 'string', 'max:20'],
            'beneficiaryFacebookUrl'       => ['nullable', 'url', 'max:255'],
            'beneficiaryInstagramUrl'      => ['nullable', 'url', 'max:255'],
            'beneficiaryGuardianName'      => ['nullable', 'string', 'max:255'],
            'beneficiaryGuardianPhone'     => ['nullable', 'string', 'max:20'],
            'beneficiaryGuardianRelation'  => ['nullable', 'string', 'max:50'],
            'beneficiaryFatherStatus'      => ['nullable', Rule::in(['alive', 'deceased', 'unknown'])],
            'beneficiaryFatherDeathDate'   => ['nullable', 'date', 'before_or_equal:today'],
            'beneficiaryMotherStatus'      => ['nullable', Rule::in(['alive', 'deceased', 'unknown'])],
            'beneficiaryMotherDeathDate'   => ['nullable', 'date', 'before_or_equal:today'],
            'beneficiarySiblingsCount'     => ['nullable', 'integer', 'min:0', 'max:30'],
            'beneficiarySiblingsNote'      => ['nullable', 'string', 'max:255'],
            'beneficiaryFinancialStatus'   => ['nullable', Rule::in(['good', 'moderate', 'poor', 'very_poor'])],
            'beneficiaryFinancialNotes'    => ['nullable', 'string', 'max:1000'],
            'beneficiaryAddressText'       => ['nullable', 'string', 'max:1000'],
            'beneficiaryArea'              => ['nullable', 'string', 'max:100'],
            'beneficiaryGovernorate'       => ['nullable', 'string', 'max:100'],
            'beneficiaryGoogleMapsUrl'     => ['nullable', 'url', 'max:500'],
            'beneficiaryServiceGroupId'    => ['required', 'integer'],
            'beneficiaryAssignedServantId' => ['nullable', 'integer'],
            'beneficiaryDisabilityType'    => ['nullable', 'string', 'max:100'],
            'beneficiaryDisabilityDegree'  => ['nullable', Rule::in(['mild', 'moderate', 'severe'])],
            'beneficiaryDoctorName'        => ['nullable', 'string', 'max:100'],
            'beneficiaryHospitalName'      => ['nullable', 'string', 'max:100'],
            'beneficiaryLastMedicalUpdate' => ['nullable', 'date', 'before_or_equal:today'],
            'beneficiaryHealthStatus'      => ['nullable', 'string', 'max:1000'],
            'beneficiaryMedicalNotes'      => ['nullable', 'string', 'max:2000'],
        ]);

        $serviceGroupId    = (int) $data['beneficiaryServiceGroupId'];
        $assignedServantId = $data['beneficiaryAssignedServantId'] ? (int) $data['beneficiaryAssignedServantId'] : null;

        $this->ensureBeneficiaryAssignmentAllowed($actor, $serviceGroupId, $assignedServantId);

        $payload = [
            'full_name'           => $data['beneficiaryFullName'],
            'birth_date'          => $data['beneficiaryBirthDate'],
            'gender'              => $data['beneficiaryGender'],
            'status'              => $data['beneficiaryRecordStatus'],
            'phone'               => $data['beneficiaryPhone'] ?: null,
            'whatsapp'            => $data['beneficiaryWhatsapp'] ?: null,
            'facebook_url'        => $data['beneficiaryFacebookUrl'] ?: null,
            'instagram_url'       => $data['beneficiaryInstagramUrl'] ?: null,
            'guardian_name'       => $data['beneficiaryGuardianName'] ?: null,
            'guardian_phone'      => $data['beneficiaryGuardianPhone'] ?: null,
            'guardian_relation'   => $data['beneficiaryGuardianRelation'] ?: null,
            'father_status'       => $data['beneficiaryFatherStatus'] ?: null,
            'father_death_date'   => $data['beneficiaryFatherDeathDate'] ?: null,
            'mother_status'       => $data['beneficiaryMotherStatus'] ?: null,
            'mother_death_date'   => $data['beneficiaryMotherDeathDate'] ?: null,
            'siblings_count'      => $data['beneficiarySiblingsCount'] ?: null,
            'siblings_note'       => $data['beneficiarySiblingsNote'] ?: null,
            'financial_status'    => $data['beneficiaryFinancialStatus'] ?: null,
            'financial_notes'     => $data['beneficiaryFinancialNotes'] ?: null,
            'address_text'        => $data['beneficiaryAddressText'] ?: null,
            'area'                => $data['beneficiaryArea'] ?: null,
            'governorate'         => $data['beneficiaryGovernorate'] ?: null,
            'google_maps_url'     => $data['beneficiaryGoogleMapsUrl'] ?: null,
            'service_group_id'    => $serviceGroupId,
            'assigned_servant_id' => $assignedServantId,
            'disability_type'     => $data['beneficiaryDisabilityType'] ?: null,
            'disability_degree'   => $data['beneficiaryDisabilityDegree'] ?: null,
            'doctor_name'         => $data['beneficiaryDoctorName'] ?: null,
            'hospital_name'       => $data['beneficiaryHospitalName'] ?: null,
            'last_medical_update' => $data['beneficiaryLastMedicalUpdate'] ?: null,
            'health_status'       => $data['beneficiaryHealthStatus'] ?: null,
            'medical_notes'       => $data['beneficiaryMedicalNotes'] ?: null,
        ];

        if ($this->beneficiaryPhoto instanceof TemporaryUploadedFile) {
            if ($record?->photo) {
                Storage::disk('public')->delete($record->photo);
            }

            $payload['photo'] = $this->beneficiaryPhoto->store('beneficiaries/photos', 'public');
        }

        if ($record) {
            $record->update($payload);
            $message = __('web_app.toasts.beneficiary_updated');
        } else {
            Beneficiary::create($payload + ['created_by' => $actor->id]);
            $message = __('web_app.toasts.beneficiary_created');
        }

        $this->showBeneficiaryForm = false;
        $this->resetBeneficiaryForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    private function resetBeneficiaryForm(): void
    {
        $this->reset([
            'editingBeneficiaryId',
            'beneficiaryPhoto',
            'beneficiaryFullName',
            'beneficiaryBirthDate',
            'beneficiaryGender',
            'beneficiaryRecordStatus',
            'beneficiaryPhone',
            'beneficiaryWhatsapp',
            'beneficiaryFacebookUrl',
            'beneficiaryInstagramUrl',
            'beneficiaryGuardianName',
            'beneficiaryGuardianPhone',
            'beneficiaryGuardianRelation',
            'beneficiaryFatherStatus',
            'beneficiaryFatherDeathDate',
            'beneficiaryMotherStatus',
            'beneficiaryMotherDeathDate',
            'beneficiarySiblingsCount',
            'beneficiarySiblingsNote',
            'beneficiaryFinancialStatus',
            'beneficiaryFinancialNotes',
            'beneficiaryAddressText',
            'beneficiaryArea',
            'beneficiaryGovernorate',
            'beneficiaryGoogleMapsUrl',
            'beneficiaryServiceGroupId',
            'beneficiaryAssignedServantId',
            'beneficiaryDisabilityType',
            'beneficiaryDisabilityDegree',
            'beneficiaryDoctorName',
            'beneficiaryHospitalName',
            'beneficiaryLastMedicalUpdate',
            'beneficiaryHealthStatus',
            'beneficiaryMedicalNotes',
        ]);

        $this->beneficiaryRecordStatus = 'active';
    }

    private function beneficiaryServiceGroupOptions(User $actor): Collection
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

    private function beneficiaryServantOptions(User $actor): Collection
    {
        if (! $this->beneficiaryServiceGroupId) {
            return collect();
        }

        $serviceGroupId = (int) $this->beneficiaryServiceGroupId;

        if (! in_array($serviceGroupId, $this->allowedBeneficiaryServiceGroupIds($actor), true)) {
            return collect();
        }

        return User::query()
            ->where('role', UserRole::Servant)
            ->where('is_active', true)
            ->where('service_group_id', $serviceGroupId)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    private function defaultBeneficiaryServiceGroupId(User $actor): ?int
    {
        if ($actor->role === UserRole::FamilyLeader) {
            return $actor->service_group_id;
        }

        $firstServiceGroupId = $this->beneficiaryServiceGroupOptions($actor)->keys()->first();

        return $firstServiceGroupId ? (int) $firstServiceGroupId : null;
    }

    private function allowedBeneficiaryServiceGroupIds(User $actor): array
    {
        return $this->beneficiaryServiceGroupOptions($actor)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function ensureBeneficiaryAssignmentAllowed(User $actor, int $serviceGroupId, ?int $assignedServantId): void
    {
        if (! in_array($serviceGroupId, $this->allowedBeneficiaryServiceGroupIds($actor), true)) {
            throw ValidationException::withMessages([
                'beneficiaryServiceGroupId' => __('web_app.validation.service_group_out_of_scope'),
            ]);
        }

        if ($assignedServantId === null) {
            return;
        }

        $servantExists = User::query()
            ->whereKey($assignedServantId)
            ->where('role', UserRole::Servant)
            ->where('is_active', true)
            ->where('service_group_id', $serviceGroupId)
            ->exists();

        if (! $servantExists) {
            throw ValidationException::withMessages([
                'beneficiaryAssignedServantId' => __('web_app.validation.servant_must_match_group'),
            ]);
        }
    }
}
