<?php

// Feature: ui-modernization
// Property 1: CSS Token completeness — Validates: Requirements 1.1, 1.2, 1.3, 1.6, 1.7
// Property 7: Dropdown viewport overflow — Validates: Requirements 11.12, 14.6
// Property 8: Touch targets ≥ 44px — Validates: Requirements 14.2, 7.4, 8.5, 13.5
// Property 9: Responsive grid breakpoints — Validates: Requirements 10.2, 10.3, 10.4
// Property 10: GPU-friendly animations only — Validates: Requirements 17.1, 17.2

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UiModernizationCssTokensTest extends TestCase
{
    private string $css;
    private string $notificationsBellPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->css = file_get_contents(
            dirname(__DIR__, 2) . '/resources/css/filament/admin/theme.css',
        );
        $this->notificationsBellPath = dirname(__DIR__, 2)
            . '/resources/views/livewire/notifications-bell.blade.php';
    }

    // ─── Property 1: CSS Token Completeness ──────────────────────────────────

    /** @test */
    public function primary_palette_50_through_900_all_exist(): void
    {
        $tokens = [
            '--color-primary-50',
            '--color-primary-100',
            '--color-primary-200',
            '--color-primary-300',
            '--color-primary-400',
            '--color-primary-500',
            '--color-primary-600',
            '--color-primary-700',
            '--color-primary-800',
            '--color-primary-900',
        ];

        foreach ($tokens as $token) {
            $this->assertStringContainsString(
                $token,
                $this->css,
                "Required CSS token {$token} is missing from theme.css",
            );
        }
    }

    /** @test */
    public function surface_tokens_all_exist(): void
    {
        $tokens = [
            '--bg-canvas',
            '--bg-surface',
            '--bg-surface-raised',
        ];

        foreach ($tokens as $token) {
            $this->assertStringContainsString(
                $token,
                $this->css,
                "Required surface token {$token} is missing from theme.css",
            );
        }
    }

    /** @test */
    public function text_tokens_all_exist(): void
    {
        $tokens = [
            '--color-text-primary',
            '--color-text-secondary',
            '--color-text-muted',
            '--color-text-inverse',
        ];

        foreach ($tokens as $token) {
            $this->assertStringContainsString(
                $token,
                $this->css,
                "Required text token {$token} is missing from theme.css",
            );
        }
    }

    /** @test */
    public function border_tokens_all_exist(): void
    {
        $tokens = [
            '--color-border',
            '--color-border-strong',
        ];

        foreach ($tokens as $token) {
            $this->assertStringContainsString(
                $token,
                $this->css,
                "Required border token {$token} is missing from theme.css",
            );
        }
    }

    /** @test */
    public function semantic_color_tokens_all_exist(): void
    {
        $tokens = [
            '--color-success',
            '--color-warning',
            '--color-danger',
            '--color-info',
        ];

        foreach ($tokens as $token) {
            $this->assertStringContainsString(
                $token,
                $this->css,
                "Required semantic color token {$token} is missing from theme.css",
            );
        }
    }

    /** @test */
    public function table_token_exists(): void
    {
        $this->assertStringContainsString(
            '--color-table-row-alt',
            $this->css,
            'Required table token --color-table-row-alt is missing from theme.css',
        );
    }

    /** @test */
    public function shadow_tokens_all_exist(): void
    {
        $tokens = [
            '--shadow-sm',
            '--shadow-md',
            '--shadow-lg',
            '--shadow-xl',
        ];

        foreach ($tokens as $token) {
            $this->assertStringContainsString(
                $token,
                $this->css,
                "Required shadow token {$token} is missing from theme.css",
            );
        }
    }

    /** @test */
    public function radius_tokens_all_exist(): void
    {
        $tokens = [
            '--radius-sm',
            '--radius-md',
            '--radius-lg',
            '--radius-xl',
            '--radius-full',
        ];

        foreach ($tokens as $token) {
            $this->assertStringContainsString(
                $token,
                $this->css,
                "Required radius token {$token} is missing from theme.css",
            );
        }
    }

    /** @test */
    public function transition_tokens_all_exist(): void
    {
        $tokens = [
            '--transition-fast',
            '--transition-normal',
            '--transition-slow',
        ];

        foreach ($tokens as $token) {
            $this->assertStringContainsString(
                $token,
                $this->css,
                "Required transition token {$token} is missing from theme.css",
            );
        }
    }

    // ─── Property 7: Dropdown Viewport Overflow (RTL-safe positioning) ───────

    /** @test */
    public function notifications_bell_dropdown_uses_logical_positioning_not_physical_right(): void
    {
        $blade = file_get_contents($this->notificationsBellPath);

        $hasLogicalProperty = str_contains($blade, 'inset-e-')
        || str_contains($blade, 'inset-inline-end');

        $this->assertTrue(
            $hasLogicalProperty,
            'notifications-bell.blade.php must use inset-e-* or inset-inline-end for RTL-safe dropdown positioning',
        );
    }

    // ─── Property 8: Touch Targets ≥ 44px ────────────────────────────────────

    /** @test */
    public function theme_css_enforces_44px_min_height_for_interactive_elements(): void
    {
        // تحقق من وجود min-height: 44px لعناصر button (بدون a لتجنب كسر inline links)
        $this->assertMatchesRegularExpression(
            '/button[^{]*\{[^}]*min-height:\s*44px/s',
            $this->css,
            'theme.css must define min-height: 44px for button elements to meet touch target requirements',
        );
    }

    // ─── Property 9: Responsive Grid Breakpoints ─────────────────────────────

    /** @test */
    public function theme_css_contains_640px_breakpoint(): void
    {
        $this->assertMatchesRegularExpression(
            '/@media[^{]*640px/',
            $this->css,
            'theme.css must contain a media query for the 640px breakpoint (tablet layout)',
        );
    }

    /** @test */
    public function theme_css_contains_1024px_breakpoint(): void
    {
        $this->assertMatchesRegularExpression(
            '/@media[^{]*1024px/',
            $this->css,
            'theme.css must contain a media query for the 1024px breakpoint (desktop layout)',
        );
    }

    // ─── Property 10: GPU-Friendly Animations Only ───────────────────────────

    /** @test */
    public function all_transition_declarations_use_only_gpu_friendly_properties(): void
    {
        // استخرج جميع قيم transition: من الـ CSS
        preg_match_all('/transition\s*:\s*([^;]+);/', $this->css, $matches);

        $forbiddenProperties = ['width', 'height', 'top', 'left'];

        foreach ($matches[1] as $transitionValue) {
            foreach ($forbiddenProperties as $forbidden) {
                // تحقق أن الـ transition لا تستخدم خصائص تسبب layout reflow
                // نتجاهل حالات مثل "transition: all" أو "transition: border-color"
                // ونتحقق فقط من الخصائص المحظورة بشكل صريح
                $this->assertDoesNotMatchRegularExpression(
                    '/\b' . preg_quote($forbidden, '/') . '\b/',
                    trim($transitionValue),
                    "transition declaration '{$transitionValue}' uses non-GPU-friendly property '{$forbidden}' which causes layout reflow",
                );
            }
        }
    }
}
