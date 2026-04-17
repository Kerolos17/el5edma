<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * اختبارات method logSelfRegistration في AuditLog model
 * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
 */
class AuditLogSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function log_self_registration_creates_audit_log_entry(): void
    {
        $serviceGroup = ServiceGroup::factory()->create(['name' => 'Test Group']);
        $user         = User::factory()->create([
            'name'             => 'Test User',
            'email'            => 'test@example.com',
            'phone'            => '01234567890',
            'service_group_id' => $serviceGroup->id,
        ]);

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, 'token123456789', '192.168.1.100');

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertDatabaseHas('audit_logs', [
            'user_id'    => $user->id,
            'model_type' => User::class,
            'model_id'   => $user->id,
            'action'     => 'servant_self_registered',
        ]);
    }

    #[Test]
    public function log_self_registration_records_user_id(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create(['service_group_id' => $serviceGroup->id]);

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, 'token123', '127.0.0.1');

        $this->assertEquals($user->id, $auditLog->user_id);
    }

    #[Test]
    public function log_self_registration_records_model_type_and_id(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create(['service_group_id' => $serviceGroup->id]);

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, 'token123', '127.0.0.1');

        $this->assertEquals(User::class, $auditLog->model_type);
        $this->assertEquals($user->id, $auditLog->model_id);
    }

    #[Test]
    public function log_self_registration_records_action_as_servant_self_registered(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create(['service_group_id' => $serviceGroup->id]);

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, 'token123', '127.0.0.1');

        $this->assertEquals('servant_self_registered', $auditLog->action);
    }

    #[Test]
    public function log_self_registration_records_ip_address(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create(['service_group_id' => $serviceGroup->id]);

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, 'token123', '192.168.1.50');

        $this->assertEquals('192.168.1.50', $auditLog->ip_address);
    }

    #[Test]
    public function log_self_registration_records_user_details_in_new_values(): void
    {
        $serviceGroup = ServiceGroup::factory()->create(['name' => 'Test Group']);
        $user         = User::factory()->create([
            'name'             => 'John Doe',
            'email'            => 'john@example.com',
            'phone'            => '01234567890',
            'service_group_id' => $serviceGroup->id,
        ]);

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, 'token123', '127.0.0.1');

        $this->assertArrayHasKey('name', $auditLog->new_values);
        $this->assertArrayHasKey('email', $auditLog->new_values);
        $this->assertArrayHasKey('phone', $auditLog->new_values);
        $this->assertEquals('John Doe', $auditLog->new_values['name']);
        $this->assertEquals('john@example.com', $auditLog->new_values['email']);
        $this->assertEquals('01234567890', $auditLog->new_values['phone']);
    }

    #[Test]
    public function log_self_registration_records_service_group_details_in_new_values(): void
    {
        $serviceGroup = ServiceGroup::factory()->create(['name' => 'Test Service Group']);
        $user         = User::factory()->create(['service_group_id' => $serviceGroup->id]);

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, 'token123', '127.0.0.1');

        $this->assertArrayHasKey('service_group_id', $auditLog->new_values);
        $this->assertArrayHasKey('service_group_name', $auditLog->new_values);
        $this->assertEquals($serviceGroup->id, $auditLog->new_values['service_group_id']);
        $this->assertEquals('Test Service Group', $auditLog->new_values['service_group_name']);
    }

    #[Test]
    public function log_self_registration_records_partial_token_for_security(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create(['service_group_id' => $serviceGroup->id]);
        $fullToken    = 'abcdefgh12345678901234567890123456789012345678901234567890';

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, $fullToken, '127.0.0.1');

        $this->assertArrayHasKey('registration_token', $auditLog->new_values);
        $this->assertStringStartsWith('abcdefgh', $auditLog->new_values['registration_token']);
        $this->assertStringEndsWith('...', $auditLog->new_values['registration_token']);
        $this->assertNotEquals($fullToken, $auditLog->new_values['registration_token']);
        $this->assertEquals('abcdefgh...', $auditLog->new_values['registration_token']);
    }

    #[Test]
    public function log_self_registration_old_values_is_null(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create(['service_group_id' => $serviceGroup->id]);

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, 'token123', '127.0.0.1');

        $this->assertNull($auditLog->old_values);
    }

    #[Test]
    public function log_self_registration_handles_short_tokens(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create(['service_group_id' => $serviceGroup->id]);
        $shortToken   = 'short';

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, $shortToken, '127.0.0.1');

        $this->assertArrayHasKey('registration_token', $auditLog->new_values);
        $this->assertEquals('short...', $auditLog->new_values['registration_token']);
    }

    #[Test]
    public function log_self_registration_creates_immutable_record(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create(['service_group_id' => $serviceGroup->id]);

        $auditLog = AuditLog::logSelfRegistration($user, $serviceGroup, 'token123', '127.0.0.1');

        // Refresh to get database-generated created_at
        $auditLog->refresh();

        // AuditLog model has $timestamps = false, so updated_at should not exist
        $this->assertNull($auditLog->updated_at);
        $this->assertNotNull($auditLog->created_at);
    }

    #[Test]
    public function log_self_registration_can_be_queried_by_action(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $user1        = User::factory()->create(['service_group_id' => $serviceGroup->id]);
        $user2        = User::factory()->create(['service_group_id' => $serviceGroup->id]);

        AuditLog::logSelfRegistration($user1, $serviceGroup, 'token123', '127.0.0.1');
        AuditLog::logSelfRegistration($user2, $serviceGroup, 'token456', '127.0.0.2');

        // Create other audit logs with different actions
        AuditLog::create([
            'user_id'    => $user1->id,
            'model_type' => User::class,
            'model_id'   => $user1->id,
            'action'     => 'created',
            'old_values' => null,
            'new_values' => ['name' => 'Test'],
            'ip_address' => '127.0.0.1',
        ]);

        $selfRegistrationLogs = AuditLog::where('action', 'servant_self_registered')->get();

        $this->assertCount(2, $selfRegistrationLogs);
    }

    #[Test]
    public function log_self_registration_can_be_queried_by_service_group(): void
    {
        $serviceGroup1 = ServiceGroup::factory()->create();
        $serviceGroup2 = ServiceGroup::factory()->create();
        $user1         = User::factory()->create(['service_group_id' => $serviceGroup1->id]);
        $user2         = User::factory()->create(['service_group_id' => $serviceGroup2->id]);

        AuditLog::logSelfRegistration($user1, $serviceGroup1, 'token123', '127.0.0.1');
        AuditLog::logSelfRegistration($user2, $serviceGroup2, 'token456', '127.0.0.2');

        $group1Logs = AuditLog::where('action', 'servant_self_registered')
            ->whereJsonContains('new_values->service_group_id', $serviceGroup1->id)
            ->get();

        $this->assertCount(1, $group1Logs);
        $this->assertEquals($user1->id, $group1Logs->first()->user_id);
    }
}
