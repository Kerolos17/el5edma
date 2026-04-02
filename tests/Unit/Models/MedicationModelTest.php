<?php

namespace Tests\Unit\Models;

use App\Models\Beneficiary;
use App\Models\Medication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_beneficiary_relationship(): void
    {
        $ben      = Beneficiary::factory()->create();
        $med      = Medication::factory()->create(['beneficiary_id' => $ben->id]);

        $this->assertInstanceOf(Beneficiary::class, $med->beneficiary);
        $this->assertSame($ben->id, $med->beneficiary->id);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $med = Medication::factory()->create(['is_active' => true]);
        $med->refresh();

        $this->assertTrue($med->is_active);
    }

    public function test_factory_creates_active_medication_by_default(): void
    {
        $med = Medication::factory()->create();

        $this->assertTrue($med->is_active);
    }
}
