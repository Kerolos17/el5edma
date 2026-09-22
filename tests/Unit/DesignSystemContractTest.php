<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DesignSystemContractTest extends TestCase
{
    private string $css;

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/design-system.css');
    }

    #[Test]
    public function shared_semantic_tokens_are_defined(): void
    {
        foreach ([
            '--surface-canvas',
            '--surface-panel',
            '--surface-raised',
            '--surface-danger-soft',
            '--text-strong',
            '--text-body',
            '--text-muted',
            '--border-subtle',
            '--focus-ring',
            '--motion-normal',
            '--ease-out',
            '--tap-target',
        ] as $token) {
            $this->assertStringContainsString($token, $this->css, "Missing shared token: {$token}");
        }
    }

    #[Test]
    public function shared_components_do_not_use_inline_gradients(): void
    {
        foreach (['stat-card', 'section-header', 'avatar'] as $component) {
            $view = file_get_contents(
                dirname(__DIR__, 2) . "/resources/views/components/ui/{$component}.blade.php",
            );

            $this->assertStringNotContainsString('linear-gradient', $view, "{$component} must use shared classes");
            $this->assertStringNotContainsString('style=', $view, "{$component} must not own inline visual styles");
        }
    }

    #[Test]
    public function web_app_defaults_to_light_without_following_system_theme(): void
    {
        $layout = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/web-app/layouts/app.blade.php',
        );

        $this->assertStringContainsString("storedTheme === 'dark' ? 'dark' : 'light'", $layout);
        $this->assertStringNotContainsString('prefers-color-scheme: dark', $layout);
    }

    #[Test]
    public function filament_initializes_sidebar_groups_and_defaults_to_light(): void
    {
        $layout = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/vendor/filament-panels/components/layout/base.blade.php',
        );
        $provider = file_get_contents(
            dirname(__DIR__, 2) . '/app/Providers/Filament/AdminPanelProvider.php',
        );

        $this->assertStringContainsString('Array.isArray(collapsedGroups)', $layout);
        $this->assertStringContainsString("localStorage.setItem('collapsedGroups', '[]')", $layout);
        $this->assertStringContainsString('defaultThemeMode(ThemeMode::Light)', $provider);

        $topbar = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/filament/widgets/notifications-bell-topbar.blade.php',
        );

        $this->assertStringContainsString('NotificationsBellWidget::class', $topbar);
        $this->assertStringNotContainsString("@livewire('notifications-bell')", $topbar);
    }

    #[Test]
    public function servant_primary_slice_uses_the_shared_surface_contract(): void
    {
        $dashboard = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/livewire/servant/dashboard.blade.php',
        );
        $beneficiaries = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/livewire/servant/beneficiary-list.blade.php',
        );
        $wizard = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/livewire/servant/create-visit-wizard.blade.php',
        );
        $header = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/components/servant/header.blade.php',
        );
        $beneficiaryDetail = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/livewire/servant/beneficiary-detail.blade.php',
        );

        $this->assertStringNotContainsString('style=', $dashboard);
        $this->assertStringNotContainsString('reveal-card', $dashboard);
        $this->assertStringNotContainsString('style=', $beneficiaries);
        $this->assertStringNotContainsString('reveal-card', $beneficiaries);
        $this->assertStringContainsString('x-show="open"', $wizard);
        $this->assertStringContainsString('role="dialog"', $wizard);
        $this->assertStringContainsString('@keydown.escape.window', $wizard);
        $this->assertStringContainsString('@keydown.tab="trapFocus($event)"', $wizard);
        $this->assertStringContainsString('restoreFocus()', $wizard);
        $this->assertStringContainsString('min-h-12', $wizard);
        $this->assertStringContainsString('role="dialog"', $header);
        $this->assertStringContainsString('@keydown.tab="trapFocus($event)"', $header);
        $this->assertStringContainsString('w-12 h-12', $header);
        $this->assertStringContainsString('<h1 class="servant-bene-detail-hero__title">', $beneficiaryDetail);
        $this->assertGreaterThanOrEqual(2, substr_count($beneficiaryDetail, '<h2>'));
    }

    #[Test]
    public function servant_secondary_surfaces_keep_the_calm_accessible_contract(): void
    {
        foreach ([
            'visit-list',
            'scheduled-visit-list',
            'prayer-request-list',
            'medical-file-list',
            'profile',
        ] as $viewName) {
            $view = file_get_contents(
                dirname(__DIR__, 2) . "/resources/views/livewire/servant/{$viewName}.blade.php",
            );

            $this->assertStringContainsString('<h1', $view, "{$viewName} needs one page heading");
            $this->assertStringNotContainsString('reveal-card', $view, "{$viewName} must not animate content on entry");
            $this->assertStringNotContainsString('card-lift', $view, "{$viewName} must use calm static surfaces");
            $this->assertStringNotContainsString('style=', $view, "{$viewName} must use semantic classes");
        }

        $notifications = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/livewire/servant/notifications-bell.blade.php',
        );
        $notificationScript = file_get_contents(
            dirname(__DIR__, 2) . '/resources/js/notifications.js',
        );

        $this->assertStringContainsString('aria-expanded="false"', $notifications);
        $this->assertStringContainsString('role="dialog"', $notifications);
        $this->assertStringContainsString('aria-hidden="true"', $notifications);
        $this->assertStringContainsString('inert', $notifications);
        $this->assertStringContainsString('data-label-mute', $notifications);
        $this->assertStringContainsString('getFocusableElements', $notificationScript);
        $this->assertStringContainsString('__notificationReturnFocus', $notificationScript);
        $this->assertStringContainsString('muteButton.dataset.labelUnmute', $notificationScript);
    }
}
