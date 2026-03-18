<?php

// Feature: church-admin-theme — Unit tests for theme.css content

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ThemeCssTest extends TestCase
{
    private string $css;

    protected function setUp(): void
    {
        parent::setUp();
        $this->css = file_get_contents(
            dirname(__DIR__, 2) . '/resources/css/filament/admin/theme.css'
        );
    }

    public function test_contains_cairo_font_import(): void
    {
        $this->assertStringContainsString('Cairo', $this->css);
    }

    public function test_contains_dark_mode_topbar_override(): void
    {
        $this->assertStringContainsString('.dark .fi-topbar', $this->css);
    }

    public function test_contains_dark_mode_sidebar_override(): void
    {
        $this->assertStringContainsString('.dark .fi-sidebar', $this->css);
    }

    public function test_contains_dark_mode_card_override(): void
    {
        $this->assertStringContainsString('.dark .fi-card', $this->css);
    }

    public function test_contains_responsive_768px_breakpoint(): void
    {
        $this->assertStringContainsString('max-width: 768px', $this->css);
    }

    public function test_sidebar_panel_has_border_radius_in_768px_media_query(): void
    {
        // Extract the @media (min-width: 768px) block(s) and check for .sidebar-panel border-radius
        preg_match_all('/@media\s*\(min-width:\s*768px\)[^{]*\{((?:[^{}]*|\{[^{}]*\})*)\}/s', $this->css, $matches);
        $mediaContent = implode('', $matches[1]);
        $this->assertStringContainsString('.sidebar-panel', $mediaContent);
        $this->assertStringContainsString('border-radius', $mediaContent);
    }

    public function test_rtl_sidebar_panel_border_radius_in_768px_media_query(): void
    {
        preg_match_all('/@media\s*\(min-width:\s*768px\)[^{]*\{((?:[^{}]*|\{[^{}]*\})*)\}/s', $this->css, $matches);
        $mediaContent = implode('', $matches[1]);
        $this->assertStringContainsString('[dir="rtl"] .sidebar-panel', $mediaContent);
    }

    public function test_code_input_base_rule_has_40px_width(): void
    {
        $this->assertMatchesRegularExpression('/\.code-input\s*\{[^}]*width:\s*40px/s', $this->css);
    }

    public function test_code_input_has_44px_width_in_400px_media_query(): void
    {
        preg_match_all('/@media\s*\(min-width:\s*400px\)[^{]*\{((?:[^{}]*|\{[^{}]*\})*)\}/s', $this->css, $matches);
        $mediaContent = implode('', $matches[1]);
        $this->assertStringContainsString('width: 44px', $mediaContent);
    }

    public function test_code_input_has_48px_width_in_768px_media_query(): void
    {
        preg_match_all('/@media\s*\(min-width:\s*768px\)[^{]*\{((?:[^{}]*|\{[^{}]*\})*)\}/s', $this->css, $matches);
        $mediaContent = implode('', $matches[1]);
        $this->assertStringContainsString('width: 48px', $mediaContent);
    }

    public function test_dark_login_card_has_correct_background(): void
    {
        $this->assertMatchesRegularExpression('/\.dark\s+\.login-card\s*\{[^}]*#111827/s', $this->css);
    }

    public function test_sidebar_panel_base_rule_has_max_height(): void
    {
        $this->assertMatchesRegularExpression('/\.sidebar-panel\s*\{[^}]*max-height/s', $this->css);
    }
}
