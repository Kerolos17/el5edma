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

class FileAccessControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_unauthenticated_request_is_blocked(): void
    {
        $path = base64_encode('some/file.pdf');

        // Filament uses its own login route; just verify the guest cannot access the file
        $response = $this->get(route('private.file', ['path' => $path]));

        $this->assertNotSame(200, $response->getStatusCode());
    }

    public function test_invalid_base64_path_is_rejected(): void
    {
        $admin    = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $response = $this->actingAs($admin)->get(route('private.file', ['path' => '!!!not-base64!!!']));

        $response->assertForbidden();
    }

    public function test_path_traversal_attempt_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $traversalPath = base64_encode('../etc/passwd');
        $response      = $this->actingAs($admin)->get(route('private.file', ['path' => $traversalPath]));

        $response->assertForbidden();
    }

    public function test_path_starting_with_slash_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $response = $this->actingAs($admin)->get(
            route('private.file', ['path' => base64_encode('/etc/passwd')])
        );

        $response->assertForbidden();
    }

    public function test_super_admin_can_access_existing_file(): void
    {
        $admin   = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $filePath = 'medical-files/test-document.pdf';

        Storage::disk('private')->put($filePath, 'PDF content');

        MedicalFile::factory()->create(['file_path' => $filePath]);

        $response = $this->actingAs($admin)->get(
            route('private.file', ['path' => base64_encode($filePath)])
        );

        $response->assertOk();
    }

    public function test_servant_cannot_access_unassigned_file(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = User::factory()->create(['role' => UserRole::Servant, 'service_group_id' => $group->id]);
        $other   = User::factory()->create(['role' => UserRole::Servant, 'service_group_id' => $group->id]);
        $ben     = Beneficiary::factory()->create(['service_group_id' => $group->id, 'assigned_servant_id' => $other->id]);

        $filePath = 'medical-files/private-doc.pdf';
        Storage::disk('private')->put($filePath, 'PDF content');
        MedicalFile::factory()->create(['beneficiary_id' => $ben->id, 'file_path' => $filePath]);

        $response = $this->actingAs($servant)->get(
            route('private.file', ['path' => base64_encode($filePath)])
        );

        $response->assertForbidden();
    }
}
