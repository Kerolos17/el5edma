<?php

namespace Tests\Feature\Controllers;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MedicalFileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_unauthenticated_request_is_blocked(): void
    {
        $medicalFile = MedicalFile::factory()->create();

        // Filament uses its own login route; just verify the guest cannot access the file
        $response = $this->get(route('medical-files.download', $medicalFile));

        $this->assertNotSame(200, $response->getStatusCode());
    }

    public function test_super_admin_can_download_any_file(): void
    {
        $admin       = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $medicalFile = MedicalFile::factory()->create();

        Storage::disk('private')->put($medicalFile->file_path, 'file content');

        $response = $this->actingAs($admin)->get(route('medical-files.download', $medicalFile));

        $response->assertOk();
    }

    public function test_servant_can_download_assigned_beneficiary_file(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = User::factory()->create(['role' => UserRole::Servant, 'service_group_id' => $group->id]);
        $ben     = Beneficiary::factory()->create(['service_group_id' => $group->id, 'assigned_servant_id' => $servant->id]);
        $medFile = MedicalFile::factory()->create(['beneficiary_id' => $ben->id]);

        Storage::disk('private')->put($medFile->file_path, 'file content');

        $response = $this->actingAs($servant)->get(route('medical-files.download', $medFile));

        $response->assertOk();
    }

    public function test_servant_cannot_download_unassigned_beneficiary_file(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = User::factory()->create(['role' => UserRole::Servant, 'service_group_id' => $group->id]);
        $other   = User::factory()->create(['role' => UserRole::Servant, 'service_group_id' => $group->id]);
        $ben     = Beneficiary::factory()->create(['service_group_id' => $group->id, 'assigned_servant_id' => $other->id]);
        $medFile = MedicalFile::factory()->create(['beneficiary_id' => $ben->id]);

        Storage::disk('private')->put($medFile->file_path, 'file content');

        $response = $this->actingAs($servant)->get(route('medical-files.download', $medFile));

        $response->assertForbidden();
    }

    public function test_family_leader_can_download_same_group_file(): void
    {
        $group   = ServiceGroup::factory()->create();
        $fl      = User::factory()->create(['role' => UserRole::FamilyLeader, 'service_group_id' => $group->id]);
        $ben     = Beneficiary::factory()->create(['service_group_id' => $group->id]);
        $medFile = MedicalFile::factory()->create(['beneficiary_id' => $ben->id]);

        Storage::disk('private')->put($medFile->file_path, 'file content');

        $response = $this->actingAs($fl)->get(route('medical-files.download', $medFile));

        $response->assertOk();
    }

    public function test_family_leader_cannot_download_other_group_file(): void
    {
        $groupA  = ServiceGroup::factory()->create();
        $groupB  = ServiceGroup::factory()->create();
        $fl      = User::factory()->create(['role' => UserRole::FamilyLeader, 'service_group_id' => $groupA->id]);
        $ben     = Beneficiary::factory()->create(['service_group_id' => $groupB->id]);
        $medFile = MedicalFile::factory()->create(['beneficiary_id' => $ben->id]);

        Storage::disk('private')->put($medFile->file_path, 'file content');

        $response = $this->actingAs($fl)->get(route('medical-files.download', $medFile));

        $response->assertForbidden();
    }

    public function test_download_returns_404_when_file_missing_from_disk(): void
    {
        $admin   = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $medFile = MedicalFile::factory()->create();

        // File not put in storage — disk is empty

        $response = $this->actingAs($admin)->get(route('medical-files.download', $medFile));

        $response->assertNotFound();
    }
}
