<?php

namespace Tests\Unit\Filament;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class UserWriteGuardTest extends TestCase
{
    use CreatesTestUsers;
    use RefreshDatabase;

    public function test_service_leader_cannot_create_user_in_unmanaged_group(): void
    {
        $groupA        = ServiceGroup::factory()->create();
        $groupB        = ServiceGroup::factory()->create();
        $serviceLeader = $this->createServiceLeader();
        $groupA->update(['service_leader_id' => $serviceLeader->id]);

        $page = new class extends CreateUser
        {
            public function mutate(array $data): array
            {
                return $this->mutateFormDataBeforeCreate($data);
            }
        };

        $this->actingAs($serviceLeader);

        $this->expectException(ValidationException::class);

        $page->mutate([
            'name'             => 'Scoped User',
            'email'            => 'scoped-user@example.com',
            'password'         => 'password123',
            'role'             => 'servant',
            'service_group_id' => $groupB->id,
            'locale'           => 'ar',
            'is_active'        => true,
        ]);
    }

    public function test_service_leader_cannot_move_user_to_unmanaged_group(): void
    {
        $groupA        = ServiceGroup::factory()->create();
        $groupB        = ServiceGroup::factory()->create();
        $serviceLeader = $this->createServiceLeader();
        $groupA->update(['service_leader_id' => $serviceLeader->id]);
        $managedServant = $this->createServant($groupA);

        $page = new class extends EditUser
        {
            public function setRecordForTest(User $record): void
            {
                $this->record = $record;
            }

            public function mutate(array $data): array
            {
                return $this->mutateFormDataBeforeSave($data);
            }
        };

        $page->setRecordForTest($managedServant);
        $this->actingAs($serviceLeader);

        $this->expectException(ValidationException::class);

        $page->mutate([
            'role'             => 'servant',
            'service_group_id' => $groupB->id,
        ]);
    }

    public function test_self_edit_strips_admin_fields(): void
    {
        $group         = ServiceGroup::factory()->create();
        $serviceLeader = $this->createServiceLeader(['service_group_id' => $group->id]);

        $page = new class extends EditUser
        {
            public function setRecordForTest(User $record): void
            {
                $this->record = $record;
            }

            public function mutate(array $data): array
            {
                return $this->mutateFormDataBeforeSave($data);
            }
        };

        $page->setRecordForTest($serviceLeader);
        $this->actingAs($serviceLeader);

        $mutated = $page->mutate([
            'name'             => 'Updated Name',
            'role'             => 'super_admin',
            'service_group_id' => 999,
            'is_active'        => false,
        ]);

        $this->assertSame('Updated Name', $mutated['name']);
        $this->assertArrayNotHasKey('role', $mutated);
        $this->assertArrayNotHasKey('service_group_id', $mutated);
        $this->assertArrayNotHasKey('is_active', $mutated);
    }
}
