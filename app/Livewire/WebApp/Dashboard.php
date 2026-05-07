<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Models\MedicalFile;
use App\Models\PrayerRequest;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('web-app.layouts.app')]
#[Title('لوحة التحكم')]
class Dashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $beneficiaryScope = WebAppScope::beneficiaries($user);

        $stats = [
            [
                'label' => 'المخدومون',
                'value' => (clone $beneficiaryScope)->count(),
                'icon' => 'ph-users-three',
                'tone' => 'blue',
            ],
            [
                'label' => 'زيارات هذا الشهر',
                'value' => Visit::query()
                    ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', (clone $beneficiaryScope)->select('id')))
                    ->whereMonth('visit_date', now()->month)
                    ->whereYear('visit_date', now()->year)
                    ->count(),
                'icon' => 'ph-clipboard-text',
                'tone' => 'emerald',
            ],
            [
                'label' => 'زيارات مجدولة',
                'value' => ScheduledVisit::query()
                    ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', (clone $beneficiaryScope)->select('id')))
                    ->where('scheduled_date', '>=', now()->toDateString())
                    ->where('status', '!=', 'completed')
                    ->count(),
                'icon' => 'ph-calendar-check',
                'tone' => 'amber',
            ],
            [
                'label' => 'حالات حرجة',
                'value' => Visit::query()
                    ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', (clone $beneficiaryScope)->select('id')))
                    ->where('is_critical', true)
                    ->whereNull('critical_resolved_at')
                    ->count(),
                'icon' => 'ph-warning-circle',
                'tone' => 'rose',
            ],
        ];

        $secondaryStats = [
            'openPrayerRequests' => PrayerRequest::query()
                ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', (clone $beneficiaryScope)->select('id')))
                ->where('status', 'open')
                ->count(),
            'medicalFiles' => MedicalFile::query()
                ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', (clone $beneficiaryScope)->select('id')))
                ->count(),
            'users' => $user->can('viewAny', User::class) ? WebAppScope::users($user)->count() : null,
            'serviceGroups' => $user->can('viewAny', ServiceGroup::class) ? WebAppScope::serviceGroups($user)->count() : null,
        ];

        $recentVisits = WebAppScope::visits($user)
            ->latest('visit_date')
            ->limit(6)
            ->get();

        return view('livewire.web-app.dashboard', [
            'roleLabel' => WebAppScope::roleLabel($user->role),
            'stats' => $stats,
            'secondaryStats' => $secondaryStats,
            'recentVisits' => $recentVisits,
        ]);
    }
}
