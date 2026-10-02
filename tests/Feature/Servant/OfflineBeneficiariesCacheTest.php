<?php

namespace Tests\Feature\Servant;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineBeneficiariesCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_scoped_list_without_sensitive_data(): void
    {
        $group   = ServiceGroup::factory()->create(['name' => 'أسرة الاختبار']);
        $servant = User::factory()->create(['service_group_id' => $group->id]);
        $mine    = Beneficiary::factory()->create([
            'full_name'        => 'مخدوم الأوفلاين',
            'service_group_id' => $group->id,
        ]);
        Beneficiary::factory()->create([
            'full_name'        => 'مخدوم أسرة أخرى',
            'service_group_id' => ServiceGroup::factory()->create()->id,
        ]);

        $response = $this->actingAs($servant)
            ->getJson(route('servant.beneficiaries.offline-cache'))
            ->assertOk();

        $payload = $response->json();

        $this->assertNotNull($payload['updated_at']);
        $this->assertCount(1, $payload['beneficiaries']);
        $this->assertSame('مخدوم الأوفلاين', $payload['beneficiaries'][0]['name']);
        $this->assertSame('أسرة الاختبار', $payload['beneficiaries'][0]['group']);

        // Privacy contract: the cached payload must never carry contact or
        // medical/financial fields.
        $this->assertArrayNotHasKey('phone', $payload['beneficiaries'][0]);
        $this->assertArrayNotHasKey('medical_notes', $payload['beneficiaries'][0]);
        $this->assertArrayNotHasKey('guardian_phone', $payload['beneficiaries'][0]);
    }

    public function test_guests_cannot_pull_the_offline_payload(): void
    {
        $this->getJson(route('servant.beneficiaries.offline-cache'))
            ->assertUnauthorized();
    }
}
