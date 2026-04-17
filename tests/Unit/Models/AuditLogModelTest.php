<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_self_registration(): void
    {
        $group = ServiceGroup::factory()->create();
        $user  = User::factory()->create(['service_group_id' => $group->id]);
        $token = 'abcdef1234567890abcdef1234567890';

        $log = AuditLog::logSelfRegistration($user, $group, $token, '192.168.1.1');

        $this->assertSame('servant_self_registered', $log->action);
        $this->assertSame(User::class, $log->model_type);
        $this->assertSame($user->id, $log->model_id);
        $this->assertSame('192.168.1.1', $log->ip_address);
    }

    public function test_log_self_registration_truncates_token(): void
    {
        $group = ServiceGroup::factory()->create();
        $user  = User::factory()->create(['service_group_id' => $group->id]);
        $token = 'abcdefgh1234567890abcdefgh1234567890';

        $log = AuditLog::logSelfRegistration($user, $group, $token, '127.0.0.1');

        $this->assertSame('abcdefgh...', $log->new_values['registration_token']);
    }

    public function test_casts_values_as_arrays(): void
    {
        $log = AuditLog::factory()->create([
            'old_values' => ['key' => 'old'],
            'new_values' => ['key' => 'new'],
        ]);
        $log->refresh();

        $this->assertIsArray($log->old_values);
        $this->assertIsArray($log->new_values);
    }

    public function test_user_relationship(): void
    {
        $user = User::factory()->create();
        $log  = AuditLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertSame($user->id, $log->user->id);
    }
}
