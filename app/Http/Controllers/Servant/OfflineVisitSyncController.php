<?php

declare(strict_types=1);

namespace App\Http\Controllers\Servant;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OfflineVisitSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'beneficiary_id'     => 'required|integer|exists:beneficiaries,id',
            'visitType'          => 'required|in:home_visit,phone_call,church_meeting',
            'beneficiaryStatus'  => 'required|in:great,good,needs_follow,critical',
            'durationMinutes'    => 'nullable|integer|min:1|max:480',
            'feedback'           => 'nullable|string|max:2000',
            'isCritical'         => 'boolean',
            'needsFamilyLeader'  => 'boolean',
            'needsServiceLeader' => 'boolean',
            'queuedAt'           => 'nullable|integer',
        ]);

        $user = $request->user();

        // Ownership check — الخادم يجب أن يملك هذا المخدوم
        $owned = Beneficiary::query()
            ->where('id', $validated['beneficiary_id'])
            ->where(function ($q) use ($user) {
                $q->where('assigned_servant_id', $user->id)
                    ->when(
                        $user->service_group_id,
                        fn ($q2) => $q2->orWhere('service_group_id', $user->service_group_id),
                    );
            })
            ->exists();

        if (! $owned) {
            return response()->json(['message' => 'لم يعد المخدوم مُعيّناً لك'], 409);
        }

        // استخدم تاريخ التسجيل الأصلي إذا كان متاحاً
        $visitDate = $validated['queuedAt']
            ? Carbon::createFromTimestampMs($validated['queuedAt'])
            : now();

        $visit = DB::transaction(function () use ($validated, $user, $visitDate) {
            $visit = Visit::create([
                'beneficiary_id'       => $validated['beneficiary_id'],
                'type'                 => $validated['visitType'],
                'visit_date'           => $visitDate,
                'duration_minutes'     => $validated['durationMinutes'] ?? null,
                'beneficiary_status'   => $validated['beneficiaryStatus'],
                'feedback'             => $validated['feedback'] ?? null,
                'is_critical'          => (bool) ($validated['isCritical'] ?? false),
                'needs_family_leader'  => (bool) ($validated['needsFamilyLeader'] ?? false),
                'needs_service_leader' => (bool) ($validated['needsServiceLeader'] ?? false),
                'created_by'           => $user->id,
            ]);

            $visit->servants()->attach($user->id);

            return $visit;
        });

        return response()->json(['id' => $visit->id], 201);
    }
}
