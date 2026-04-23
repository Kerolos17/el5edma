<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Exports\BeneficiariesExport;
use App\Exports\VisitsExport;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.reports';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 5;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $groupId = null;

    public ?string $servantId = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.management');
    }

    public static function getNavigationLabel(): string
    {
        return __('reports.title');
    }

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::SuperAdmin,
            UserRole::ServiceLeader,
            UserRole::FamilyLeader,
            UserRole::Servant,
        ], true);
    }

    public function canAccessBeneficiaryReports(): bool
    {
        return self::canAccess();
    }

    public function canAccessManagementReports(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::SuperAdmin,
            UserRole::ServiceLeader,
            UserRole::FamilyLeader,
        ], true);
    }

    // ── Excel — شغال مع Livewire ──
    public function exportBeneficiariesExcel(): BinaryFileResponse
    {
        abort_unless($this->canAccessBeneficiaryReports(), 403);

        return Excel::download(
            new BeneficiariesExport(Auth::user()),
            'beneficiaries-' . now()->format('Y-m-d') . '.xlsx',
        );
    }

    public function exportVisitsExcel(): BinaryFileResponse
    {
        abort_unless($this->canAccessManagementReports(), 403);

        return Excel::download(
            new VisitsExport(
                user: Auth::user(),
                dateFrom: $this->dateFrom,
                dateTo: $this->dateTo,
                groupId: $this->groupId,
            ),
            'visits-' . now()->format('Y-m-d') . '.xlsx',
        );
    }

    // ── PDF URLs — بتتفتح في tab جديد ──
    public function getBeneficiariesPdfUrl(): string
    {
        return route('reports.beneficiaries.pdf');
    }

    public function getVisitsPdfUrl(): string
    {
        $params = array_filter([
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
        ]);

        return route('reports.visits.pdf', $params);
    }

    public function getUnvisitedPdfUrl(): string
    {
        return route('reports.unvisited.pdf');
    }
}
