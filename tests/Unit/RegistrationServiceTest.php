<?php
namespace Tests\Unit;

use App\Jobs\SendFcmNotificationJob;
use App\Models\AuditLog;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RegistrationService::class);
        Queue::fake();
    }

    #[Test]
    public function register_creates_user_account_successfully(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $data         = [
            'name'     => 'Test Servant',
            'email'    => 'servant@example.com',
            'phone'    => '01234567890',
            'password' => 'password123',
            'token'    => 'test-token-123',
        ];

        $user = $this->service->register($data, $serviceGroup, '127.0.0.1');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test Servant', $user->name);
        $this->assertEquals('servant@example.com', $user->email);
        $this->assertEquals('01234567890', $user->phone);
        $this->assertEquals('servant', $user->role);
        $this->assertEquals($serviceGroup->id, $user->service_group_id);
        $this->assertTrue($user->is_active);
        $this->assertEquals('ar', $user->locale);
        $this->assertNotNull($user->personal_code);
    }

    #[Test]
    public function register_creates_audit_log_entry(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $data         = [
            'name'     => 'Test Servant',
            'email'    => 'servant@example.com',
            'phone'    => '01234567890',
            'password' => 'password123',
            'token'    => 'test-token-123',
        ];

        $user = $this->service->register($data, $serviceGroup, '192.168.1.1');

        $auditLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'servant_self_registered')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals(User::class, $auditLog->model_type);
        $this->assertEquals($user->id, $auditLog->model_id);
        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
        $this->assertArrayHasKey('name', $auditLog->new_values);
        $this->assertArrayHasKey('email', $auditLog->new_values);
        $this->assertArrayHasKey('service_group_id', $auditLog->new_values);
    }

    #[Test]
    public function register_creates_notifications_for_leaders(): void
    {
        $leader        = User::factory()->create(['role' => 'family_leader', 'is_active' => true]);
        $serviceLeader = User::factory()->create(['role' => 'service_leader', 'is_active' => true]);
        $serviceGroup  = ServiceGroup::factory()->create([
            'leader_id'         => $leader->id,
            'service_leader_id' => $serviceLeader->id,
        ]);

        $data = [
            'name'     => 'Test Servant',
            'email'    => 'servant@example.com',
            'phone'    => '01234567890',
            'password' => 'password123',
            'token'    => 'test-token-123',
        ];

        $user = $this->service->register($data, $serviceGroup, '127.0.0.1');

        // Check all notifications
        $allNotifications = MinistryNotification::all();

        $leaderNotification = MinistryNotification::where('user_id', $leader->id)
            ->where('type', 'servant_registered')
            ->first();

        $serviceLeaderNotification = MinistryNotification::where('user_id', $serviceLeader->id)
            ->where('type', 'servant_registered')
            ->first();

        $this->assertNotNull($leaderNotification, 'Leader notification was not created. Total notifications: ' . $allNotifications->count());
        $this->assertNotNull($serviceLeaderNotification, 'Service leader notification was not created');
        $this->assertNotNull($leaderNotification->body);
        $this->assertNotNull($leaderNotification->title);
    }

    #[Test]
    public function register_dispatches_fcm_job_for_leaders_with_tokens(): void
    {
        $leader = User::factory()->create([
            'role'      => 'family_leader',
            'is_active' => true,
            'fcm_token' => 'fcm-token-123',
        ]);
        $serviceGroup = ServiceGroup::factory()->create([
            'leader_id' => $leader->id,
        ]);

        $data = [
            'name'     => 'Test Servant',
            'email'    => 'servant@example.com',
            'phone'    => '01234567890',
            'password' => 'password123',
            'token'    => 'test-token-123',
        ];

        $this->service->register($data, $serviceGroup, '127.0.0.1');

        Queue::assertPushed(SendFcmNotificationJob::class);
    }

    #[Test]
    public function register_does_not_dispatch_fcm_job_when_no_tokens(): void
    {
        $leader = User::factory()->create([
            'role'      => 'family_leader',
            'fcm_token' => null,
        ]);
        $serviceGroup = ServiceGroup::factory()->create([
            'leader_id' => $leader->id,
        ]);

        $data = [
            'name'     => 'Test Servant',
            'email'    => 'servant@example.com',
            'phone'    => '01234567890',
            'password' => 'password123',
            'token'    => 'test-token-123',
        ];

        $this->service->register($data, $serviceGroup, '127.0.0.1');

        Queue::assertNotPushed(SendFcmNotificationJob::class);
    }

    #[Test]
    public function register_rolls_back_on_failure(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();

        // Create a user with duplicate email to cause constraint violation
        User::factory()->create(['email' => 'duplicate@example.com']);

        $data = [
            'name'     => 'Test Servant',
            'email'    => 'duplicate@example.com', // Duplicate email will cause failure
            'phone'    => '01234567890',
            'password' => 'password123',
            'token'    => 'test-token-123',
        ];

        $initialUserCount  = User::count();
        $initialAuditCount = AuditLog::count();

        try {
            $this->service->register($data, $serviceGroup, '127.0.0.1');
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify no additional user was created
        $this->assertEquals($initialUserCount, User::count());

        // Verify no audit log was created
        $this->assertEquals($initialAuditCount, AuditLog::count());
    }

    #[Test]
    public function checkDuplicates_detects_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $result = $this->service->checkDuplicates('existing@example.com', '01111111111');

        $this->assertTrue($result['email']);
        $this->assertFalse($result['phone']);
    }

    #[Test]
    public function checkDuplicates_detects_existing_phone(): void
    {
        User::factory()->create(['phone' => '01234567890']);

        $result = $this->service->checkDuplicates('new@example.com', '01234567890');

        $this->assertFalse($result['email']);
        $this->assertTrue($result['phone']);
    }

    #[Test]
    public function checkDuplicates_detects_both_duplicates(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'phone' => '01234567890',
        ]);

        $result = $this->service->checkDuplicates('existing@example.com', '01234567890');

        $this->assertTrue($result['email']);
        $this->assertTrue($result['phone']);
    }

    #[Test]
    public function checkDuplicates_returns_false_for_new_credentials(): void
    {
        $result = $this->service->checkDuplicates('new@example.com', '01111111111');

        $this->assertFalse($result['email']);
        $this->assertFalse($result['phone']);
    }

    #[Test]
    public function notifyLeaders_handles_service_group_without_leaders(): void
    {
        $serviceGroup = ServiceGroup::factory()->create([
            'leader_id'         => null,
            'service_leader_id' => null,
        ]);
        $servant = User::factory()->create(['role' => 'servant']);

        // Should not throw exception
        $this->service->notifyLeaders($servant, $serviceGroup);

        $this->assertEquals(0, MinistryNotification::count());
    }

    #[Test]
    public function notifyLeaders_skips_inactive_leaders(): void
    {
        $inactiveLeader = User::factory()->create([
            'role'      => 'family_leader',
            'is_active' => false,
        ]);
        $serviceGroup = ServiceGroup::factory()->create([
            'leader_id' => $inactiveLeader->id,
        ]);
        $servant = User::factory()->create(['role' => 'servant']);

        $this->service->notifyLeaders($servant, $serviceGroup);

        $this->assertEquals(0, MinistryNotification::where('user_id', $inactiveLeader->id)->count());
    }

    #[Test]
    public function logRegistration_creates_audit_log_with_correct_data(): void
    {
        $serviceGroup = ServiceGroup::factory()->create(['name' => 'Test Group']);
        $user         = User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
            'phone' => '01234567890',
        ]);

        $this->service->logRegistration($user, $serviceGroup, 'token123456', '192.168.1.100');

        $auditLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'servant_self_registered')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('192.168.1.100', $auditLog->ip_address);
        $this->assertEquals('Test User', $auditLog->new_values['name']);
        $this->assertEquals('test@example.com', $auditLog->new_values['email']);
        $this->assertEquals('Test Group', $auditLog->new_values['service_group_name']);
        $this->assertStringContainsString('token123', $auditLog->new_values['registration_token']);
    }
}
