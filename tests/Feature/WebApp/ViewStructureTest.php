<?php

declare(strict_types=1);

namespace Tests\Feature\WebApp;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ViewStructureTest extends TestCase
{
    #[Test]
    public function resource_pages_use_dedicated_views(): void
    {
        $pages = [
            'beneficiaries-page' => 'BeneficiariesPage',
            'visits-page' => 'VisitsPage',
            'users-page' => 'UsersPage',
            'service-groups-page' => 'ServiceGroupsPage',
            'medical-files-page' => 'MedicalFilesPage',
            'prayer-requests-page' => 'PrayerRequestsPage',
            'scheduled-visits-page' => 'ScheduledVisitsPage',
            'reports-page' => 'ReportsPage',
        ];

        foreach ($pages as $view => $component) {
            $viewPath = resource_path("views/livewire/web-app/{$view}.blade.php");
            $this->assertFileExists($viewPath, "Missing view for {$component}");

            $componentPath = app_path("Livewire/WebApp/{$component}.php");
            $this->assertFileExists($componentPath, "Missing component for {$component}");
        }
    }

    #[Test]
    public function resource_pages_include_modals(): void
    {
        $pages = [
            'beneficiaries-page',
            'visits-page',
            'users-page',
            'service-groups-page',
            'medical-files-page',
            'prayer-requests-page',
            'scheduled-visits-page',
        ];

        foreach ($pages as $view) {
            $viewPath = resource_path("views/livewire/web-app/{$view}.blade.php");
            $source = file_get_contents($viewPath);
            $this->assertStringContainsString("@include('livewire.web-app.partials.modals.index')", $source);
        }
    }

    #[Test]
    public function web_app_shell_exposes_language_and_dark_mode_controls(): void
    {
        $layout = file_get_contents(resource_path('views/web-app/layouts/app.blade.php'));

        $this->assertStringContainsString('data-theme-toggle', $layout);
        $this->assertStringContainsString('web-app-language-form', $layout);
        $this->assertStringContainsString("app()->getLocale() === 'ar' ? 'rtl' : 'ltr'", $layout);
    }

    #[Test]
    public function modal_index_delegates_each_workflow_to_its_own_partial(): void
    {
        $view = file_get_contents(resource_path('views/livewire/web-app/partials/modals/index.blade.php'));

        foreach ([
            'beneficiary-form',
            'medical-file-form',
            'visit-form',
            'prayer-form',
            'user-form',
            'service-group-form',
            'scheduled-visit-form',
        ] as $partial) {
            $this->assertStringContainsString("@include('livewire.web-app.partials.modals.{$partial}')", $view);
        }
    }

    #[Test]
    public function placeholder_page_delegates_large_workflows_to_concerns(): void
    {
        $component = file_get_contents(app_path('Livewire/WebApp/PlaceholderPage.php'));

        foreach ([
            'ManagesScheduledVisits',
            'ManagesVisits',
            'ManagesPrayerRequests',
            'ManagesMedicalFiles',
            'ManagesServiceGroups',
            'ManagesUsers',
            'ManagesBeneficiaries',
        ] as $concern) {
            $this->assertStringContainsString("use App\\Livewire\\WebApp\\Concerns\\{$concern};", $component);
            $this->assertStringContainsString("use {$concern};", $component);
            $this->assertFileExists(app_path("Livewire/WebApp/Concerns/{$concern}.php"));
        }
    }

    #[Test]
    public function placeholder_page_delegates_resource_listing_to_concern(): void
    {
        $component = file_get_contents(app_path('Livewire/WebApp/PlaceholderPage.php'));
        $concern = app_path('Livewire/WebApp/Concerns/ManagesResourceListing.php');

        $this->assertStringContainsString('use App\\Livewire\\WebApp\\Concerns\\ManagesResourceListing;', $component);
        $this->assertStringContainsString('use ManagesResourceListing;', $component);
        $this->assertFileExists($concern);

        $concernSource = file_get_contents($concern);

        foreach ([
            'private function queryForSection',
            'private function applySearch',
            'private function applyFilter',
            'private function applySort',
        ] as $method) {
            $this->assertStringContainsString($method, $concernSource);
        }
    }
}
