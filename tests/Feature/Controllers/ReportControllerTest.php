<?php

namespace Tests\Feature\Controllers;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ReportControllerTest extends TestCase
{
    use CreatesTestUsers;
    use RefreshDatabase;

    public function test_family_leader_can_download_beneficiaries_pdf(): void
    {
        $group = ServiceGroup::factory()->create();
        $user  = $this->createFamilyLeader($group);

        Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'created_by'       => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.beneficiaries.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_family_leader_can_download_visits_pdf_with_date_filters(): void
    {
        $group       = ServiceGroup::factory()->create();
        $user        = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'created_by'       => $user->id,
        ]);

        Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by'     => $user->id,
            'visit_date'     => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('reports.visits.pdf', [
            'date_from' => now()->subWeek()->toDateString(),
            'date_to'   => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_family_leader_can_download_unvisited_pdf(): void
    {
        $group = ServiceGroup::factory()->create();
        $user  = $this->createFamilyLeader($group);

        Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'created_by'       => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.unvisited.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_family_leader_can_download_single_beneficiary_pdf_with_photo(): void
    {
        $group = ServiceGroup::factory()->create();
        $user  = $this->createFamilyLeader($group);

        $relativePhotoPath = 'beneficiaries/photos/test-beneficiary-photo.png';
        $absolutePhotoPath = storage_path('app/public/' . $relativePhotoPath);

        File::ensureDirectoryExists(dirname($absolutePhotoPath));
        File::put($absolutePhotoPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sX6lz8AAAAASUVORK5CYII='));

        try {
            $beneficiary = Beneficiary::factory()->create([
                'service_group_id' => $group->id,
                'created_by'       => $user->id,
                'photo'            => $relativePhotoPath,
            ]);

            $response = $this->actingAs($user)->get(route('reports.beneficiary.pdf', ['beneficiary' => $beneficiary]));

            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        } finally {
            File::delete($absolutePhotoPath);
        }
    }

    public function test_servant_cannot_access_report_pdf_routes_directly(): void
    {
        $group       = ServiceGroup::factory()->create();
        $user        = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'created_by'       => $user->id,
        ]);

        $this->actingAs($user)->get(route('reports.beneficiaries.pdf'))->assertOk();
        $this->actingAs($user)->get(route('reports.beneficiary.pdf', ['beneficiary' => $beneficiary]))->assertOk();
        $this->actingAs($user)->get(route('reports.visits.pdf'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.unvisited.pdf'))->assertForbidden();
    }

    public function test_servant_cannot_download_single_beneficiary_pdf_from_another_group(): void
    {
        $group       = ServiceGroup::factory()->create();
        $otherGroup  = ServiceGroup::factory()->create();
        $user        = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $otherGroup->id,
        ]);

        $this->actingAs($user)
            ->get(route('reports.beneficiary.pdf', ['beneficiary' => $beneficiary]))
            ->assertForbidden();
    }
}
