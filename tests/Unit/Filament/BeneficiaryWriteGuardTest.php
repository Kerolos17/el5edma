<?php

namespace Tests\Unit\Filament;

use App\Filament\Resources\Beneficiaries\Pages\CreateBeneficiary;
use App\Filament\Resources\Beneficiaries\Pages\EditBeneficiary;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class BeneficiaryWriteGuardTest extends TestCase
{
    use CreatesTestUsers;
    use RefreshDatabase;

    public function test_family_leader_cannot_create_beneficiary_in_other_group(): void
    {
        $groupA       = ServiceGroup::factory()->create();
        $groupB       = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($groupA);

        $page = new class extends CreateBeneficiary
        {
            public function mutate(array $data): array
            {
                return $this->mutateFormDataBeforeCreate($data);
            }
        };

        $this->actingAs($familyLeader);

        $this->expectException(ValidationException::class);

        $page->mutate([
            'full_name'        => 'Test Beneficiary',
            'birth_date'       => now()->subYears(10)->toDateString(),
            'gender'           => 'male',
            'status'           => 'active',
            'service_group_id' => $groupB->id,
        ]);
    }

    public function test_family_leader_cannot_assign_servant_from_other_group(): void
    {
        $groupA         = ServiceGroup::factory()->create();
        $groupB         = ServiceGroup::factory()->create();
        $familyLeader   = $this->createFamilyLeader($groupA);
        $foreignServant = $this->createServant($groupB);

        $page = new class extends CreateBeneficiary
        {
            public function mutate(array $data): array
            {
                return $this->mutateFormDataBeforeCreate($data);
            }
        };

        $this->actingAs($familyLeader);

        $this->expectException(ValidationException::class);

        $page->mutate([
            'full_name'           => 'Test Beneficiary',
            'birth_date'          => now()->subYears(10)->toDateString(),
            'gender'              => 'male',
            'status'              => 'active',
            'service_group_id'    => $groupA->id,
            'assigned_servant_id' => $foreignServant->id,
        ]);
    }

    public function test_service_leader_cannot_move_beneficiary_to_unmanaged_group(): void
    {
        $groupA        = ServiceGroup::factory()->create();
        $groupB        = ServiceGroup::factory()->create();
        $serviceLeader = $this->createServiceLeader();
        $groupA->update(['service_leader_id' => $serviceLeader->id]);

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $groupA->id,
        ]);

        $page = new class extends EditBeneficiary
        {
            public function setRecordForTest(Beneficiary $record): void
            {
                $this->record = $record;
            }

            public function mutate(array $data): array
            {
                return $this->mutateFormDataBeforeSave($data);
            }
        };

        $page->setRecordForTest($beneficiary);
        $this->actingAs($serviceLeader);

        $this->expectException(ValidationException::class);

        $page->mutate([
            'service_group_id' => $groupB->id,
        ]);
    }
}
