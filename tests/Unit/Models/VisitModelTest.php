<?php

namespace Tests\Unit\Models;

use App\Models\Beneficiary;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_beneficiary_relationship(): void
    {
        $ben   = Beneficiary::factory()->create();
        $visit = Visit::factory()->create(['beneficiary_id' => $ben->id]);

        $this->assertInstanceOf(Beneficiary::class, $visit->beneficiary);
        $this->assertSame($ben->id, $visit->beneficiary->id);
    }

    public function test_created_by_relationship(): void
    {
        $user  = User::factory()->create();
        $visit = Visit::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $visit->createdBy);
        $this->assertSame($user->id, $visit->createdBy->id);
    }

    public function test_resolved_by_relationship(): void
    {
        $resolver = User::factory()->create();
        $visit    = Visit::factory()->create(['critical_resolved_by' => $resolver->id]);

        $this->assertInstanceOf(User::class, $visit->resolvedBy);
        $this->assertSame($resolver->id, $visit->resolvedBy->id);
    }

    public function test_servants_many_to_many_relationship(): void
    {
        $servant = User::factory()->create();
        $visit   = Visit::factory()->create();
        $visit->servants()->attach($servant->id);

        $this->assertCount(1, $visit->servants);
        $this->assertSame($servant->id, $visit->servants->first()->id);
    }

    public function test_boolean_casts(): void
    {
        $visit = Visit::factory()->create([
            'is_critical'          => true,
            'needs_family_leader'  => true,
            'needs_service_leader' => false,
        ]);
        $visit->refresh();

        $this->assertTrue($visit->is_critical);
        $this->assertTrue($visit->needs_family_leader);
        $this->assertFalse($visit->needs_service_leader);
    }

    public function test_visit_date_is_cast_to_carbon(): void
    {
        $visit = Visit::factory()->create(['visit_date' => '2024-01-15 10:00:00']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $visit->visit_date);
    }
}
