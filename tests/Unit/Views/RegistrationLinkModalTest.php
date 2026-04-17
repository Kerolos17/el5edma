<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

class RegistrationLinkModalTest extends TestCase
{
    /** @test */
    public function it_renders_registration_link_modal_with_url_and_count(): void
    {
        $url             = 'https://example.com/register/test-token-123';
        $registeredCount = 5;

        $view = $this->blade(
            '<x-dynamic-component component="filament::modal">
                @include("filament.modals.registration-link", ["url" => $url, "registeredCount" => $registeredCount])
            </x-dynamic-component>',
            compact('url', 'registeredCount'),
        );

        $view->assertSee($url, false);
        $view->assertSee((string) $registeredCount);
        $view->assertSee(__('service_groups.registration_url'));
        $view->assertSee(__('service_groups.copy'));
        $view->assertSee(__('service_groups.registered_servants_count'));
    }

    /** @test */
    public function it_includes_copy_button_with_javascript(): void
    {
        $url             = 'https://example.com/register/test-token';
        $registeredCount = 0;

        $view = $this->blade(
            '@include("filament.modals.registration-link")',
            compact('url', 'registeredCount'),
        );

        $view->assertSee('copyToClipboard', false);
        $view->assertSee('registration-url', false);
        $view->assertSee('copy-button-text', false);
    }

    /** @test */
    public function it_displays_readonly_input_field(): void
    {
        $url             = 'https://example.com/register/abc123';
        $registeredCount = 10;

        $view = $this->blade(
            '@include("filament.modals.registration-link")',
            compact('url', 'registeredCount'),
        );

        $view->assertSee('readonly', false);
        $view->assertSee('type="text"', false);
    }

    /** @test */
    public function it_shows_zero_registered_count(): void
    {
        $url             = 'https://example.com/register/token';
        $registeredCount = 0;

        $view = $this->blade(
            '@include("filament.modals.registration-link")',
            compact('url', 'registeredCount'),
        );

        $view->assertSee('0');
    }
}
