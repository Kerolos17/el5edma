<?php

declare(strict_types=1);

namespace App\Http\Controllers\Servant;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Visit;
use Illuminate\Database\QueryException;
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
            'queuedAt'           => 'nullable|integer|min:1',
            'clientUuid'         => 'nullable|string|max:64',
        ]);

        $user = $request->user();

        abort_unless($user->can('create', Visit::class), 403);

        // Idempotency: a retried/replayed queue record with the same client
        // UUID returns the original visit instead of creating a duplicate.
        if (! empty($validated['clientUuid'])) {
            $existing = Visit::query()
                ->where('client_uuid', $validated['clientUuid'])
                ->where('created_by', $user->id)
                ->first();

            if ($existing) {
                return response()->json(['id' => $existing->id, 'duplicate' => true], 200);
            }
        }

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

        // استخدم تاريخ التسجيل الأصلي إذا كان متاحاً وصالحاً، وإلا الآن.
        // الحماية: قيم seconds-vs-ms الخاطئة أو التواريخ المستقبلية/القديمة
        // جداً كانت تنتج تواريخ 1970 — نرفضها بصمت لصالح now().
        $visitDate = now();

        if (! empty($validated['queuedAt']) && $validated['queuedAt'] > 0) {
            try {
                $candidate = Carbon::createFromTimestampMs($validated['queuedAt']);

                if ($candidate->lessThanOrEqualTo(now()->addMinute())
                    && $candidate->greaterThan(now()->subDays(90))) {
                    $visitDate = $candidate;
                }
            } catch (\Throwable) {
                // Fall through to now().
            }
        }

        $visit = null;

        try {
            DB::transaction(function () use ($validated, $user, $visitDate, &$visit) {
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
                    'client_uuid'          => $validated['clientUuid'] ?? null,
                ]);

                DB::afterCommit(function () use ($visit, $user) {
                    $visit->servants()->syncWithoutDetaching($user->id);
                });
            });
        } catch (QueryException $e) {
            // Unique race: two concurrent replays with the same UUID — the
            // loser returns the winner's visit instead of erroring.
            if (! empty($validated['clientUuid']) && $e->getCode() === '23000') {
                $existing = Visit::query()
                    ->where('client_uuid', $validated['clientUuid'])
                    ->where('created_by', $user->id)
                    ->first();

                if ($existing) {
                    return response()->json(['id' => $existing->id, 'duplicate' => true], 200);
                }
            }

            throw $e;
        }

        return response()->json(['id' => $visit->id], 201);
    }
}
