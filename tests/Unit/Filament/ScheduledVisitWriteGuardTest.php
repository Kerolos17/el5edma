<?php

namespace Tests\Unit\Filament;

use App\Filament\Resources\ScheduledVisits\Pages\CreateScheduledVisit;
use App\Filament\Resources\ScheduledVisits\Pages\EditScheduledVisit;
use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ScheduledVisitWriteGuardTest extends TestCase
{
    use CreatesTestUsers;
    use RefreshDatabase;

    public function test_family_leader_cannot_assign_servant_from_other_group(): void
    {
        $groupA         = ServiceGroup::factory()->create();
        $groupB         = ServiceGroup::factory()->create();
        $familyLeader   = $this->createFamilyLeader($groupA);
        $beneficiary    = Beneficiary::factory()->create(['service_group_id' => $groupA->id]);
        $foreignServant = $this->createServant($groupB);

        $page = new class extends CreateScheduledVisit
        {
            public function mutate(array $data): array
            {
                return $this->mutateFormDataBeforeCreate($data);
            }
        };

        $this->actingAs($familyLeader);
        $this->expectException(ValidationException::class);

        $page->mutate([
            'beneficiary_id'      => $beneficiary->id,
            'assigned_servant_id' => $foreignServant->id,
            'scheduled_date'      => now()->addDay()->toDateString(),
            'scheduled_time'      => '10:00',
            'status'              => 'pending',
        ]);
    }

    public function test_service_leader_cannot_create_scheduled_visit_for_unmanaged_group(): void
    {
        $groupA        = ServiceGroup::factory()->create();
        $groupB        = ServiceGroup::factory()->create();
        $serviceLeader = $this->createServiceLeader();
        $groupA->update(['service_leader_id' => $serviceLeader->id]);
        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $groupB->id]);
        $servant     = $this->createServant($groupB);

        $page = new class extends CreateScheduledVisit
        {
            public function mutate(array $data): array
            {
                return $this->mutateFormDataBeforeCreate($data);
            }
        };

        $this->actingAs($serviceLeader);
        $this->expectException(ValidationException::class);

        $page->mutate([
            'beneficiary_id'      => $beneficiary->id,
            'assigned_servant_id' => $servant->id,
            'scheduled_date'      => now()->addDay()->toDateString(),
            'scheduled_time'      => '10:00',
            'status'              => 'pending',
        ]);
    }

    public function test_service_leader_cannot_move_scheduled_visit_to_unmanaged_group(): void
    {
        $groupA        = ServiceGroup::factory()->create();
        $groupB        = ServiceGroup::factory()->create();
        $serviceLeader = $this->createServiceLeader();
        $groupA->update(['service_leader_id' => $serviceLeader->id]);

        $managedBeneficiary = Beneficiary::factory()->create(['service_group_id' => $groupA->id]);
        $foreignBeneficiary = Beneficiary::factory()->create(['service_group_id' => $groupB->id]);
        $managedServant     = $this->createServant($groupA);
        $scheduledVisit     = ScheduledVisit::factory()->create([
            'beneficiary_id'      => $managedBeneficiary->id,
            'assigned_servant_id' => $managedServant->id,
        ]);

        $page = new class extends EditScheduledVisit
        {
            public function setRecordForTest(ScheduledVisit $record): void
            {
                $this->record = $record;
            }

            public function mutate(array $data): array
            {
                return $this->mutateFormDataBeforeSave($data);
            }
        };

        $page->setRecordForTest($scheduledVisit);
        $this->actingAs($serviceLeader);
        $this->expectException(ValidationException::class);

        $page->mutate([
            'beneficiary_id'      => $foreignBeneficiary->id,
            'assigned_servant_id' => $managedServant->id,
        ]);
    }
}
