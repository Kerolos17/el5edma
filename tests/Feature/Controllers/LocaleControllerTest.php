<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_locale_switch_valid(): void
    {
        $response = $this->post('/language-guest/ar');
        $response->assertRedirect();
        $this->assertSame('ar', session('locale'));
    }

    public function test_guest_locale_switch_invalid_aborts(): void
    {
        $response = $this->post('/language-guest/fr');
        $response->assertStatus(400);
    }

    public function test_authenticated_locale_switch(): void
    {
        $user = User::factory()->create(['locale' => 'ar']);
        $response = $this->actingAs($user)->post('/language/en');
        $response->assertRedirect();
    }
}
