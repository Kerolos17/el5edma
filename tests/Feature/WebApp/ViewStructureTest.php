<?php

declare(strict_types=1);

namespace Tests\Feature\WebApp;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ViewStructureTest extends TestCase
{
    #[Test]
    public function placeholder_page_uses_shared_partials_for_common_sections(): void
    {
        $view = file_get_contents(resource_path('views/livewire/web-app/placeholder-page.blade.php'));

        $this->assertStringContainsString("@include('livewire.web-app.partials.hero')", $view);
        $this->assertStringContainsString("@include('livewire.web-app.partials.stats')", $view);
        $this->assertStringContainsString("@include('livewire.web-app.partials.toolbar')", $view);
        $this->assertStringContainsString("@include('livewire.web-app.partials.report-cards')", $view);
        $this->assertStringContainsString("@include('livewire.web-app.partials.resource-table')", $view);
        $this->assertStringContainsString("@include('livewire.web-app.partials.modals.index')", $view);
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
    public function resource_rows_and_mobile_cards_delegate_each_resource_to_its_own_partial(): void
    {
        $headerView = file_get_contents(resource_path('views/livewire/web-app/partials/resource-headers.blade.php'));
        $rowView = file_get_contents(resource_path('views/livewire/web-app/partials/resource-row.blade.php'));
        $mobileView = file_get_contents(resource_path('views/livewire/web-app/partials/resource-mobile-card.blade.php'));

        foreach ([
            'beneficiaries',
            'visits',
            'scheduled-visits',
            'prayer-requests',
            'medical-files',
            'users',
            'service-groups',
        ] as $section) {
            $this->assertStringContainsString("@include('livewire.web-app.partials.headers.{$section}')", $headerView);
            $this->assertStringContainsString("@include('livewire.web-app.partials.rows.{$section}')", $rowView);
            $this->assertStringContainsString("@include('livewire.web-app.partials.mobile-cards.{$section}')", $mobileView);
        }
    }
}
