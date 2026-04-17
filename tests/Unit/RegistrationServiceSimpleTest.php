<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationServiceSimpleTest extends TestCase
{
    use RefreshDatabase;

    private RegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RegistrationService::class);
    }

    #[Test]
    public function register_creates_user_with_correct_attributes(): void
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

        $this->assertEquals('Test Servant', $user->name);
        $this->assertEquals('servant@example.com', $user->email);
        $this->assertEquals(UserRole::Servant, $user->role);
        $this->assertEquals($serviceGroup->id, $user->service_group_id);
        $this->assertFalse($user->is_active);
    }

    #[Test]
    public function check_duplicates_works_correctly(): void
    {
        User::factory()->create(['email' => 'existing@example.com', 'phone' => '01111111111']);

        $result1 = $this->service->checkDuplicates('existing@example.com', '02222222222');
        $this->assertTrue($result1['email']);
        $this->assertFalse($result1['phone']);

        $result2 = $this->service->checkDuplicates('new@example.com', '01111111111');
        $this->assertFalse($result2['email']);
        $this->assertTrue($result2['phone']);

        $result3 = $this->service->checkDuplicates('new@example.com', '02222222222');
        $this->assertFalse($result3['email']);
        $this->assertFalse($result3['phone']);
    }

    #[Test]
    public function log_registration_creates_audit_entry(): void
    {
        $serviceGroup = ServiceGroup::factory()->create(['name' => 'Test Group']);
        $user         = User::factory()->create();

        $this->service->logRegistration($user, $serviceGroup, 'token123', '192.168.1.1');

        $log = AuditLog::where('user_id', $user->id)
            ->where('action', 'servant_self_registered')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('192.168.1.1', $log->ip_address);
    }
}
