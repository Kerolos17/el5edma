<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(private ReportService $service) {}

    public function beneficiariesPdf(Request $request)
    {
        return $this->service->beneficiariesPdf(Auth::user());
    }

    public function visitsPdf(Request $request)
    {
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
        return $this->service->unvisitedPdf(Auth::user());
    }

    // ── تقرير مخدوم واحد ──
    public function singleBeneficiaryPdf(Beneficiary $beneficiary)
    {
        $this->authorize('view', $beneficiary);

        return $this->service->singleBeneficiaryPdf($beneficiary);
    }

    // ── تقرير الأسرة ──
    public function serviceGroupPdf(ServiceGroup $serviceGroup)
    {
        $this->authorize('view', $serviceGroup);

        return $this->service->serviceGroupPdf($serviceGroup);
    }

    // ── تقرير مخدومي الأسرة ──
    public function serviceGroupBeneficiariesPdf(ServiceGroup $serviceGroup)
    {
        $this->authorize('view', $serviceGroup);

        return $this->service->serviceGroupBeneficiariesPdf($serviceGroup);
    }
}
