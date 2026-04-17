<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function generate_unique_personal_code_generates_a_valid_code(): void
    {
        $code = User::generateUniquePersonalCode();

        $this->assertIsString($code);
        $this->assertMatchesRegularExpression('/^\d{4,6}$/', $code);
    }

    #[Test]
    public function generate_unique_personal_code_generates_unique_codes(): void
    {
        $codes = [];

        for ($i = 0; $i < 10; $i++) {
            $code = User::generateUniquePersonalCode();
            $this->assertNotContains($code, $codes);
            $codes[] = $code;
        }
    }

    #[Test]
    public function generate_unique_personal_code_avoids_existing_codes(): void
    {
        // Create a user with a specific personal code
        $existingCode = '1234';
        User::factory()->create(['personal_code' => $existingCode]);

        // Generate 50 codes and ensure none match the existing one
        $codes = [];
        for ($i = 0; $i < 50; $i++) {
            $codes[] = User::generateUniquePersonalCode();
        }

        // Decrypt the existing code to compare
        $user                  = User::first();
        $decryptedExistingCode = $user->personal_code;

        $this->assertNotContains($decryptedExistingCode, $codes);
    }

    #[Test]
    public function create_from_self_registration_creates_user_with_correct_attributes(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();

        $data = [
            'name'     => 'Test Servant',
            'email'    => 'servant@example.com',
            'phone'    => '01234567890',
            'password' => 'password123',
        ];

        $user = User::createFromSelfRegistration($data, $serviceGroup);

        $this->assertEquals('Test Servant', $user->name);
        $this->assertEquals('servant@example.com', $user->email);
        $this->assertEquals('01234567890', $user->phone);
        $this->assertEquals(UserRole::Servant, $user->role);
        $this->assertEquals($serviceGroup->id, $user->service_group_id);
        $this->assertEquals(app()->getLocale(), $user->locale);
        $this->assertFalse($user->is_active);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $user->personal_code);
    }

    #[Test]
    public function create_from_self_registration_hashes_password(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();

        $data = [
            'name'     => 'Test Servant',
            'email'    => 'servant@example.com',
            'phone'    => '01234567890',
            'password' => 'password123',
        ];

        $user = User::createFromSelfRegistration($data, $serviceGroup);

        // Password should be hashed, not plain text
        $this->assertNotEquals('password123', $user->getAttributes()['password']);
        $this->assertTrue(password_verify('password123', $user->getAttributes()['password']));
    }

    #[Test]
    public function create_from_self_registration_generates_unique_personal_codes_for_multiple_users(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();

        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $data = [
                'name'     => "Servant $i",
                'email'    => "servant$i@example.com",
                'phone'    => "0123456789$i",
                'password' => 'password123',
            ];

            $users[] = User::createFromSelfRegistration($data, $serviceGroup);
        }

        // Extract all personal codes
        $personalCodes = array_map(fn ($user) => $user->personal_code, $users);

        // Ensure all codes are unique
        $this->assertCount(5, $personalCodes);
        $this->assertCount(5, array_unique($personalCodes));
    }

    #[Test]
    public function create_from_self_registration_links_user_to_service_group(): void
    {
        $serviceGroup = ServiceGroup::factory()->create(['name' => 'Test Group']);

        $data = [
            'name'     => 'Test Servant',
            'email'    => 'servant@example.com',
            'phone'    => '01234567890',
            'password' => 'password123',
        ];

        $user = User::createFromSelfRegistration($data, $serviceGroup);

        $this->assertNotNull($user->serviceGroup);
        $this->assertEquals($serviceGroup->id, $user->serviceGroup->id);
        $this->assertEquals('Test Group', $user->serviceGroup->name);
    }
}
