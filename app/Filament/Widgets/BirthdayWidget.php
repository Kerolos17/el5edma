<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BirthdayWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return __('dashboard.birthdays_title');
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();

        // Filter birthdays in database query before eager loading relationships
        $today      = now()->startOfDay();
        $todayMonth = $today->month;
        $todayDay   = $today->day;

        // Calculate the date 7 days from now
        $sevenDaysLater = $today->copy()->addDays(7);
        $sevenDaysMonth = $sevenDaysLater->month;
        $sevenDaysDay   = $sevenDaysLater->day;

        $query = Beneficiary::query()
            ->with(['assignedServant'])
            ->where('status', 'active')
            ->whereNotNull('birth_date');

        // Apply role-based scoping
        if ($user->role === UserRole::FamilyLeader) {
            $query->where('service_group_id', $user->service_group_id);
        } elseif ($user->role === UserRole::Servant) {
            $query->where('assigned_servant_id', $user->id);
        }

        // Filter birthdays in database query
        // Handle month boundary crossing (e.g., Dec 28 to Jan 3)
        if ($todayMonth === $sevenDaysMonth) {
            // Same month - simple range
            $query->whereMonth('birth_date', $todayMonth)
                ->whereDay('birth_date', '>=', $todayDay)
                ->whereDay('birth_date', '<=', $sevenDaysDay);
        } else {
            // Crosses month boundary
            $query->where(function ($q) use ($todayMonth, $todayDay, $sevenDaysMonth, $sevenDaysDay) {
                $q->where(function ($sub) use ($todayMonth, $todayDay) {
                    $sub->whereMonth('birth_date', $todayMonth)
                        ->whereDay('birth_date', '>=', $todayDay);
                })->orWhere(function ($sub) use ($sevenDaysMonth, $sevenDaysDay) {
                    $sub->whereMonth('birth_date', $sevenDaysMonth)
                        ->whereDay('birth_date', '<=', $sevenDaysDay);
                });
            });
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('beneficiaries.full_name')),

                TextColumn::make('birth_date')
                    ->label(__('beneficiaries.birth_date'))
                    ->formatStateUsing(function ($state) {
                        $birthday = Carbon::parse($state)->setYear(now()->year);
                        if ($birthday->lt(now()->startOfDay())) {
                            $birthday->addYear();
                        }

                        $diff = (int) now()->startOfDay()->diffInDays($birthday);
                        $age  = (int) Carbon::parse($state)->diffInYears(now()) + 1;

                        $dayLabel = match (true) {
                            $diff === 0 => __('dashboard.today'),
                            $diff === 1 => __('dashboard.tomorrow'),
                            default     => __('dashboard.days_ago', ['days' => $diff]),
                        };

                        return $dayLabel . ' — ' . __('dashboard.turning_years', ['age' => $age]);
                    }),

                TextColumn::make('assignedServant.name')
                    ->label(__('beneficiaries.assigned_servant'))
                    ->default('—'),
            ])
            ->emptyStateHeading(__('dashboard.no_upcoming_birthdays'))
            ->emptyStateIcon('heroicon-o-cake');
    }
}
