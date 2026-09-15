<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\PrayerRequest;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AuditLogSanitizationTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    #[Test]
    public function beneficiary_observer_excludes_pii_phi_from_audit_log(): void
    {
        $admin = $this->createSuperAdmin();
        $group = ServiceGroup::factory()->create();
        Auth::login($admin);

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'created_by'       => $admin->id,
        ]);

        $audit = AuditLog::where('model_type', Beneficiary::class)
            ->where('model_id', $beneficiary->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertNull($audit->new_values['full_name'] ?? null, 'full_name must not be in audit');
        $this->assertNull($audit->new_values['phone'] ?? null, 'phone must not be in audit');
        $this->assertNull($audit->new_values['medical_notes'] ?? null, 'medical_notes must not be in audit');
        $this->assertNull($audit->new_values['financial_notes'] ?? null, 'financial_notes must not be in audit');
        $this->assertNull($audit->new_values['doctor_name'] ?? null, 'doctor_name must not be in audit');
        $this->assertNull($audit->new_values['hospital_name'] ?? null, 'hospital_name must not be in audit');
        $this->assertNull($audit->new_values['guardian_phone'] ?? null, 'guardian_phone must not be in audit');
        $this->assertNull($audit->new_values['address_text'] ?? null, 'address_text must not be in audit');
        $this->assertArrayHasKey('service_group_id', $audit->new_values, 'service_group_id should still be logged');
    }

    #[Test]
    public function prayer_request_observer_excludes_body_from_audit_log(): void
    {
        $admin = $this->createSuperAdmin();
        Auth::login($admin);

        $group       = ServiceGroup::factory()->create();
        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);
        $prayer      = PrayerRequest::factory()->create(['beneficiary_id' => $beneficiary->id]);

        $audit = AuditLog::where('model_type', PrayerRequest::class)
            ->where('model_id', $prayer->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertNull($audit->new_values['body'] ?? null, 'body must not be in audit');
        $this->assertArrayHasKey('beneficiary_id', $audit->new_values);
    }

    #[Test]
    public function medical_file_observer_excludes_file_path_from_audit_log(): void
    {
        $admin = $this->createSuperAdmin();
        Auth::login($admin);

        $group       = ServiceGroup::factory()->create();
        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);
        $file        = MedicalFile::factory()->create(['beneficiary_id' => $beneficiary->id]);

        $audit = AuditLog::where('model_type', MedicalFile::class)
            ->where('model_id', $file->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertNull($audit->new_values['file_path'] ?? null, 'file_path must not be in audit');
        $this->assertArrayHasKey('beneficiary_id', $audit->new_values);
    }

    #[Test]
    public function user_observer_excludes_name_email_phone_from_audit_log(): void
    {
        $admin = $this->createSuperAdmin();
        Auth::login($admin);

        $user = User::factory()->create(['role' => 'servant']);

        $audit = AuditLog::where('model_type', User::class)
            ->where('model_id', $user->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertNull($audit->new_values['name'] ?? null, 'name must not be in audit');
        $this->assertNull($audit->new_values['email'] ?? null, 'email must not be in audit');
        $this->assertNull($audit->new_values['phone'] ?? null, 'phone must not be in audit');
        $this->assertArrayHasKey('role', $audit->new_values, 'role should still be logged');
    }

    #[Test]
    public function log_self_registration_excludes_pii(): void
    {
        $admin = $this->createSuperAdmin();
        Auth::login($admin);

        $user  = User::factory()->create(['role' => 'servant']);
        $group = ServiceGroup::factory()->create();

        AuditLog::logSelfRegistration($user, $group, 'test-token-1234567890', '127.0.0.1');

        $audit = AuditLog::where('action', 'servant_self_registered')
            ->where('model_id', $user->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertNull($audit->new_values['name'] ?? null, 'name must not be in self-registration audit');
        $this->assertNull($audit->new_values['email'] ?? null, 'email must not be in self-registration audit');
        $this->assertNull($audit->new_values['phone'] ?? null, 'phone must not be in self-registration audit');
        $this->assertArrayHasKey('service_group_id', $audit->new_values);
    }

    #[Test]
    public function registration_service_creates_user_without_pii_in_audit(): void
    {
        $admin = $this->createSuperAdmin();
        Auth::login($admin);

        $serviceGroup = ServiceGroup::factory()->create();
        $data         = [
            'name'     => 'Test Servant',
            'email'    => 'servant@example.com',
            'phone'    => '01234567890',
            'password' => 'password123',
            'token'    => 'test-token-123',
        ];

        $user = app(RegistrationService::class)->register($data, $serviceGroup, '127.0.0.1');

        $audit = AuditLog::where('action', 'servant_self_registered')
            ->where('model_id', $user->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertNull($audit->new_values['name'] ?? null, 'email must not appear in self-registration audit');
        $this->assertNull($audit->new_values['email'] ?? null, 'email must not appear in self-registration audit');
        $this->assertNull($audit->new_values['phone'] ?? null, 'phone must not appear in self-registration audit');
    }
}
