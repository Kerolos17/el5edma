<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\Visit;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function __construct(private ReportService $service) {}

    private function authorizeBeneficiaryReportsAccess(): void
    {
        abort_unless(in_array(Auth::user()?->role, [
            UserRole::SuperAdmin,
            UserRole::ServiceLeader,
            UserRole::FamilyLeader,
            UserRole::Servant,
        ], true), 403);
    }

    private function authorizeManagementReportsAccess(): void
    {
        abort_unless(in_array(Auth::user()?->role, [
            UserRole::SuperAdmin,
            UserRole::ServiceLeader,
            UserRole::FamilyLeader,
        ], true), 403);
    }

    public function beneficiariesPdf(Request $request)
    {
        $this->authorizeBeneficiaryReportsAccess();
        Gate::authorize('viewAny', Beneficiary::class);

        return $this->service->beneficiariesPdf(Auth::user());
    }

    public function visitsPdf(Request $request)
    {
        $this->authorizeManagementReportsAccess();
        Gate::authorize('viewAny', Visit::class);

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        return $this->service->visitsPdf(
            user: Auth::user(),
            dateFrom: $request->query('date_from'),
            dateTo: $request->query('date_to'),
        );
    }

    public function unvisitedPdf(Request $request)
    {
        $this->authorizeManagementReportsAccess();
        Gate::authorize('viewAny', Beneficiary::class);

        return $this->service->unvisitedPdf(Auth::user());
    }

    // ── تقرير مخدوم واحد ──
    public function singleBeneficiaryPdf(Beneficiary $beneficiary)
    {
        $this->authorizeBeneficiaryReportsAccess();
        Gate::authorize('view', $beneficiary);

        return $this->service->singleBeneficiaryPdf($beneficiary);
    }

    // ── تقرير الأسرة ──
    public function serviceGroupPdf(ServiceGroup $serviceGroup)
    {
        $this->authorizeManagementReportsAccess();
        Gate::authorize('view', $serviceGroup);

        return $this->service->serviceGroupPdf($serviceGroup);
    }

    // ── تقرير مخدومي الأسرة ──
    public function serviceGroupBeneficiariesPdf(ServiceGroup $serviceGroup)
    {
        $this->authorizeManagementReportsAccess();
        Gate::authorize('view', $serviceGroup);

        return $this->service->serviceGroupBeneficiariesPdf($serviceGroup);
    }
}
