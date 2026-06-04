<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

/**
 * Every access to a private medical file must be recorded in the audit log.
 */
class MedicalFileAuditTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    public function test_accessing_medical_file_creates_audit_log_entry(): void
    {
        Storage::fake('private');

        $group  = ServiceGroup::factory()->create();
        $leader = $this->createFamilyLeader($group);
        $bene   = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'created_by'       => $leader->id,
        ]);

        Storage::disk('private')->put('medical/test-file.pdf', 'dummy-content');

        $file = MedicalFile::create([
            'beneficiary_id' => $bene->id,
            'file_path'      => 'medical/test-file.pdf',
            'file_type'      => 'report',
            'title'          => 'تقرير طبي',
            'uploaded_by'    => $leader->id,
        ]);

        $this->actingAs($leader)
            ->get(route('private.file', ['path' => base64_encode('medical/test-file.pdf')]));

        $this->assertDatabaseHas('audit_logs', [
            'user_id'    => $leader->id,
            'model_type' => MedicalFile::class,
            'model_id'   => $file->id,
            'action'     => 'accessed',
        ]);
    }

    public function test_unauthorized_access_does_not_create_audit_entry(): void
    {
        Storage::fake('private');

        $group1 = ServiceGroup::factory()->create();
        $group2 = ServiceGroup::factory()->create();
        $leader = $this->createFamilyLeader($group1);
        $other  = $this->createFamilyLeader($group2);

        $bene = Beneficiary::factory()->create([
            'service_group_id' => $group1->id,
            'created_by'       => $leader->id,
        ]);

        Storage::disk('private')->put('medical/secure.pdf', 'dummy');

        MedicalFile::create([
            'beneficiary_id' => $bene->id,
            'file_path'      => 'medical/secure.pdf',
            'file_type'      => 'report',
            'title'          => 'ملف سري',
            'uploaded_by'    => $leader->id,
        ]);

        $this->actingAs($other)
            ->get(route('private.file', ['path' => base64_encode('medical/secure.pdf')]))
            ->assertForbidden();

        $this->assertDatabaseMissing('audit_logs', [
            'user_id' => $other->id,
            'action'  => 'accessed',
        ]);
    }
}
