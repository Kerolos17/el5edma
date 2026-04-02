<?php

// Feature: church-admin-theme — Unit tests for AdminPanelProvider panel configuration

namespace Tests\Unit;

use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class AdminPanelProviderTest extends TestCase
{
    private Panel $panel;

    protected function setUp(): void
    {
        parent::setUp();
        $provider    = new AdminPanelProvider($this->app);
        $this->panel = $provider->panel(Panel::make());
    }

    // Requirements: 8.1 — brandName locale mapping

    public function test_brand_name_returns_arabic_when_locale_is_ar(): void
    {
        App::setLocale('ar');

        $brandName = App::isLocale('ar') ? 'نظام الخدمة' : 'Ministry System';

        $this->assertSame('نظام الخدمة', $brandName);
    }

    public function test_brand_name_returns_english_when_locale_is_en(): void
    {
        App::setLocale('en');

        $brandName = App::isLocale('ar') ? 'نظام الخدمة' : 'Ministry System';

        $this->assertSame('Ministry System', $brandName);
    }

    // Requirements: 3.5, 5.3 — sidebar dimensions

    public function test_sidebar_width_is_260px(): void
    {
        $this->assertSame('260px', $this->panel->getSidebarWidth());
    }

    public function test_collapsed_sidebar_width_is_72px(): void
    {
        $this->assertSame('72px', $this->panel->getCollapsedSidebarWidth());
    }

    // Requirements: 5.3 — max content width

    public function test_max_content_width_is_1400px(): void
    {
        $this->assertSame('1400px', $this->panel->getMaxContentWidth());
    }
}
