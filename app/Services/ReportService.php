<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Response;
use Mpdf\Mpdf;

class ReportService
{
    private function makeMpdf(): Mpdf
    {
        $isAr = app()->getLocale() === 'ar';

        return new Mpdf([
            'mode'          => 'utf-8',
            'direction'     => $isAr ? 'rtl' : 'ltr',
            'default_font'  => 'dejavusans',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 15,
            'margin_bottom' => 15,
        ]);
    }

    public function beneficiariesPdf(User $user): Response
    {
        $query = Beneficiary::with(['serviceGroup', 'assignedServant'])
            ->where('status', 'active');

        if ($user->role === UserRole::FamilyLeader) {
            $query->where('service_group_id', $user->service_group_id);
        } elseif ($user->role === UserRole::Servant) {
            $query->where('assigned_servant_id', $user->id);
        }

        $beneficiaries = $query->limit(500)->get();
        $isAr          = app()->getLocale() === 'ar';

        $html = view('reports.beneficiaries-pdf', compact('beneficiaries', 'isAr', 'user'))->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="beneficiaries-' . now()->format('Y-m-d') . '.pdf"',
        ]);
    }

    public function visitsPdf(User $user, ?string $dateFrom = null, ?string $dateTo = null): Response
    {
        $query = Visit::with(['beneficiary.serviceGroup', 'createdBy'])->latest('visit_date');

        if ($user->role === UserRole::FamilyLeader) {
            $query->whereHas('beneficiary', fn($q) =>
                $q->where('service_group_id', $user->service_group_id)
            );
        } elseif ($user->role === UserRole::Servant) {
            $query->where('created_by', $user->id);
        }

        if ($dateFrom) {
            $query->whereDate('visit_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('visit_date', '<=', $dateTo);
        }

        $visits = $query->limit(500)->get();
        $isAr   = app()->getLocale() === 'ar';

        $html = view('reports.visits-pdf', compact('visits', 'isAr', 'user', 'dateFrom', 'dateTo'))->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="visits-' . now()->format('Y-m-d') . '.pdf"',
        ]);
    }

    public function unvisitedPdf(User $user): Response
    {
        $cutoff = now()->subDays(30);

        $query = Beneficiary::with(['serviceGroup', 'assignedServant'])
            ->withMax('visits', 'visit_date')
            ->where('status', 'active')
            ->where(function ($q) use ($cutoff) {
                $q->whereDoesntHave('visits')
                    ->orWhereHas('visits', function ($vq) use ($cutoff) {
                        $vq->havingRaw('MAX(visit_date) < ?', [$cutoff]);
                    });
            })
            ->when($user->role === UserRole::FamilyLeader,
                fn($q) => $q->where('service_group_id', $user->service_group_id)
            )
            ->when($user->role === UserRole::Servant,
                fn($q) => $q->where('assigned_servant_id', $user->id)
            );

        $beneficiaries = $query->limit(500)->get();
        $isAr          = app()->getLocale() === 'ar';
        $html          = view('reports.unvisited-pdf', compact('beneficiaries', 'isAr', 'user'))->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="unvisited-' . now()->format('Y-m-d') . '.pdf"',
        ]);
    }

    // ── تقرير مخدوم واحد كامل ──
    public function singleBeneficiaryPdf(Beneficiary $beneficiary): Response
    {
        $beneficiary->load([
            'serviceGroup',
            'assignedServant',
            'createdBy',
            'medications' => fn ($q) => $q->where('is_active', true),
            'visits'      => fn ($q) => $q->latest('visit_date')->limit(10),
            'visits.createdBy',
            'medicalFiles',
            'prayerRequests',
        ]);

        $isAr = app()->getLocale() === 'ar';
        $html = view('reports.single-beneficiary-pdf', compact('beneficiary', 'isAr'))->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);

        $filename = 'beneficiary-' . $beneficiary->code . '-' . now()->format('Y-m-d') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── تقرير الأسرة (الخدام + الإحصائيات) ──
    public function serviceGroupPdf(ServiceGroup $serviceGroup): Response
    {
        $serviceGroup->load([
            'leader',
            'serviceLeader',
            'beneficiaries' => fn ($q) => $q->where('status', 'active'),
        ]);

        // Load servants with aggregated statistics
        $servants = User::where('service_group_id', $serviceGroup->id)
            ->withCount([
                'visits as visits_this_month' => fn ($q) => $q
                    ->whereMonth('visit_date', now()->month)
                    ->whereYear('visit_date', now()->year),
                'assignedBeneficiaries as assigned_count' => fn ($q) => $q
                    ->where('service_group_id', $serviceGroup->id)
                    ->where('status', 'active'),
            ])
            ->withMax('visits as last_visit', 'visit_date')
            ->get();

        // إحصائيات كل خادم
        $servantStats = $servants->map(fn ($servant) => [
            'servant'           => $servant,
            'visits_this_month' => $servant->visits_this_month ?? 0,
            'assigned_count'    => $servant->assigned_count    ?? 0,
            'last_visit'        => $servant->last_visit,
        ]);

        $isAr = app()->getLocale() === 'ar';
        $html = view('reports.service-group-pdf', compact('serviceGroup', 'servantStats', 'isAr'))->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);

        $filename = 'service-group-' . now()->format('Y-m-d') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── تقرير مخدومي الأسرة ──
    public function serviceGroupBeneficiariesPdf(ServiceGroup $serviceGroup): Response
    {
        $beneficiaries = Beneficiary::with(['assignedServant'])
            ->withCount('visits as visits_count')
            ->withMax('visits as last_visit', 'visit_date')
            ->where('service_group_id', $serviceGroup->id)
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get()
            ->map(fn ($b) => [
                'beneficiary'  => $b,
                'last_visit'   => $b->last_visit,
                'visits_count' => $b->visits_count ?? 0,
            ]);

        $isAr = app()->getLocale() === 'ar';
        $html = view('reports.service-group-beneficiaries-pdf',
            compact('serviceGroup', 'beneficiaries', 'isAr'),
        )->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);

        $filename = 'group-beneficiaries-' . now()->format('Y-m-d') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
