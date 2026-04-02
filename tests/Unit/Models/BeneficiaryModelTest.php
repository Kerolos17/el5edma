<?php

namespace Tests\Unit\Models;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_generates_code_on_create(): void
    {
        $ben = Beneficiary::factory()->create(['code' => null]);
        $ben->refresh();
        $this->assertStringStartsWith('SN-', $ben->code);
    }

    public function test_photo_url_returns_null_when_no_photo(): void
    {
        $ben = Beneficiary::factory()->create(['photo' => null]);
        $this->assertNull($ben->photo_url);
    }

    public function test_photo_url_returns_path_when_photo_exists(): void
    {
        $ben = Beneficiary::factory()->create(['photo' => 'beneficiaries/test.jpg']);
        $this->assertSame('/storage/beneficiaries/test.jpg', $ben->photo_url);
    }

    public function test_whatsapp_url_uses_phone_fallback(): void
    {
        $ben = Beneficiary::factory()->create(['whatsapp' => null, 'phone' => '01234567890']);
        $this->assertStringContainsString('wa.me', $ben->whatsapp_url);
    }

    public function test_whatsapp_url_returns_null_when_no_contact(): void
    {
        $ben = Beneficiary::factory()->create(['whatsapp' => null, 'phone' => null]);
        $this->assertNull($ben->whatsapp_url);
    }

    public function test_relationships(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = User::factory()->create();
        $ben = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'assigned_servant_id' => $servant->id,
        ]);

        $this->assertInstanceOf(ServiceGroup::class, $ben->serviceGroup);
        $this->assertInstanceOf(User::class, $ben->assignedServant);
    }

    public function test_casts(): void
    {
        $ben = Beneficiary::factory()->create(['birth_date' => '2000-01-15']);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $ben->birth_date);
    }
}
