<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Models\Beneficiary;
use App\Models\Medication;
use App\Models\PrayerRequest;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Verifies that sensitive PHI/PII fields are encrypted at rest so a
 * database-only leak cannot expose personal medical and financial data.
 */
class SensitiveFieldEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private Beneficiary $beneficiary;

    protected function setUp(): void
    {
        parent::setUp();

        $group = ServiceGroup::factory()->create();
        $actor = User::factory()->create();

        $this->beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'created_by'       => $actor->id,
        ]);
    }

    // ── Beneficiary ───────────────────────────────────────────

    public function test_beneficiary_medical_notes_are_encrypted_at_rest(): void
    {
        $this->beneficiary->update(['medical_notes' => 'يعاني من تشنجات مزمنة ويتناول دواء يومياً']);
        $raw = \DB::table('beneficiaries')->where('id', $this->beneficiary->id)->value('medical_notes');

        $this->assertNotEquals('يعاني من تشنجات مزمنة ويتناول دواء يومياً', $raw);
        $this->assertEquals('يعاني من تشنجات مزمنة ويتناول دواء يومياً', Crypt::decryptString($raw));
        $this->assertEquals('يعاني من تشنجات مزمنة ويتناول دواء يومياً', $this->beneficiary->fresh()->medical_notes);
    }

    public function test_beneficiary_financial_notes_are_encrypted_at_rest(): void
    {
        $this->beneficiary->update(['financial_notes' => 'الأسرة تعتمد على معاش الوالد المتوفى فقط']);
        $raw = \DB::table('beneficiaries')->where('id', $this->beneficiary->id)->value('financial_notes');

        $this->assertNotEquals('الأسرة تعتمد على معاش الوالد المتوفى فقط', $raw);
        $this->assertEquals('الأسرة تعتمد على معاش الوالد المتوفى فقط', $this->beneficiary->fresh()->financial_notes);
    }

    public function test_beneficiary_doctor_name_is_encrypted_at_rest(): void
    {
        $this->beneficiary->update(['doctor_name' => 'د. محمد عبدالله']);
        $raw = \DB::table('beneficiaries')->where('id', $this->beneficiary->id)->value('doctor_name');

        $this->assertNotEquals('د. محمد عبدالله', $raw);
        $this->assertEquals('د. محمد عبدالله', $this->beneficiary->fresh()->doctor_name);
    }

    public function test_beneficiary_hospital_name_is_encrypted_at_rest(): void
    {
        $this->beneficiary->update(['hospital_name' => 'مستشفى النزهة العام']);
        $raw = \DB::table('beneficiaries')->where('id', $this->beneficiary->id)->value('hospital_name');

        $this->assertNotEquals('مستشفى النزهة العام', $raw);
        $this->assertEquals('مستشفى النزهة العام', $this->beneficiary->fresh()->hospital_name);
    }

    public function test_beneficiary_encrypted_fields_accept_null(): void
    {
        $this->beneficiary->update([
            'medical_notes'   => null,
            'financial_notes' => null,
            'doctor_name'     => null,
            'hospital_name'   => null,
        ]);
        $fresh = $this->beneficiary->fresh();

        $this->assertNull($fresh->medical_notes);
        $this->assertNull($fresh->financial_notes);
        $this->assertNull($fresh->doctor_name);
        $this->assertNull($fresh->hospital_name);
    }

    // ── PrayerRequest ─────────────────────────────────────────

    public function test_prayer_request_body_is_encrypted_at_rest(): void
    {
        $actor  = User::factory()->create();
        $prayer = PrayerRequest::create([
            'beneficiary_id' => $this->beneficiary->id,
            'title'          => 'طلب شفاء',
            'body'           => 'اطلب الصلاة من أجل الشفاء الكامل والسلام الداخلي',
            'status'         => 'open',
            'created_by'     => $actor->id,
        ]);

        $raw = \DB::table('prayer_requests')->where('id', $prayer->id)->value('body');

        $this->assertNotEquals('اطلب الصلاة من أجل الشفاء الكامل والسلام الداخلي', $raw);
        $this->assertEquals('اطلب الصلاة من أجل الشفاء الكامل والسلام الداخلي', $prayer->fresh()->body);
    }

    // ── Medication ────────────────────────────────────────────

    public function test_medication_notes_are_encrypted_at_rest(): void
    {
        $med = Medication::create([
            'beneficiary_id' => $this->beneficiary->id,
            'name'           => 'ريسبيريدون',
            'dosage'         => '2mg',
            'frequency'      => 1,
            'timing'         => 'morning',
            'notes'          => 'يُعطى مع الطعام لتجنب الغثيان — تأثيره قد يكون غير منتظم',
            'is_active'      => true,
        ]);

        $raw = \DB::table('medications')->where('id', $med->id)->value('notes');

        $this->assertNotEquals('يُعطى مع الطعام لتجنب الغثيان — تأثيره قد يكون غير منتظم', $raw);
        $this->assertEquals('يُعطى مع الطعام لتجنب الغثيان — تأثيره قد يكون غير منتظم', $med->fresh()->notes);
    }
}
