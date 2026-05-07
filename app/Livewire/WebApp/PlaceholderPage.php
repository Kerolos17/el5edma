<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\PrayerRequest;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('web-app.layouts.app')]
class PlaceholderPage extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $section;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $filter = 'all';

    public bool $showVisitForm = false;

    public ?int $editingVisitId = null;

    public ?int $visitBeneficiaryId = null;

    public string $visitType = '';

    public string $visitDate = '';

    public ?int $durationMinutes = null;

    public string $beneficiaryStatus = '';

    public string $visitFeedback = '';

    public bool $isCritical = false;

    public bool $needsFamilyLeader = false;

    public bool $needsServiceLeader = false;

    public bool $showPrayerForm = false;

    public ?int $prayerBeneficiaryId = null;

    public string $prayerTitle = '';

    public string $prayerBody = '';

    public bool $showScheduledVisitForm = false;

    public ?int $editingScheduledVisitId = null;

    public ?int $scheduledVisitBeneficiaryId = null;

    public array $scheduledVisitAssignedServantIds = [];

    public string $scheduledVisitDate = '';

    public string $scheduledVisitTime = '';

    public string $scheduledVisitNotes = '';

    public ?int $scheduledVisitContextId = null;

    public bool $showUserForm = false;

    public ?int $editingUserId = null;

    public string $userName = '';

    public string $userEmail = '';

    public string $userPhone = '';

    public string $userPassword = '';

    public string $userRole = '';

    public ?int $userServiceGroupId = null;

    public string $userLocale = 'ar';

    public bool $userIsActive = true;

    public bool $showServiceGroupForm = false;

    public ?int $editingServiceGroupId = null;

    public string $serviceGroupName = '';

    public string $serviceGroupDescription = '';

    public ?int $serviceGroupLeaderId = null;

    public ?int $serviceGroupServiceLeaderId = null;

    public bool $serviceGroupIsActive = true;

    public bool $showBeneficiaryForm = false;

    public ?int $editingBeneficiaryId = null;

    public string $beneficiaryFullName = '';

    public string $beneficiaryBirthDate = '';

    public string $beneficiaryGender = '';

    public string $beneficiaryRecordStatus = 'active';

    public string $beneficiaryPhone = '';

    public string $beneficiaryWhatsapp = '';

    public string $beneficiaryGuardianName = '';

    public string $beneficiaryGuardianPhone = '';

    public string $beneficiaryAddressText = '';

    public ?int $beneficiaryServiceGroupId = null;

    public ?int $beneficiaryAssignedServantId = null;

    public bool $showMedicalFileForm = false;

    public ?int $medicalFileBeneficiaryId = null;

    public string $medicalFileTitle = '';

    public string $medicalFileType = 'report';

    public mixed $medicalUploadedFile = null;

    public function mount(string $section): void
    {
        $this->section = $section;

        match ($section) {
            'users' => abort_unless(auth()->user()->can('viewAny', User::class), 403),
            'service-groups' => abort_unless(auth()->user()->can('viewAny', ServiceGroup::class), 403),
            default => null,
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

    public function updatedScheduledVisitBeneficiaryId(): void
    {
        if ($this->showScheduledVisitForm) {
            $this->scheduledVisitAssignedServantIds = [];
        }
    }

    public function updatedUserRole(): void
    {
        $role = UserRole::tryFrom($this->userRole);

        if ($role?->isAdminLevel()) {
            $this->userServiceGroupId = null;
        }
    }

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
            $this->showBeneficiaryForm = true;

            return;
        }

        $record = WebAppScope::beneficiaries($actor)->whereKey($beneficiaryId)->firstOrFail();

        abort_unless($actor->can('update', $record), 403);

        $this->editingBeneficiaryId = $record->id;
        $this->beneficiaryFullName = $record->full_name;
        $this->beneficiaryBirthDate = $record->birth_date?->toDateString() ?? '';
        $this->beneficiaryGender = (string) ($record->gender ?? '');
        $this->beneficiaryRecordStatus = $record->status ?: 'active';
        $this->beneficiaryPhone = (string) ($record->phone ?? '');
        $this->beneficiaryWhatsapp = (string) ($record->whatsapp ?? '');
        $this->beneficiaryGuardianName = (string) ($record->guardian_name ?? '');
        $this->beneficiaryGuardianPhone = (string) ($record->guardian_phone ?? '');
        $this->beneficiaryAddressText = (string) ($record->address_text ?? '');
        $this->beneficiaryServiceGroupId = $record->service_group_id;
        $this->beneficiaryAssignedServantId = $record->assigned_servant_id;
        $this->showBeneficiaryForm = true;
    }

    public function closeBeneficiaryForm(): void
    {
        $this->showBeneficiaryForm = false;
        $this->resetBeneficiaryForm();
    }

    public function saveBeneficiary(): void
    {
        $actor = auth()->user();
        $record = $this->editingBeneficiaryId
            ? WebAppScope::beneficiaries($actor)->whereKey($this->editingBeneficiaryId)->firstOrFail()
            : null;

        abort_unless($record ? $actor->can('update', $record) : $actor->can('create', Beneficiary::class), 403);

        $data = $this->validate([
            'beneficiaryFullName' => ['required', 'string', 'max:255'],
            'beneficiaryBirthDate' => ['required', 'date', 'before_or_equal:today'],
            'beneficiaryGender' => ['required', Rule::in(['male', 'female'])],
            'beneficiaryRecordStatus' => ['required', Rule::in(['active', 'inactive', 'moved', 'deceased'])],
            'beneficiaryPhone' => ['nullable', 'string', 'max:20'],
            'beneficiaryWhatsapp' => ['nullable', 'string', 'max:20'],
            'beneficiaryGuardianName' => ['nullable', 'string', 'max:255'],
            'beneficiaryGuardianPhone' => ['nullable', 'string', 'max:20'],
            'beneficiaryAddressText' => ['nullable', 'string', 'max:1000'],
            'beneficiaryServiceGroupId' => ['required', 'integer'],
            'beneficiaryAssignedServantId' => ['nullable', 'integer'],
        ]);

        $serviceGroupId = (int) $data['beneficiaryServiceGroupId'];
        $assignedServantId = $data['beneficiaryAssignedServantId'] ? (int) $data['beneficiaryAssignedServantId'] : null;

        $this->ensureBeneficiaryAssignmentAllowed($actor, $serviceGroupId, $assignedServantId);

        $payload = [
            'full_name' => $data['beneficiaryFullName'],
            'birth_date' => $data['beneficiaryBirthDate'],
            'gender' => $data['beneficiaryGender'],
            'status' => $data['beneficiaryRecordStatus'],
            'phone' => $data['beneficiaryPhone'] ?: null,
            'whatsapp' => $data['beneficiaryWhatsapp'] ?: null,
            'guardian_name' => $data['beneficiaryGuardianName'] ?: null,
            'guardian_phone' => $data['beneficiaryGuardianPhone'] ?: null,
            'address_text' => $data['beneficiaryAddressText'] ?: null,
            'service_group_id' => $serviceGroupId,
            'assigned_servant_id' => $assignedServantId,
        ];

        if ($record) {
            $record->update($payload);
            $message = 'تم تحديث بيانات المخدوم بنجاح';
        } else {
            Beneficiary::create($payload + ['created_by' => $actor->id]);
            $message = 'تم إضافة المخدوم بنجاح';
        }

        $this->showBeneficiaryForm = false;
        $this->resetBeneficiaryForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function openMedicalFileForm(?int $beneficiaryId = null): void
    {
        abort_unless(auth()->user()->can('create', MedicalFile::class), 403);

        $this->resetMedicalFileForm();

        if ($beneficiaryId !== null && $this->beneficiaryOptionsQuery()->whereKey($beneficiaryId)->exists()) {
            $this->medicalFileBeneficiaryId = $beneficiaryId;
        }

        $this->showMedicalFileForm = true;
    }

    public function closeMedicalFileForm(): void
    {
        $this->showMedicalFileForm = false;
        $this->resetMedicalFileForm();
    }

    public function saveMedicalFile(): void
    {
        abort_unless(auth()->user()->can('create', MedicalFile::class), 403);

        $data = $this->validate([
            'medicalFileBeneficiaryId' => ['required', 'integer'],
            'medicalFileTitle' => ['required', 'string', 'max:255'],
            'medicalFileType' => ['required', Rule::in(['report', 'image', 'document'])],
            'medicalUploadedFile' => [
                'required',
                'file',
                'max:5120',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
            ],
        ]);

        $beneficiary = $this->beneficiaryOptionsQuery()
            ->whereKey($data['medicalFileBeneficiaryId'])
            ->firstOrFail();

        $path = $this->medicalUploadedFile instanceof TemporaryUploadedFile
            ? $this->medicalUploadedFile->store('medical/' . $beneficiary->id, 'private')
            : null;

        if (! $path) {
            throw ValidationException::withMessages([
                'medicalUploadedFile' => 'تعذر رفع الملف. حاول مرة أخرى.',
            ]);
        }

        MedicalFile::create([
            'beneficiary_id' => $beneficiary->id,
            'file_path' => $path,
            'file_type' => $data['medicalFileType'],
            'title' => $data['medicalFileTitle'],
            'uploaded_by' => auth()->id(),
        ]);

        $this->showMedicalFileForm = false;
        $this->resetMedicalFileForm();
        $this->dispatch('toast', message: 'تم رفع الملف الطبي بنجاح', type: 'success');
    }

    public function openUserForm(?int $userId = null): void
    {
        $actor = auth()->user();

        $this->resetUserForm();

        if ($userId === null) {
            abort_unless($actor->can('create', User::class), 403);

            $this->userRole = $this->userRoleOptionsForActor($actor)->keys()->first() ?? UserRole::Servant->value;
            $firstServiceGroupId = $this->userServiceGroupOptionsForActor($actor)->keys()->first();
            $this->userServiceGroupId = $firstServiceGroupId ? (int) $firstServiceGroupId : null;
            $this->showUserForm = true;

            return;
        }

        $record = WebAppScope::users($actor)->whereKey($userId)->firstOrFail();

        abort_unless($actor->can('update', $record), 403);

        $this->editingUserId = $record->id;
        $this->userName = $record->name;
        $this->userEmail = $record->email;
        $this->userPhone = (string) ($record->phone ?? '');
        $this->userRole = $record->role->value;
        $this->userServiceGroupId = $record->service_group_id;
        $this->userLocale = $record->locale ?: 'ar';
        $this->userIsActive = (bool) $record->is_active;
        $this->showUserForm = true;
    }

    public function closeUserForm(): void
    {
        $this->showUserForm = false;
        $this->resetUserForm();
    }

    public function saveUser(): void
    {
        $actor = auth()->user();
        $record = $this->editingUserId
            ? WebAppScope::users($actor)->whereKey($this->editingUserId)->firstOrFail()
            : null;

        abort_unless($record ? $actor->can('update', $record) : $actor->can('create', User::class), 403);

        $roleOptions = $record && $record->id === $actor->id
            ? [$record->role->value]
            : $this->userRoleOptionsForActor($actor)->keys()->all();

        $data = $this->validate([
            'userName' => ['required', 'string', 'max:255'],
            'userEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($record?->id)],
            'userPhone' => ['nullable', 'string', 'max:20'],
            'userPassword' => [$record ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'userRole' => ['required', Rule::in($roleOptions)],
            'userServiceGroupId' => ['nullable', 'integer'],
            'userLocale' => ['required', Rule::in(['ar', 'en'])],
            'userIsActive' => ['boolean'],
        ]);

        $role = UserRole::from($data['userRole']);
        $serviceGroupId = $data['userServiceGroupId'] ? (int) $data['userServiceGroupId'] : null;

        if (in_array($role, [UserRole::FamilyLeader, UserRole::Servant], true) && $serviceGroupId === null) {
            throw ValidationException::withMessages([
                'userServiceGroupId' => 'يجب اختيار مجموعة خدمة لهذا الدور.',
            ]);
        }

        $this->ensureUserAssignmentAllowed($actor, $record, $role, $serviceGroupId);

        $payload = [
            'name' => $data['userName'],
            'email' => $data['userEmail'],
            'phone' => $data['userPhone'] ?: null,
            'locale' => $data['userLocale'],
        ];

        if ($record && $record->id === $actor->id) {
            $payload['is_active'] = true;
        } else {
            $payload['role'] = $role;
            $payload['service_group_id'] = $role->isAdminLevel() ? null : $serviceGroupId;
            $payload['is_active'] = (bool) $data['userIsActive'];
        }

        if ($data['userPassword']) {
            $payload['password'] = $data['userPassword'];
        }

        if ($record) {
            $record->update($payload);
            $message = 'تم تحديث المستخدم بنجاح';
        } else {
            User::create($payload);
            $message = 'تم إنشاء المستخدم بنجاح';
        }

        $this->showUserForm = false;
        $this->resetUserForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function toggleUserActive(int $userId): void
    {
        $actor = auth()->user();
        $record = WebAppScope::users($actor)->whereKey($userId)->firstOrFail();

        abort_unless($actor->id !== $record->id && $actor->can('update', $record), 403);

        $this->ensureUserAssignmentAllowed($actor, $record, $record->role, $record->service_group_id);

        $record->update(['is_active' => ! $record->is_active]);
        $this->dispatch('toast', message: $record->is_active ? 'تم تفعيل المستخدم' : 'تم تعطيل المستخدم', type: 'success');
    }

    public function openServiceGroupForm(?int $serviceGroupId = null): void
    {
        $actor = auth()->user();

        $this->resetServiceGroupForm();

        if ($serviceGroupId === null) {
            abort_unless($actor->can('create', ServiceGroup::class), 403);

            $this->serviceGroupServiceLeaderId = $actor->isServiceLeader() ? $actor->id : null;
            $this->showServiceGroupForm = true;

            return;
        }

        $record = WebAppScope::serviceGroups($actor)->whereKey($serviceGroupId)->firstOrFail();

        abort_unless($actor->can('update', $record), 403);

        $this->editingServiceGroupId = $record->id;
        $this->serviceGroupName = $record->name;
        $this->serviceGroupDescription = (string) ($record->description ?? '');
        $this->serviceGroupLeaderId = $record->leader_id;
        $this->serviceGroupServiceLeaderId = $record->service_leader_id;
        $this->serviceGroupIsActive = (bool) $record->is_active;
        $this->showServiceGroupForm = true;
    }

    public function closeServiceGroupForm(): void
    {
        $this->showServiceGroupForm = false;
        $this->resetServiceGroupForm();
    }

    public function saveServiceGroup(): void
    {
        $actor = auth()->user();
        $record = $this->editingServiceGroupId
            ? WebAppScope::serviceGroups($actor)->whereKey($this->editingServiceGroupId)->firstOrFail()
            : null;

        abort_unless($record ? $actor->can('update', $record) : $actor->can('create', ServiceGroup::class), 403);

        $data = $this->validate([
            'serviceGroupName' => ['required', 'string', 'max:255'],
            'serviceGroupDescription' => ['nullable', 'string', 'max:1000'],
            'serviceGroupLeaderId' => ['nullable', 'integer'],
            'serviceGroupServiceLeaderId' => ['nullable', 'integer'],
            'serviceGroupIsActive' => ['boolean'],
        ]);

        $leaderId = $data['serviceGroupLeaderId'] ? (int) $data['serviceGroupLeaderId'] : null;
        $serviceLeaderId = $actor->isServiceLeader()
            ? $actor->id
            : ($data['serviceGroupServiceLeaderId'] ? (int) $data['serviceGroupServiceLeaderId'] : null);

        $this->ensureServiceGroupLeadersAllowed($actor, $record, $leaderId, $serviceLeaderId);

        $payload = [
            'name' => $data['serviceGroupName'],
            'description' => $data['serviceGroupDescription'] ?: null,
            'leader_id' => $leaderId,
            'service_leader_id' => $serviceLeaderId,
            'is_active' => (bool) $data['serviceGroupIsActive'],
        ];

        if ($record) {
            $record->update($payload);
            $this->syncInactiveServiceGroupBeneficiaries($record);
            $message = 'تم تحديث مجموعة الخدمة بنجاح';
        } else {
            $record = ServiceGroup::create($payload);
            $message = 'تم إنشاء مجموعة الخدمة بنجاح';
        }

        $this->showServiceGroupForm = false;
        $this->resetServiceGroupForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function toggleServiceGroupActive(int $serviceGroupId): void
    {
        $actor = auth()->user();
        $record = WebAppScope::serviceGroups($actor)->whereKey($serviceGroupId)->firstOrFail();

        abort_unless($actor->can('update', $record), 403);

        $record->update(['is_active' => ! $record->is_active]);
        $this->syncInactiveServiceGroupBeneficiaries($record);
        $this->dispatch('toast', message: $record->is_active ? 'تم تفعيل مجموعة الخدمة' : 'تم تعطيل مجموعة الخدمة', type: 'success');
    }

    public function openVisitForm(?int $beneficiaryId = null): void
    {
        abort_unless(auth()->user()->can('create', Visit::class), 403);

        $this->resetVisitForm();
        $this->visitDate = now()->format('Y-m-d\TH:i');

        if ($beneficiaryId !== null && $this->beneficiaryOptionsQuery()->whereKey($beneficiaryId)->exists()) {
            $this->visitBeneficiaryId = $beneficiaryId;
        }

        $this->showVisitForm = true;
    }

    public function closeVisitForm(): void
    {
        $this->showVisitForm = false;
        $this->resetVisitForm();
    }

    public function editVisit(int $visitId): void
    {
        $visit = WebAppScope::visits(auth()->user())
            ->whereKey($visitId)
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $visit), 403);

        $this->resetVisitForm();
        $this->editingVisitId = $visit->id;
        $this->visitBeneficiaryId = $visit->beneficiary_id;
        $this->visitType = (string) ($visit->type ?? '');
        $this->visitDate = $visit->visit_date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->durationMinutes = $visit->duration_minutes;
        $this->beneficiaryStatus = (string) ($visit->beneficiary_status ?? '');
        $this->visitFeedback = (string) ($visit->feedback ?? '');
        $this->isCritical = (bool) $visit->is_critical;
        $this->needsFamilyLeader = (bool) $visit->needs_family_leader;
        $this->needsServiceLeader = (bool) $visit->needs_service_leader;
        $this->showVisitForm = true;
    }

    public function saveVisit(): void
    {
        $visit = $this->editingVisitId
            ? WebAppScope::visits(auth()->user())->whereKey($this->editingVisitId)->firstOrFail()
            : null;

        abort_unless($visit ? auth()->user()->can('update', $visit) : auth()->user()->can('create', Visit::class), 403);

        $data = $this->validate([
            'visitBeneficiaryId'   => ['required', 'integer'],
            'visitType'            => ['required', 'in:home_visit,phone_call,church_meeting'],
            'visitDate'            => ['required', 'date'],
            'beneficiaryStatus'    => ['required', 'in:great,good,needs_follow,critical'],
            'durationMinutes'      => ['nullable', 'integer', 'min:1', 'max:480'],
            'visitFeedback'        => ['nullable', 'string', 'max:2000'],
            'isCritical'           => ['boolean'],
            'needsFamilyLeader'    => ['boolean'],
            'needsServiceLeader'   => ['boolean'],
        ]);

        $beneficiary = $this->beneficiaryOptionsQuery()->whereKey($data['visitBeneficiaryId'])->firstOrFail();

        $payload = [
            'beneficiary_id'       => $beneficiary->id,
            'type'                 => $data['visitType'],
            'visit_date'           => $data['visitDate'],
            'duration_minutes'     => $data['durationMinutes'] ?: null,
            'beneficiary_status'   => $data['beneficiaryStatus'],
            'feedback'             => $data['visitFeedback'] ?: null,
            'is_critical'          => $data['isCritical'],
            'needs_family_leader'  => $data['needsFamilyLeader'],
            'needs_service_leader' => $data['needsServiceLeader'],
        ];

        if ($visit) {
            $visit->update($payload);
        } else {
            $visit = Visit::create($payload + [
                'created_by' => auth()->id(),
            ]);
        }

        if (auth()->user()->isServant()) {
            $visit->servants()->syncWithoutDetaching([auth()->id()]);
        }

        $this->completeLinkedScheduledVisit($visit);

        $message = $this->editingVisitId ? 'تم تحديث الزيارة بنجاح' : 'تم تسجيل الزيارة بنجاح';
        $this->showVisitForm = false;
        $this->resetVisitForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function resolveVisitFollowUp(int $visitId): void
    {
        $visit = WebAppScope::visits(auth()->user())
            ->whereKey($visitId)
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $visit), 403);

        $visit->update([
            'critical_resolved_at' => now(),
            'critical_resolved_by' => auth()->id(),
            'is_critical' => false,
            'needs_family_leader' => false,
            'needs_service_leader' => false,
        ]);

        $this->dispatch('toast', message: 'تم إغلاق متابعة الزيارة', type: 'success');
    }

    public function openPrayerForm(?int $beneficiaryId = null): void
    {
        abort_unless(auth()->user()->can('create', PrayerRequest::class), 403);

        $this->resetPrayerForm();

        if ($beneficiaryId !== null && $this->beneficiaryOptionsQuery()->whereKey($beneficiaryId)->exists()) {
            $this->prayerBeneficiaryId = $beneficiaryId;
        }

        $this->showPrayerForm = true;
    }

    public function closePrayerForm(): void
    {
        $this->showPrayerForm = false;
        $this->resetPrayerForm();
    }

    public function savePrayer(): void
    {
        abort_unless(auth()->user()->can('create', PrayerRequest::class), 403);

        $data = $this->validate([
            'prayerBeneficiaryId' => ['required', 'integer'],
            'prayerTitle'         => ['required', 'string', 'max:255'],
            'prayerBody'          => ['nullable', 'string', 'max:2000'],
        ]);

        $beneficiary = $this->beneficiaryOptionsQuery()->whereKey($data['prayerBeneficiaryId'])->firstOrFail();

        PrayerRequest::create([
            'beneficiary_id' => $beneficiary->id,
            'title'          => $data['prayerTitle'],
            'body'           => $data['prayerBody'] ?: null,
            'status'         => 'open',
            'created_by'     => auth()->id(),
        ]);

        $this->showPrayerForm = false;
        $this->resetPrayerForm();
        $this->dispatch('toast', message: 'تم حفظ طلب الصلاة', type: 'success');
    }

    public function markPrayerAnswered(int $prayerRequestId): void
    {
        $prayerRequest = WebAppScope::prayerRequests(auth()->user())
            ->whereKey($prayerRequestId)
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $prayerRequest), 403);

        if ($prayerRequest->status !== 'answered') {
            $prayerRequest->update([
                'status' => 'answered',
                'answered_at' => now(),
            ]);
        }

        $this->dispatch('toast', message: 'تم تعليم طلب الصلاة كمستجاب', type: 'success');
    }

    public function closePrayerRequest(int $prayerRequestId): void
    {
        $prayerRequest = WebAppScope::prayerRequests(auth()->user())
            ->whereKey($prayerRequestId)
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $prayerRequest), 403);

        $prayerRequest->update([
            'status' => 'closed',
            'answered_at' => null,
        ]);

        $this->dispatch('toast', message: 'تم إغلاق طلب الصلاة', type: 'success');
    }

    public function reopenPrayerRequest(int $prayerRequestId): void
    {
        $prayerRequest = WebAppScope::prayerRequests(auth()->user())
            ->whereKey($prayerRequestId)
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $prayerRequest), 403);

        $prayerRequest->update([
            'status' => 'open',
            'answered_at' => null,
        ]);

        $this->dispatch('toast', message: 'تم إعادة فتح طلب الصلاة', type: 'success');
    }

    public function openScheduledVisitForm(?int $beneficiaryId = null): void
    {
        abort_unless(auth()->user()->can('create', ScheduledVisit::class), 403);

        $this->resetScheduledVisitForm();

        if ($beneficiaryId !== null && $this->beneficiaryOptionsQuery()->whereKey($beneficiaryId)->exists()) {
            $this->scheduledVisitBeneficiaryId = $beneficiaryId;
        }

        $this->scheduledVisitDate = now()->toDateString();
        $this->scheduledVisitFormDefaultAssignee();
        $this->showScheduledVisitForm = true;
    }

    public function closeScheduledVisitForm(): void
    {
        $this->showScheduledVisitForm = false;
        $this->resetScheduledVisitForm();
    }

    public function editScheduledVisit(int $scheduledVisitId): void
    {
        $scheduledVisit = WebAppScope::scheduledVisits(auth()->user())
            ->whereKey($scheduledVisitId)
            ->where('status', 'pending')
            ->firstOrFail();

        abort_unless(auth()->user()->can('update', $scheduledVisit), 403);

        $scheduledVisit->loadMissing('servants');

        $this->resetScheduledVisitForm();
        $this->editingScheduledVisitId = $scheduledVisit->id;
        $this->scheduledVisitBeneficiaryId = $scheduledVisit->beneficiary_id;
        $this->scheduledVisitAssignedServantIds = $scheduledVisit->servants
            ->pluck('id')
            ->whenEmpty(fn (Collection $ids) => $scheduledVisit->assigned_servant_id ? $ids->push($scheduledVisit->assigned_servant_id) : $ids)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $this->scheduledVisitDate = $scheduledVisit->scheduled_date?->toDateString() ?? now()->toDateString();
        $this->scheduledVisitTime = substr((string) $scheduledVisit->scheduled_time, 0, 5);
        $this->scheduledVisitNotes = (string) ($scheduledVisit->notes ?? '');
        $this->showScheduledVisitForm = true;
    }

    public function saveScheduledVisit(): void
    {
        $scheduledVisit = $this->editingScheduledVisitId
            ? WebAppScope::scheduledVisits(auth()->user())->whereKey($this->editingScheduledVisitId)->firstOrFail()
            : null;

        abort_unless(
            $scheduledVisit
                ? auth()->user()->can('update', $scheduledVisit)
                : auth()->user()->can('create', ScheduledVisit::class),
            403,
        );
        abort_unless(! $scheduledVisit || $scheduledVisit->status === 'pending', 403);

        $data = $this->validate([
            'scheduledVisitBeneficiaryId' => ['required', 'integer'],
            'scheduledVisitAssignedServantIds' => ['required', 'array', 'min:1'],
            'scheduledVisitAssignedServantIds.*' => ['integer'],
            'scheduledVisitDate' => ['required', 'date', 'after_or_equal:today'],
            'scheduledVisitTime' => ['required', 'date_format:H:i'],
            'scheduledVisitNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $beneficiary = $this->beneficiaryOptionsQuery()->whereKey($data['scheduledVisitBeneficiaryId'])->firstOrFail();
        $assignedServantIds = $this->servantOptionsQuery()
            ->where('service_group_id', $beneficiary->service_group_id)
            ->whereIn('id', $data['scheduledVisitAssignedServantIds'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($assignedServantIds) !== count(array_unique($data['scheduledVisitAssignedServantIds']))) {
            throw ValidationException::withMessages([
                'scheduledVisitAssignedServantIds' => 'الخدام المختارون يجب أن يكونوا من نفس مجموعة المخدوم.',
            ]);
        }

        $payload = [
            'beneficiary_id' => $beneficiary->id,
            'assigned_servant_id' => $assignedServantIds[0],
            'scheduled_date' => $data['scheduledVisitDate'],
            'scheduled_time' => $data['scheduledVisitTime'],
            'notes' => $data['scheduledVisitNotes'] ?: null,
            'status' => 'pending',
        ];

        if ($scheduledVisit) {
            $scheduledVisit->update($payload);
        } else {
            $scheduledVisit = ScheduledVisit::create($payload + [
                'created_by' => auth()->id(),
            ]);
        }

        $scheduledVisit->syncAssignedServants($assignedServantIds);

        $toastMessage = $this->editingScheduledVisitId
            ? 'تم تحديث الزيارة المجدولة بنجاح'
            : 'تمت جدولة الزيارة بنجاح';

        $this->showScheduledVisitForm = false;
        $this->resetScheduledVisitForm();
        $this->dispatch('toast', message: $toastMessage, type: 'success');
    }

    public function cancelScheduledVisit(int $scheduledVisitId): void
    {
        $scheduledVisit = WebAppScope::scheduledVisits(auth()->user())->whereKey($scheduledVisitId)->firstOrFail();

        abort_unless($scheduledVisit->status === 'pending', 403);

        if (auth()->user()->can('delete', $scheduledVisit)) {
            $scheduledVisit->update(['status' => 'cancelled']);
            $this->dispatch('toast', message: 'تم إلغاء الزيارة المجدولة', type: 'success');

            return;
        }

        abort_unless(
            auth()->user()->isServant()
            && $scheduledVisit->isAssignedTo(auth()->id())
            && $scheduledVisit->status === 'pending',
            403
        );

        $scheduledVisit->update(['status' => 'cancelled']);
        $this->dispatch('toast', message: 'تم إلغاء الزيارة المجدولة', type: 'success');
    }

    public function openVisitFromScheduled(int $scheduledVisitId): void
    {
        $scheduledVisit = WebAppScope::scheduledVisits(auth()->user())->whereKey($scheduledVisitId)->firstOrFail();

        abort_unless($scheduledVisit->status === 'pending', 403);

        $this->openVisitForm($scheduledVisit->beneficiary_id);
        $this->scheduledVisitContextId = $scheduledVisit->id;
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $meta = $this->meta();
        $beneficiaryOptions = $this->beneficiaryOptionsQuery()
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'code']);
        $servantOptions = $this->scheduledVisitServantOptionsQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'service_group_id']);

        if ($this->section === 'reports') {
            return view('livewire.web-app.placeholder-page', [
                'meta' => $meta,
                'filters' => [],
                'stats' => $this->reportStats($user),
                'records' => collect(),
                'reportCards' => $this->reportCards($user),
                'beneficiaryOptions' => $beneficiaryOptions,
                'servantOptions' => $servantOptions,
                'userRoleOptions' => $this->userRoleOptionsForActor($user),
                'userServiceGroupOptions' => $this->userServiceGroupOptionsForActor($user),
                'serviceGroupLeaderOptions' => $this->serviceGroupLeaderOptions($user),
                'serviceGroupServiceLeaderOptions' => $this->serviceGroupServiceLeaderOptions($user),
                'beneficiaryServiceGroupOptions' => $this->beneficiaryServiceGroupOptions($user),
                'beneficiaryServantOptions' => $this->beneficiaryServantOptions($user),
                'beneficiaryRecordStatusOptions' => $this->beneficiaryRecordStatusOptions(),
                'medicalFileTypeOptions' => $this->medicalFileTypeOptions(),
                'visitTypeOptions' => $this->visitTypeOptions(),
                'beneficiaryStatusOptions' => $this->beneficiaryStatusOptions(),
            ]);
        }

        $baseQuery = $this->queryForSection($user);
        $records = $this->applySort(
            $this->applyFilter(
                $this->applySearch(clone $baseQuery),
            )
        )->paginate($this->perPage());

        return view('livewire.web-app.placeholder-page', [
            'meta' => $meta,
            'filters' => $this->filters($user),
            'stats' => $this->stats($user, clone $baseQuery),
            'records' => $records,
            'reportCards' => collect(),
            'beneficiaryOptions' => $beneficiaryOptions,
            'servantOptions' => $servantOptions,
            'userRoleOptions' => $this->userRoleOptionsForActor($user),
            'userServiceGroupOptions' => $this->userServiceGroupOptionsForActor($user),
            'serviceGroupLeaderOptions' => $this->serviceGroupLeaderOptions($user),
            'serviceGroupServiceLeaderOptions' => $this->serviceGroupServiceLeaderOptions($user),
            'beneficiaryServiceGroupOptions' => $this->beneficiaryServiceGroupOptions($user),
            'beneficiaryServantOptions' => $this->beneficiaryServantOptions($user),
            'beneficiaryRecordStatusOptions' => $this->beneficiaryRecordStatusOptions(),
            'medicalFileTypeOptions' => $this->medicalFileTypeOptions(),
            'visitTypeOptions' => $this->visitTypeOptions(),
            'beneficiaryStatusOptions' => $this->beneficiaryStatusOptions(),
        ]);
    }

    private function queryForSection(User $user): Builder
    {
        return match ($this->section) {
            'beneficiaries' => WebAppScope::beneficiaries($user)
                ->with(['serviceGroup', 'assignedServant'])
                ->withCount('visits'),
            'visits' => WebAppScope::visits($user),
            'scheduled-visits' => WebAppScope::scheduledVisits($user),
            'prayer-requests' => WebAppScope::prayerRequests($user),
            'medical-files' => WebAppScope::medicalFiles($user),
            'users' => WebAppScope::users($user),
            'service-groups' => WebAppScope::serviceGroups($user),
            default => WebAppScope::beneficiaries($user),
        };
    }

    private function applySearch(Builder $query): Builder
    {
        if ($this->search === '') {
            return $query;
        }

        $term = '%' . $this->search . '%';

        return match ($this->section) {
            'beneficiaries' => $query->where(fn (Builder $builder) => $builder
                ->where('full_name', 'like', $term)
                ->orWhere('code', 'like', $term)
                ->orWhere('phone', 'like', $term)),
            'visits' => $query->where(fn (Builder $builder) => $builder
                ->where('type', 'like', $term)
                ->orWhere('feedback', 'like', $term)
                ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term))),
            'scheduled-visits' => $query->where(fn (Builder $builder) => $builder
                ->where('notes', 'like', $term)
                ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term))
                ->orWhereHas('servants', fn (Builder $servant) => $servant->where('name', 'like', $term))
                ->orWhereHas('assignedServant', fn (Builder $servant) => $servant->where('name', 'like', $term))),
            'prayer-requests' => $query->where(fn (Builder $builder) => $builder
                ->where('title', 'like', $term)
                ->orWhere('body', 'like', $term)
                ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term))),
            'medical-files' => $query->where(fn (Builder $builder) => $builder
                ->where('title', 'like', $term)
                ->orWhere('file_type', 'like', $term)
                ->orWhereHas('beneficiary', fn (Builder $beneficiary) => $beneficiary->where('full_name', 'like', $term))),
            'users' => $query->where(fn (Builder $builder) => $builder
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('phone', 'like', $term)),
            'service-groups' => $query->where(fn (Builder $builder) => $builder
                ->where('name', 'like', $term)
                ->orWhere('description', 'like', $term)),
            default => $query,
        };
    }

    private function applyFilter(Builder $query): Builder
    {
        return match ($this->section) {
            'beneficiaries' => match ($this->filter) {
                'mine' => $query->where('assigned_servant_id', auth()->id()),
                'recent' => $query->whereHas('visits', fn (Builder $builder) => $builder->where('visit_date', '>=', now()->subDays(30))),
                'needs-visit' => $query->whereDoesntHave('visits'),
                default => $query,
            },
            'visits' => match ($this->filter) {
                'month' => $query->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year),
                'critical' => $query->where('is_critical', true)->whereNull('critical_resolved_at'),
                'follow-up' => $query->where(fn (Builder $builder) => $builder->where('needs_family_leader', true)->orWhere('needs_service_leader', true)),
                default => $query,
            },
            'scheduled-visits' => match ($this->filter) {
                'upcoming' => $query->where('scheduled_date', '>=', now()->toDateString())->where('status', 'pending'),
                'completed' => $query->where('status', 'completed'),
                'past' => $query->where('scheduled_date', '<', now()->toDateString()),
                default => $query,
            },
            'prayer-requests' => $this->filter === 'all' ? $query : $query->where('status', $this->filter),
            'medical-files' => match ($this->filter) {
                'recent' => $query->where('created_at', '>=', now()->subDays(30)),
                'all' => $query,
                default => $query->where('file_type', $this->filter),
            },
            'users' => match ($this->filter) {
                'inactive' => $query->where('is_active', false),
                'active' => $query->where('is_active', true),
                'service_leader', 'family_leader', 'servant', 'super_admin' => $query->where('role', $this->filter),
                default => $query,
            },
            'service-groups' => match ($this->filter) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                default => $query,
            },
            default => $query,
        };
    }

    private function applySort(Builder $query): Builder
    {
        return match ($this->section) {
            'beneficiaries' => $query->orderBy('full_name'),
            'visits' => $query->latest('visit_date'),
            'scheduled-visits' => $query->orderBy('scheduled_date')->orderBy('scheduled_time'),
            'prayer-requests' => $query->latest(),
            'medical-files' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'users' => $query->orderByDesc('is_active')->orderBy('name'),
            'service-groups' => $query->orderBy('name'),
            default => $query,
        };
    }

    private function perPage(): int
    {
        return match ($this->section) {
            'service-groups' => 8,
            'users' => 10,
            default => 12,
        };
    }

    private function meta(): array
    {
        return [
            'beneficiaries' => [
                'title' => 'المخدومون',
                'description' => 'متابعة قائمة المخدومين داخل نطاقك مع بحث سريع، فلاتر واضحة، وأولوية للحالات التي تحتاج متابعة.',
                'icon' => 'ph-users-three',
                'primaryAction' => ['label' => 'لوحة التحكم', 'route' => route('app.dashboard'), 'icon' => 'ph-squares-four'],
                'secondaryAction' => ['label' => 'الزيارات', 'route' => route('app.visits'), 'icon' => 'ph-clipboard-text'],
            ],
            'visits' => [
                'title' => 'الزيارات',
                'description' => 'سجل الزيارات اليومية مع إبراز الحالات الحرجة والزيارات التي تحتاج تصعيد أو متابعة أسرع.',
                'icon' => 'ph-clipboard-text',
                'primaryAction' => ['label' => 'المخدومون', 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
                'secondaryAction' => ['label' => 'المجدولة', 'route' => route('app.scheduled-visits'), 'icon' => 'ph-calendar-check'],
            ],
            'scheduled-visits' => [
                'title' => 'الزيارات المجدولة',
                'description' => 'ترتيب المواعيد القادمة مع رؤية واضحة للمكلفين والحالات المكتملة أو المؤجلة.',
                'icon' => 'ph-calendar-check',
                'primaryAction' => ['label' => 'الزيارات', 'route' => route('app.visits'), 'icon' => 'ph-clipboard-text'],
                'secondaryAction' => ['label' => 'طلبات الصلاة', 'route' => route('app.prayer-requests'), 'icon' => 'ph-hands-praying'],
            ],
            'prayer-requests' => [
                'title' => 'طلبات الصلاة',
                'description' => 'مساحة هادئة لمتابعة الطلبات المفتوحة والمجابة بدون ضوضاء، مع ربط واضح بالمخدوم وصاحب الطلب.',
                'icon' => 'ph-hands-praying',
                'primaryAction' => ['label' => 'المخدومون', 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
                'secondaryAction' => ['label' => 'الملفات الطبية', 'route' => route('app.medical-files'), 'icon' => 'ph-file-lock'],
            ],
            'medical-files' => [
                'title' => 'الملفات الطبية',
                'description' => 'الوصول السريع للملفات الطبية المصرح بها داخل نطاقك، مع تنظيم حسب النوع وتاريخ الرفع.',
                'icon' => 'ph-file-lock',
                'primaryAction' => ['label' => 'طلبات الصلاة', 'route' => route('app.prayer-requests'), 'icon' => 'ph-hands-praying'],
                'secondaryAction' => ['label' => 'التقارير', 'route' => route('app.reports'), 'icon' => 'ph-chart-line-up'],
            ],
            'reports' => [
                'title' => 'التقارير',
                'description' => 'مركز واحد لتقارير المتابعة والزيارات وملفات الأسر، مع روابط مباشرة للنسخ الحالية PDF.',
                'icon' => 'ph-chart-line-up',
                'primaryAction' => ['label' => 'لوحة التحكم', 'route' => route('app.dashboard'), 'icon' => 'ph-squares-four'],
                'secondaryAction' => ['label' => 'المخدومون', 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
            ],
            'users' => [
                'title' => 'الخدام والمستخدمون',
                'description' => 'رؤية عملية للمستخدمين داخل نطاقك: من النشط، أدوار الخدمة، ومجموعات الخدمة المرتبطة.',
                'icon' => 'ph-identification-card',
                'primaryAction' => ['label' => 'مجموعات الخدمة', 'route' => route('app.service-groups'), 'icon' => 'ph-tree-structure'],
                'secondaryAction' => ['label' => 'التقارير', 'route' => route('app.reports'), 'icon' => 'ph-chart-line-up'],
            ],
            'service-groups' => [
                'title' => 'مجموعات الخدمة',
                'description' => 'عرض مجموعات الخدمة داخل نطاقك مع عدد المخدومين والخدام والمسؤولين عن كل مجموعة.',
                'icon' => 'ph-tree-structure',
                'primaryAction' => ['label' => 'الخدام', 'route' => route('app.users'), 'icon' => 'ph-identification-card'],
                'secondaryAction' => ['label' => 'التقارير', 'route' => route('app.reports'), 'icon' => 'ph-chart-line-up'],
            ],
        ][$this->section];
    }

    private function filters(User $user): array
    {
        $base = match ($this->section) {
            'beneficiaries' => [
                ['value' => 'all', 'label' => 'الكل'],
                ['value' => 'mine', 'label' => 'تعييني المباشر'],
                ['value' => 'recent', 'label' => 'نشط خلال 30 يوم'],
                ['value' => 'needs-visit', 'label' => 'بدون زيارات'],
            ],
            'visits' => [
                ['value' => 'all', 'label' => 'الكل'],
                ['value' => 'month', 'label' => 'هذا الشهر'],
                ['value' => 'critical', 'label' => 'حرجة'],
                ['value' => 'follow-up', 'label' => 'تحتاج متابعة'],
            ],
            'scheduled-visits' => [
                ['value' => 'all', 'label' => 'الكل'],
                ['value' => 'upcoming', 'label' => 'قادمة'],
                ['value' => 'completed', 'label' => 'مكتملة'],
                ['value' => 'past', 'label' => 'سابقة'],
            ],
            'prayer-requests' => [
                ['value' => 'all', 'label' => 'الكل'],
                ['value' => 'open', 'label' => 'مفتوحة'],
                ['value' => 'answered', 'label' => 'مستجابة'],
                ['value' => 'closed', 'label' => 'مغلقة'],
            ],
            'medical-files' => array_merge(
                [
                    ['value' => 'all', 'label' => 'الكل'],
                    ['value' => 'recent', 'label' => 'آخر 30 يوم'],
                ],
                WebAppScope::medicalFiles($user)
                    ->select('file_type')
                    ->distinct()
                    ->pluck('file_type')
                    ->filter()
                    ->map(fn (string $type) => ['value' => $type, 'label' => $type])
                    ->values()
                    ->all(),
            ),
            'users' => [
                ['value' => 'all', 'label' => 'الكل'],
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير مفعل'],
                ['value' => 'service_leader', 'label' => 'أمين خدمة'],
                ['value' => 'family_leader', 'label' => 'أمين أسرة'],
                ['value' => 'servant', 'label' => 'خادم'],
            ],
            'service-groups' => [
                ['value' => 'all', 'label' => 'الكل'],
                ['value' => 'active', 'label' => 'نشطة'],
                ['value' => 'inactive', 'label' => 'غير نشطة'],
            ],
            default => [],
        };

        return array_values(array_reduce($base, function (array $carry, array $item): array {
            $carry[$item['value']] = $item;

            return $carry;
        }, []));
    }

    private function stats(User $user, Builder $baseQuery): array
    {
        return match ($this->section) {
            'beneficiaries' => [
                ['label' => 'إجمالي المخدومين', 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
                ['label' => 'تعيين مباشر', 'value' => (clone $baseQuery)->where('assigned_servant_id', $user->id)->count(), 'tone' => 'emerald'],
                ['label' => 'بدون زيارات', 'value' => (clone $baseQuery)->whereDoesntHave('visits')->count(), 'tone' => 'amber'],
            ],
            'visits' => [
                ['label' => 'إجمالي الزيارات', 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
                ['label' => 'هذا الشهر', 'value' => (clone $baseQuery)->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year)->count(), 'tone' => 'emerald'],
                ['label' => 'حرجة مفتوحة', 'value' => (clone $baseQuery)->where('is_critical', true)->whereNull('critical_resolved_at')->count(), 'tone' => 'rose'],
            ],
            'scheduled-visits' => [
                ['label' => 'قادمة', 'value' => (clone $baseQuery)->where('scheduled_date', '>=', now()->toDateString())->where('status', 'pending')->count(), 'tone' => 'blue'],
                ['label' => 'اليوم', 'value' => (clone $baseQuery)->whereDate('scheduled_date', now()->toDateString())->count(), 'tone' => 'emerald'],
                ['label' => 'مكتملة', 'value' => (clone $baseQuery)->where('status', 'completed')->count(), 'tone' => 'amber'],
            ],
            'prayer-requests' => [
                ['label' => 'مفتوحة', 'value' => (clone $baseQuery)->where('status', 'open')->count(), 'tone' => 'blue'],
                ['label' => 'مستجابة', 'value' => (clone $baseQuery)->where('status', 'answered')->count(), 'tone' => 'emerald'],
                ['label' => 'آخر 7 أيام', 'value' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count(), 'tone' => 'amber'],
            ],
            'medical-files' => [
                ['label' => 'إجمالي الملفات', 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
                ['label' => 'آخر 30 يوم', 'value' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count(), 'tone' => 'emerald'],
                ['label' => 'أنواع مختلفة', 'value' => (clone $baseQuery)->select('file_type')->distinct()->count('file_type'), 'tone' => 'amber'],
            ],
            'users' => [
                ['label' => 'إجمالي المستخدمين', 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
                ['label' => 'نشطون', 'value' => (clone $baseQuery)->where('is_active', true)->count(), 'tone' => 'emerald'],
                ['label' => 'خدام', 'value' => (clone $baseQuery)->where('role', 'servant')->count(), 'tone' => 'amber'],
            ],
            'service-groups' => [
                ['label' => 'المجموعات', 'value' => (clone $baseQuery)->count(), 'tone' => 'blue'],
                ['label' => 'نشطة', 'value' => (clone $baseQuery)->where('is_active', true)->count(), 'tone' => 'emerald'],
                ['label' => 'غير نشطة', 'value' => (clone $baseQuery)->where('is_active', false)->count(), 'tone' => 'amber'],
            ],
            default => [],
        };
    }

    private function reportStats(User $user): array
    {
        return [
            ['label' => 'التقارير المتاحة', 'value' => $this->reportCards($user)->count(), 'tone' => 'blue'],
            ['label' => 'المجموعات ضمن نطاقك', 'value' => $user->can('viewAny', ServiceGroup::class) ? WebAppScope::serviceGroups($user)->count() : 0, 'tone' => 'emerald'],
            ['label' => 'المخدومون ضمن نطاقك', 'value' => WebAppScope::beneficiaries($user)->count(), 'tone' => 'amber'],
        ];
    }

    private function reportCards(User $user): Collection
    {
        $cards = collect([
            [
                'title' => 'تقرير المخدومين',
                'description' => 'نسخة PDF عامة لقائمة المخدومين حسب صلاحيات المستخدم الحالي.',
                'route' => route('reports.beneficiaries.pdf'),
                'icon' => 'ph-users-three',
            ],
        ]);

        if (! in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader], true)) {
            return $cards;
        }

        $cards = $cards->merge([
            [
                'title' => 'تقرير الزيارات',
                'description' => 'ملخص PDF للزيارات المسجلة.',
                'route' => route('reports.visits.pdf'),
                'icon' => 'ph-clipboard-text',
            ],
            [
                'title' => 'تقرير غير المزورين',
                'description' => 'إبراز المخدومين الذين يحتاجون متابعة أو زيارة.',
                'route' => route('reports.unvisited.pdf'),
                'icon' => 'ph-warning-circle',
            ],
        ]);

        if (! $user->can('viewAny', ServiceGroup::class)) {
            return $cards;
        }

        return $cards->merge(
            WebAppScope::serviceGroups($user)
                ->get()
                ->flatMap(fn (ServiceGroup $group) => [
                    [
                        'title' => "تقرير {$group->name}",
                        'description' => 'ملف PDF لبيانات الأسرة ومؤشراتها الأساسية.',
                        'route' => route('reports.service-group.pdf', $group),
                        'icon' => 'ph-tree-structure',
                    ],
                    [
                        'title' => "مخدومو {$group->name}",
                        'description' => 'قائمة PDF لمخدومي المجموعة الحالية.',
                        'route' => route('reports.service-group.beneficiaries.pdf', $group),
                        'icon' => 'ph-users-three',
                    ],
                ])
        );
    }

    private function beneficiaryOptionsQuery(): Builder
    {
        return WebAppScope::beneficiaries(auth()->user());
    }

    private function resetVisitForm(): void
    {
        $this->reset([
            'editingVisitId',
            'visitBeneficiaryId',
            'visitType',
            'visitDate',
            'durationMinutes',
            'beneficiaryStatus',
            'visitFeedback',
            'isCritical',
            'needsFamilyLeader',
            'needsServiceLeader',
            'scheduledVisitContextId',
        ]);
    }

    private function resetBeneficiaryForm(): void
    {
        $this->reset([
            'editingBeneficiaryId',
            'beneficiaryFullName',
            'beneficiaryBirthDate',
            'beneficiaryGender',
            'beneficiaryRecordStatus',
            'beneficiaryPhone',
            'beneficiaryWhatsapp',
            'beneficiaryGuardianName',
            'beneficiaryGuardianPhone',
            'beneficiaryAddressText',
            'beneficiaryServiceGroupId',
            'beneficiaryAssignedServantId',
        ]);

        $this->beneficiaryRecordStatus = 'active';
    }

    private function resetMedicalFileForm(): void
    {
        $this->reset([
            'medicalFileBeneficiaryId',
            'medicalFileTitle',
            'medicalFileType',
            'medicalUploadedFile',
        ]);

        $this->medicalFileType = 'report';
    }

    private function resetPrayerForm(): void
    {
        $this->reset([
            'prayerBeneficiaryId',
            'prayerTitle',
            'prayerBody',
        ]);
    }

    private function visitTypeOptions(): array
    {
        return [
            'home_visit' => 'زيارة منزلية',
            'phone_call' => 'مكالمة هاتفية',
            'church_meeting' => 'اجتماع كنيسة',
        ];
    }

    private function beneficiaryStatusOptions(): array
    {
        return [
            'great' => 'ممتاز',
            'good' => 'جيد',
            'needs_follow' => 'يحتاج متابعة',
            'critical' => 'حرج',
        ];
    }

    private function beneficiaryRecordStatusOptions(): array
    {
        return [
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'moved' => 'انتقل',
            'deceased' => 'متنيح',
        ];
    }

    private function medicalFileTypeOptions(): array
    {
        return [
            'report' => 'تقرير',
            'image' => 'صورة',
            'document' => 'مستند',
        ];
    }

    private function servantOptionsQuery(): Builder
    {
        return WebAppScope::users(auth()->user())->where('role', UserRole::Servant);
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
                'beneficiaryServiceGroupId' => 'مجموعة الخدمة خارج نطاق صلاحياتك.',
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
                'beneficiaryAssignedServantId' => 'الخادم المختار يجب أن يكون من نفس مجموعة الخدمة.',
            ]);
        }
    }

    private function resetScheduledVisitForm(): void
    {
        $this->reset([
            'scheduledVisitBeneficiaryId',
            'editingScheduledVisitId',
            'scheduledVisitAssignedServantIds',
            'scheduledVisitDate',
            'scheduledVisitTime',
            'scheduledVisitNotes',
        ]);
    }

    private function resetUserForm(): void
    {
        $this->reset([
            'editingUserId',
            'userName',
            'userEmail',
            'userPhone',
            'userPassword',
            'userRole',
            'userServiceGroupId',
            'userLocale',
            'userIsActive',
        ]);

        $this->userLocale = 'ar';
        $this->userIsActive = true;
    }

    private function resetServiceGroupForm(): void
    {
        $this->reset([
            'editingServiceGroupId',
            'serviceGroupName',
            'serviceGroupDescription',
            'serviceGroupLeaderId',
            'serviceGroupServiceLeaderId',
            'serviceGroupIsActive',
        ]);

        $this->serviceGroupIsActive = true;
    }

    private function userRoleOptionsForActor(User $actor): Collection
    {
        if ($actor->role === UserRole::SuperAdmin) {
            return collect(UserRole::options());
        }

        if ($actor->role === UserRole::ServiceLeader) {
            return collect([
                UserRole::FamilyLeader->value => UserRole::FamilyLeader->label(),
                UserRole::Servant->value => UserRole::Servant->label(),
            ]);
        }

        return collect();
    }

    private function userServiceGroupOptionsForActor(User $actor): Collection
    {
        $query = ServiceGroup::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($actor->role === UserRole::ServiceLeader) {
            $query->where('service_leader_id', $actor->id);
        } elseif ($actor->role !== UserRole::SuperAdmin) {
            $query->whereRaw('1 = 0');
        }

        return $query->pluck('name', 'id');
    }

    private function ensureUserAssignmentAllowed(User $actor, ?User $record, UserRole $role, ?int $serviceGroupId): void
    {
        if ($record && $record->id === $actor->id) {
            return;
        }

        if ($role->isAdminLevel()) {
            if ($actor->role !== UserRole::SuperAdmin) {
                throw ValidationException::withMessages([
                    'userRole' => 'لا يمكنك تعيين هذا الدور.',
                ]);
            }

            return;
        }

        $allowedGroupIds = $this->userServiceGroupOptionsForActor($actor)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! $serviceGroupId || ! in_array($serviceGroupId, $allowedGroupIds, true)) {
            throw ValidationException::withMessages([
                'userServiceGroupId' => 'مجموعة الخدمة خارج نطاق صلاحياتك.',
            ]);
        }

        if ($actor->role === UserRole::ServiceLeader && ! in_array($role, [UserRole::FamilyLeader, UserRole::Servant], true)) {
            throw ValidationException::withMessages([
                'userRole' => 'أمين الخدمة يمكنه إدارة أمناء الأسر والخدام فقط.',
            ]);
        }
    }

    private function serviceGroupLeaderOptions(User $actor): Collection
    {
        $query = User::query()
            ->where('role', UserRole::FamilyLeader)
            ->where('is_active', true)
            ->orderBy('name');

        if ($actor->role === UserRole::ServiceLeader) {
            $query->whereIn('service_group_id', $actor->managedServiceGroupIds());
        } elseif ($actor->role === UserRole::FamilyLeader) {
            $query->whereKey($actor->id);
        } elseif ($actor->role !== UserRole::SuperAdmin) {
            $query->whereRaw('1 = 0');
        }

        return $query->pluck('name', 'id');
    }

    private function serviceGroupServiceLeaderOptions(User $actor): Collection
    {
        if ($actor->isServiceLeader()) {
            return collect([$actor->id => $actor->name]);
        }

        if ($actor->role !== UserRole::SuperAdmin) {
            return collect();
        }

        return User::query()
            ->where('role', UserRole::ServiceLeader)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    private function ensureServiceGroupLeadersAllowed(User $actor, ?ServiceGroup $record, ?int $leaderId, ?int $serviceLeaderId): void
    {
        if ($leaderId !== null) {
            if (! $record) {
                throw ValidationException::withMessages([
                    'serviceGroupLeaderId' => 'يمكن تعيين أمين الأسرة بعد إنشاء المجموعة.',
                ]);
            }

            $leader = User::query()
                ->whereKey($leaderId)
                ->where('role', UserRole::FamilyLeader)
                ->where('is_active', true)
                ->first();

            if (! $leader) {
                throw ValidationException::withMessages([
                    'serviceGroupLeaderId' => 'أمين الأسرة المختار غير صالح.',
                ]);
            }

            if ($record && $leader->service_group_id !== $record->id) {
                throw ValidationException::withMessages([
                    'serviceGroupLeaderId' => 'أمين الأسرة يجب أن يكون من نفس مجموعة الخدمة.',
                ]);
            }

            if ($actor->isServiceLeader() && ! in_array($leader->service_group_id, $actor->managedServiceGroupIds(), true)) {
                throw ValidationException::withMessages([
                    'serviceGroupLeaderId' => 'أمين الأسرة خارج نطاق صلاحياتك.',
                ]);
            }
        }

        if ($serviceLeaderId === null) {
            return;
        }

        if ($actor->isServiceLeader() && $serviceLeaderId !== $actor->id) {
            throw ValidationException::withMessages([
                'serviceGroupServiceLeaderId' => 'لا يمكنك تعيين أمين خدمة آخر.',
            ]);
        }

        if (! User::query()->whereKey($serviceLeaderId)->where('role', UserRole::ServiceLeader)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'serviceGroupServiceLeaderId' => 'أمين الخدمة المختار غير صالح.',
            ]);
        }
    }

    private function syncInactiveServiceGroupBeneficiaries(ServiceGroup $serviceGroup): void
    {
        if ($serviceGroup->is_active) {
            return;
        }

        Beneficiary::query()
            ->where('service_group_id', $serviceGroup->id)
            ->update(['status' => 'inactive']);
    }

    private function scheduledVisitFormDefaultAssignee(): void
    {
        if (auth()->user()->isServant()) {
            $this->scheduledVisitAssignedServantIds = [auth()->id()];
        }
    }

    private function scheduledVisitServantOptionsQuery(): Builder
    {
        $query = $this->servantOptionsQuery();

        if ($this->scheduledVisitBeneficiaryId === null) {
            return $query;
        }

        $beneficiary = $this->beneficiaryOptionsQuery()
            ->whereKey($this->scheduledVisitBeneficiaryId)
            ->first();

        if (! $beneficiary) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('service_group_id', $beneficiary->service_group_id);
    }

    private function completeLinkedScheduledVisit(Visit $visit): void
    {
        if ($this->scheduledVisitContextId !== null) {
            $scheduledVisit = WebAppScope::scheduledVisits(auth()->user())
                ->whereKey($this->scheduledVisitContextId)
                ->where('status', 'pending')
                ->first();

            if ($scheduledVisit !== null) {
                $scheduledVisit->update([
                    'status' => 'completed',
                    'completed_visit_id' => $visit->id,
                ]);
            }

            return;
        }

        if (! auth()->user()->isServant()) {
            return;
        }

        ScheduledVisit::query()
            ->where('beneficiary_id', $visit->beneficiary_id)
            ->assignedTo(auth()->id())
            ->where('status', 'pending')
            ->whereDate('scheduled_date', today())
            ->update([
                'status' => 'completed',
                'completed_visit_id' => $visit->id,
            ]);
    }
}
