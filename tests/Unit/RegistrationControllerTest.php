<?php

namespace Tests\Unit;

use App\Models\ServiceGroup;
use App\Models\User;
use App\Services\RegistrationLinkService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * إرسال POST request مع تجاوز CSRF فقط
     */
    protected function postForm(string $uri, array $data = []): TestResponse
    {
        VerifyCsrfToken::except($uri);

        return $this->post($uri, $data);
    }

    /** @test */
    public function it_displays_registration_form_with_valid_token(): void
    {
        // Arrange
        $serviceGroup = ServiceGroup::factory()->create(['name' => 'Test Group']);
        $token        = app(RegistrationLinkService::class)->getOrCreateToken($serviceGroup);

        // Act
        $response = $this->get(route('register.show', ['token' => $token]));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Test Group');
        $response->assertSee(__('registration.title'));
    }

    /** @test */
    public function it_redirects_to_login_with_invalid_token(): void
    {
        // Act
        $response = $this->get(route('register.show', ['token' => 'invalid-token']));

        // Assert
        $response->assertRedirect(route('filament.admin.auth.login'));
        $response->assertSessionHas('error', __('registration.errors.invalid_token'));
    }

    /** @test */
    public function it_creates_user_account_with_valid_data(): void
    {
        // Arrange
        $serviceGroup = ServiceGroup::factory()->create();
        $token        = app(RegistrationLinkService::class)->getOrCreateToken($serviceGroup);

        $data = [
            'name'                  => 'Test Servant',
            'email'                 => 'test@example.com',
            'phone'                 => '01234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->postForm(route('register.store', ['token' => $token]), $data);

        // Assert
        $response->assertRedirect(route('filament.admin.auth.login'));
        $response->assertSessionHas('success', __('registration.success'));

        $this->assertDatabaseHas('users', [
            'name'             => 'Test Servant',
            'email'            => 'test@example.com',
            'phone'            => '01234567890',
            'role'             => 'servant',
            'service_group_id' => $serviceGroup->id,
            'is_active'        => true,
            'locale'           => 'ar',
        ]);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        // Arrange
        $serviceGroup = ServiceGroup::factory()->create();
        $token        = app(RegistrationLinkService::class)->getOrCreateToken($serviceGroup);

        // Act
        $response = $this->postForm(route('register.store', ['token' => $token]), []);

        // Assert
        $response->assertSessionHasErrors(['name', 'email', 'phone', 'password']);
    }

    /** @test */
    public function it_validates_email_format(): void
    {
        // Arrange
        $serviceGroup = ServiceGroup::factory()->create();
        $token        = app(RegistrationLinkService::class)->getOrCreateToken($serviceGroup);

        $data = [
            'name'                  => 'Test Servant',
            'email'                 => 'invalid-email',
            'phone'                 => '01234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act - email format
        $response = $this->postForm(route('register.store', ['token' => $token]), $data);

        // Assert
        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function it_validates_email_uniqueness(): void
    {
        // Arrange
        $serviceGroup = ServiceGroup::factory()->create();
        $token        = app(RegistrationLinkService::class)->getOrCreateToken($serviceGroup);

        User::factory()->create(['email' => 'existing@example.com']);

        $data = [
            'name'                  => 'Test Servant',
            'email'                 => 'existing@example.com',
            'phone'                 => '01234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->postForm(route('register.store', ['token' => $token]), $data);

        // Assert
        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function it_validates_phone_uniqueness(): void
    {
        // Arrange
        $serviceGroup = ServiceGroup::factory()->create();
        $token        = app(RegistrationLinkService::class)->getOrCreateToken($serviceGroup);

        User::factory()->create(['phone' => '01234567890']);

        $data = [
            'name'                  => 'Test Servant',
            'email'                 => 'test@example.com',
            'phone'                 => '01234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->postForm(route('register.store', ['token' => $token]), $data);

        // Assert
        $response->assertSessionHasErrors(['phone']);
    }

    /** @test */
    public function it_validates_password_minimum_length(): void
    {
        // Arrange
        $serviceGroup = ServiceGroup::factory()->create();
        $token        = app(RegistrationLinkService::class)->getOrCreateToken($serviceGroup);

        $data = [
            'name'                  => 'Test Servant',
            'email'                 => 'test@example.com',
            'phone'                 => '01234567890',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ];

        // Act
        $response = $this->postForm(route('register.store', ['token' => $token]), $data);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function it_validates_password_confirmation(): void
    {
        // Arrange
        $serviceGroup = ServiceGroup::factory()->create();
        $token        = app(RegistrationLinkService::class)->getOrCreateToken($serviceGroup);

        $data = [
            'name'                  => 'Test Servant',
            'email'                 => 'test@example.com',
            'phone'                 => '01234567890',
            'password'              => 'password123',
            'password_confirmation' => 'different',
        ];

        // Act
        $response = $this->postForm(route('register.store', ['token' => $token]), $data);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function it_rejects_registration_with_invalid_token(): void
    {
        // Arrange
        $data = [
            'name'                  => 'Test Servant',
            'email'                 => 'test@example.com',
            'phone'                 => '01234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->postForm(route('register.store', ['token' => 'invalid-token']), $data);

        // Assert
        $response->assertRedirect(route('filament.admin.auth.login'));
        $response->assertSessionHas('error', __('registration.errors.invalid_token'));
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
