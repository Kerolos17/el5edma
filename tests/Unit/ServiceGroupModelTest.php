<?php
namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * اختبارات methods التسجيل الذاتي في ServiceGroup model
 * Requirements: 1.5, 7.5
 */
class ServiceGroupModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function hasActiveRegistrationToken_returns_true_when_token_exists(): void
    {
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token'              => str_repeat('a', 64),
            'registration_token_generated_at' => now(),
        ]);

        $this->assertTrue($serviceGroup->hasActiveRegistrationToken());
    }

    #[Test]
    public function hasActiveRegistrationToken_returns_false_when_token_is_null(): void
    {
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token'              => null,
            'registration_token_generated_at' => null,
        ]);

        $this->assertFalse($serviceGroup->hasActiveRegistrationToken());
    }

    #[Test]
    public function hasActiveRegistrationToken_returns_false_when_token_is_empty_string(): void
    {
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token'              => '',
            'registration_token_generated_at' => now(),
        ]);

        $this->assertFalse($serviceGroup->hasActiveRegistrationToken());
    }

    #[Test]
    public function getSelfRegisteredServantsCount_returns_zero_when_no_registrations(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();

        $count = $serviceGroup->getSelfRegisteredServantsCount();

        $this->assertEquals(0, $count);
    }

    #[Test]
    public function getSelfRegisteredServantsCount_returns_correct_count_for_single_registration(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create([
            'service_group_id' => $serviceGroup->id,
            'role'             => 'servant',
        ]);

        // Create audit log for self-registration
        AuditLog::create([
            'user_id'    => $user->id,
            'model_type' => User::class,
            'model_id'   => $user->id,
            'action'     => 'servant_self_registered',
            'old_values' => null,
            'new_values' => [
                'name'             => $user->name,
                'email'            => $user->email,
                'service_group_id' => $serviceGroup->id,
            ],
            'ip_address' => '127.0.0.1',
        ]);

        $count = $serviceGroup->getSelfRegisteredServantsCount();

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function getSelfRegisteredServantsCount_returns_correct_count_for_multiple_registrations(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();

        // Create 5 self-registered servants
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create([
                'service_group_id' => $serviceGroup->id,
                'role'             => 'servant',
            ]);

            AuditLog::create([
                'user_id'    => $user->id,
                'model_type' => User::class,
                'model_id'   => $user->id,
                'action'     => 'servant_self_registered',
                'old_values' => null,
                'new_values' => [
                    'name'             => $user->name,
                    'email'            => $user->email,
                    'service_group_id' => $serviceGroup->id,
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        $count = $serviceGroup->getSelfRegisteredServantsCount();

        $this->assertEquals(5, $count);
    }

    #[Test]
    public function getSelfRegisteredServantsCount_only_counts_self_registered_servants(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();

        // Create 3 self-registered servants
        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create([
                'service_group_id' => $serviceGroup->id,
                'role'             => 'servant',
            ]);

            AuditLog::create([
                'user_id'    => $user->id,
                'model_type' => User::class,
                'model_id'   => $user->id,
                'action'     => 'servant_self_registered',
                'old_values' => null,
                'new_values' => [
                    'service_group_id' => $serviceGroup->id,
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        // Create 2 manually created servants (no audit log with servant_self_registered action)
        for ($i = 0; $i < 2; $i++) {
            $user = User::factory()->create([
                'service_group_id' => $serviceGroup->id,
                'role'             => 'servant',
            ]);

            AuditLog::create([
                'user_id'    => $user->id,
                'model_type' => User::class,
                'model_id'   => $user->id,
                'action'     => 'created', // Different action
                'old_values' => null,
                'new_values' => [
                    'service_group_id' => $serviceGroup->id,
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        $count = $serviceGroup->getSelfRegisteredServantsCount();

        // Should only count the 3 self-registered servants
        $this->assertEquals(3, $count);
    }

    #[Test]
    public function getSelfRegisteredServantsCount_only_counts_for_specific_service_group(): void
    {
        $serviceGroup1 = ServiceGroup::factory()->create();
        $serviceGroup2 = ServiceGroup::factory()->create();

        // Create 3 servants for service group 1
        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create([
                'service_group_id' => $serviceGroup1->id,
                'role'             => 'servant',
            ]);

            AuditLog::create([
                'user_id'    => $user->id,
                'model_type' => User::class,
                'model_id'   => $user->id,
                'action'     => 'servant_self_registered',
                'old_values' => null,
                'new_values' => [
                    'service_group_id' => $serviceGroup1->id,
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        // Create 2 servants for service group 2
        for ($i = 0; $i < 2; $i++) {
            $user = User::factory()->create([
                'service_group_id' => $serviceGroup2->id,
                'role'             => 'servant',
            ]);

            AuditLog::create([
                'user_id'    => $user->id,
                'model_type' => User::class,
                'model_id'   => $user->id,
                'action'     => 'servant_self_registered',
                'old_values' => null,
                'new_values' => [
                    'service_group_id' => $serviceGroup2->id,
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        $count1 = $serviceGroup1->getSelfRegisteredServantsCount();
        $count2 = $serviceGroup2->getSelfRegisteredServantsCount();

        $this->assertEquals(3, $count1);
        $this->assertEquals(2, $count2);
    }

    #[Test]
    public function registration_token_generated_at_is_cast_to_datetime(): void
    {
        $now          = now();
        $serviceGroup = ServiceGroup::factory()->create([
            'registration_token'              => str_repeat('a', 64),
            'registration_token_generated_at' => $now,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $serviceGroup->registration_token_generated_at);
        $this->assertEquals($now->timestamp, $serviceGroup->registration_token_generated_at->timestamp);
    }

    #[Test]
    public function registration_token_fields_are_fillable(): void
    {
        $serviceGroup = ServiceGroup::create([
            'name'                            => 'Test Group',
            'is_active'                       => true,
            'registration_token'              => str_repeat('b', 64),
            'registration_token_generated_at' => now(),
        ]);

        $this->assertNotNull($serviceGroup->registration_token);
        $this->assertNotNull($serviceGroup->registration_token_generated_at);
    }
}
