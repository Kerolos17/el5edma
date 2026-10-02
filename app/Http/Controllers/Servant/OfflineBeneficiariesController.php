<?php

declare(strict_types=1);

namespace App\Http\Controllers\Servant;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Support\WebAppScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Offline-read payload for the servant field list: the assigned/group
 * beneficiaries with their names, codes and status only — deliberately
 * NO phone numbers, notes, medical or financial data, because this
 * payload is stored in the device cache for offline viewing.
 */
class OfflineBeneficiariesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = auth()->user();

        $beneficiaries = WebAppScope::beneficiaries($user)
            ->with('serviceGroup:id,name')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'code', 'status', 'service_group_id']);

        // Last visit date per beneficiary (one aggregated query).
        $lastVisits = Beneficiary::query()
            ->whereIn('id', $beneficiaries->pluck('id'))
            ->with('visits:id,beneficiary_id,visit_date')
            ->get(['id'])
            ->mapWithKeys(fn (Beneficiary $b) => [
                $b->id => optional($b->visits->sortByDesc('visit_date')->first())->visit_date?->toDateString(),
            ]);

        return response()->json([
            'updated_at'    => now()->toIso8601String(),
            'beneficiaries' => $beneficiaries->map(fn (Beneficiary $b) => [
                'id'         => $b->id,
                'name'       => $b->full_name,
                'code'       => $b->code,
                'status'     => $b->status,
                'group'      => $b->serviceGroup?->name,
                'last_visit' => $lastVisits->get($b->id),
            ])->values(),
        ]);
    }
}
