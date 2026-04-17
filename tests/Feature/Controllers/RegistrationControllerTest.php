<?php

namespace Tests\Feature\Controllers;

use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_with_valid_token_returns_form(): void
    {
        $group = ServiceGroup::factory()->create([
            'registration_token'              => 'valid-token-123',
            'is_active'                       => true,
            'registration_token_generated_at' => now(),
        ]);

        $response = $this->get('/register/valid-token-123');
        $response->assertOk();
        $response->assertViewIs('registration.form');
    }

    public function test_show_with_invalid_token_redirects(): void
    {
        $response = $this->get('/register/invalid-token');
        $response->assertRedirect();
    }

    public function test_store_with_valid_data_creates_user(): void
    {
        $group = ServiceGroup::factory()->create([
            'registration_token'              => 'valid-token-456',
            'is_active'                       => true,
            'registration_token_generated_at' => now(),
        ]);

        $response = $this->post('/register/valid-token-456', [
            'name'                  => 'New Servant',
            'email'                 => 'newservant@example.com',
            'phone'                 => '01234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email'            => 'newservant@example.com',
            'service_group_id' => $group->id,
        ]);
    }

    public function test_store_with_duplicate_email_fails(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $group = ServiceGroup::factory()->create([
            'registration_token'              => 'token-789',
            'is_active'                       => true,
            'registration_token_generated_at' => now(),
        ]);

        $response = $this->post('/register/token-789', [
            'name'                  => 'Test',
            'email'                 => 'existing@example.com',
            'phone'                 => '01234567891',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    public function test_public_registration_form(): void
    {
        ServiceGroup::factory()->create(['is_active' => true]);
        $response = $this->get('/register');
        $response->assertOk();
        $response->assertViewIs('registration.public-form');
    }

    public function test_public_registration_store(): void
    {
        $group = ServiceGroup::factory()->create(['is_active' => true]);

        $response = $this->post('/register', [
            'name'                  => 'Public Servant',
            'email'                 => 'public@example.com',
            'phone'                 => '01234567892',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'service_group_id'      => $group->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'public@example.com']);
    }
}
