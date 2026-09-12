<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Jobs\SendFcmNotificationJob;
use App\Models\AuditLog;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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
        $this->assertEquals(UserRole::Servant, $user->role);
        $this->assertEquals($serviceGroup->id, $user->service_group_id);
        $this->assertFalse($user->is_active);
        $this->assertEquals(app()->getLocale(), $user->locale);
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

        $this->service->register($data, $serviceGroup, '127.0.0.1');

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
    public function register_notifies_only_relevant_leaders_and_super_admins(): void
    {
        $familyLeader = User::factory()->create([
            'role'      => UserRole::FamilyLeader,
            'is_active' => true,
        ]);
        $serviceLeader = User::factory()->create([
            'role'      => UserRole::ServiceLeader,
            'is_active' => true,
        ]);
        $unrelatedServiceLeader = User::factory()->create([
            'role'      => UserRole::ServiceLeader,
            'is_active' => true,
        ]);
        $superAdmin = User::factory()->create([
            'role'      => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $serviceGroup = ServiceGroup::factory()->create([
            'leader_id'         => $familyLeader->id,
            'service_leader_id' => $serviceLeader->id,
        ]);

        $this->service->register([
            'name'     => 'Scoped Servant',
            'email'    => 'scoped-servant@example.com',
            'phone'    => '01234567891',
            'password' => 'password123',
            'token'    => 'scope-token-123',
        ], $serviceGroup, '127.0.0.1');

        $this->assertDatabaseHas('ministry_notifications', [
            'user_id' => $familyLeader->id,
            'type'    => 'servant_registered',
        ]);
        $this->assertDatabaseHas('ministry_notifications', [
            'user_id' => $serviceLeader->id,
            'type'    => 'servant_registered',
        ]);
        $this->assertDatabaseHas('ministry_notifications', [
            'user_id' => $superAdmin->id,
            'type'    => 'servant_registered',
        ]);
        $this->assertDatabaseMissing('ministry_notifications', [
            'user_id' => $unrelatedServiceLeader->id,
            'type'    => 'servant_registered',
        ]);
    }

    #[Test]
    public function registration_succeeds_when_welcome_notification_creation_fails(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();

        $service = new class extends RegistrationService
        {
            protected function createWelcomeNotification(User $newServant, ServiceGroup $serviceGroup): void
            {
                throw new \RuntimeException('simulated welcome notification failure');
            }
        };

        $user = $service->register([
            'name'     => 'Resilient Servant',
            'email'    => 'resilient-welcome@example.com',
            'phone'    => '01234567892',
            'password' => 'password123',
            'token'    => 'resilient-token-1',
        ], $serviceGroup, '127.0.0.1');

        $this->assertDatabaseHas('users', [
            'id'    => $user->id,
            'email' => 'resilient-welcome@example.com',
        ]);
        $this->assertFalse($user->is_active);
    }

    #[Test]
    public function registration_succeeds_when_leader_notification_creation_fails(): void
    {
        $leader = User::factory()->create([
            'role'      => UserRole::FamilyLeader,
            'is_active' => true,
        ]);
        $serviceGroup = ServiceGroup::factory()->create([
            'leader_id' => $leader->id,
        ]);

        $service = new class extends RegistrationService
        {
            protected function createNotificationRecords(
                User $newServant,
                ServiceGroup $serviceGroup,
                Collection $leaders,
            ): void {
                throw new \RuntimeException('simulated leader notification failure');
            }
        };

        $user = $service->register([
            'name'     => 'Resilient Servant',
            'email'    => 'resilient-leader@example.com',
            'phone'    => '01234567893',
            'password' => 'password123',
            'token'    => 'resilient-token-2',
        ], $serviceGroup, '127.0.0.1');

        $this->assertDatabaseHas('users', [
            'id'    => $user->id,
            'email' => 'resilient-leader@example.com',
        ]);
        $this->assertFalse($user->is_active);
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

        User::factory()->create(['email' => 'duplicate@example.com']);

        $data = [
            'name'     => 'Test Servant',
            'email'    => 'duplicate@example.com',
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

        $this->assertEquals($initialUserCount, User::count());
        $this->assertEquals($initialAuditCount, AuditLog::count());
    }

    #[Test]
    public function check_duplicates_detects_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $result = $this->service->checkDuplicates('existing@example.com', '01111111111');

        $this->assertTrue($result['email']);
        $this->assertFalse($result['phone']);
    }

    #[Test]
    public function check_duplicates_detects_existing_phone(): void
    {
        User::factory()->create(['phone' => '01234567890']);

        $result = $this->service->checkDuplicates('new@example.com', '01234567890');

        $this->assertFalse($result['email']);
        $this->assertTrue($result['phone']);
    }

    #[Test]
    public function check_duplicates_detects_both_duplicates(): void
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
    public function check_duplicates_returns_false_for_new_credentials(): void
    {
        $result = $this->service->checkDuplicates('new@example.com', '01111111111');

        $this->assertFalse($result['email']);
        $this->assertFalse($result['phone']);
    }

    #[Test]
    public function notify_leaders_handles_service_group_without_leaders(): void
    {
        $serviceGroup = ServiceGroup::factory()->create([
            'leader_id'         => null,
            'service_leader_id' => null,
        ]);
        $servant = User::factory()->create(['role' => 'servant']);

        $this->service->notifyLeaders($servant, $serviceGroup);

        $this->assertEquals(0, MinistryNotification::count());
    }

    #[Test]
    public function notify_leaders_skips_inactive_leaders(): void
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
    public function log_registration_creates_audit_log_with_correct_data(): void
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
        $this->assertEquals('token123...', $auditLog->new_values['registration_token']);
    }
}
