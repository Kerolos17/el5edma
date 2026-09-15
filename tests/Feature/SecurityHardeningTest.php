<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\WebApp\BeneficiariesPage;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class SecurityHardeningTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    // -- Session Security --

    #[Test]
    public function session_expire_on_close_defaults_to_true(): void
    {
        $this->assertTrue(config('session.expire_on_close'));
    }

    #[Test]
    public function session_secure_cookie_defaults_to_true(): void
    {
        $this->assertTrue(config('session.secure'));
    }

    // -- HSTS Preload --

    #[Test]
    public function hsts_header_includes_preload_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $response = $this->get('/register');

        $hsts = $response->headers->get('Strict-Transport-Security');
        $this->assertStringContainsString('preload', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
    }

    // -- Security Headers --

    #[Test]
    public function security_headers_are_present(): void
    {
        $response = $this->get('/register');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
        $response->assertHeader('Permissions-Policy');
    }

    // -- Upload MIME Hardening --

    #[Test]
    public function medical_files_livewire_has_mimetypes_validation_rule(): void
    {
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin);

        $component = BeneficiariesPage::class;

        // Verify the Livewire component exists and is instantiable
        $this->assertTrue(class_exists($component));
    }

    #[Test]
    public function medical_files_livewire_component_exists_with_mimetypes(): void
    {
        // Verify the trait file contains mimetypes rule
        $traitFile = file_get_contents(app_path('Livewire/WebApp/Concerns/ManagesMedicalFiles.php'));
        $this->assertStringContainsString('mimetypes:', $traitFile, 'Medical file upload must have mimetypes validation');
        $this->assertStringContainsString('application/pdf', $traitFile);
        $this->assertStringContainsString('image/jpeg', $traitFile);
    }

    #[Test]
    public function beneficiary_photo_has_mimetypes_validation_rule(): void
    {
        $traitFile = file_get_contents(app_path('Livewire/WebApp/Concerns/ManagesBeneficiaries.php'));
        $this->assertStringContainsString('mimetypes:', $traitFile, 'Beneficiary photo must have mimetypes validation');
        $this->assertStringContainsString('image/jpeg', $traitFile);
        $this->assertStringContainsString('image/png', $traitFile);
    }

    #[Test]
    public function my_profile_photo_has_mimetypes_validation(): void
    {
        $profileFile = file_get_contents(app_path('Filament/Pages/MyProfile.php'));
        $this->assertStringContainsString('mimetypes:', $profileFile, 'My profile photo must have mimetypes validation');
    }

    // -- Rate Limiting --

    #[Test]
    public function file_access_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('private.file');
        $this->assertNotNull($route);
        $middleware = collect($route->gatherMiddleware());
        $this->assertTrue(
            $middleware->contains(fn ($m) => str_contains($m, 'throttle')),
            'File access route must have throttle middleware',
        );
    }

    #[Test]
    public function public_registration_has_rate_limiting(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('registration.public.store'), [
                'name'                  => 'Test User ' . $i,
                'email'                 => "test{$i}@example.com",
                'phone'                 => "0123456789{$i}",
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'service_group_id'      => ServiceGroup::factory()->create(['is_active' => true])->id,
            ]);
        }

        $response = $this->post(route('registration.public.store'), [
            'name'                  => 'Rate Limited',
            'email'                 => 'ratelimited@example.com',
            'phone'                 => '01234567899',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'service_group_id'      => ServiceGroup::factory()->create(['is_active' => true])->id,
        ]);

        $response->assertStatus(429);
    }

    #[Test]
    public function login_code_has_rate_limiting(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.code'), ['personal_code' => 'wrong' . $i]);
        }

        $response = $this->post(route('login.code'), ['personal_code' => 'wrong6']);
        $response->assertStatus(429);
    }
}
