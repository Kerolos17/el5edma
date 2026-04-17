<?php

namespace App\Filament\Resources\Beneficiaries\Tables;

use App\Enums\UserRole;
use App\Services\CacheService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BeneficiariesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('')
                    ->circular()
                    ->disk('public')
                    ->imageSize(40)
                    ->getStateUsing(fn ($record) => $record->photo)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=2A9393&color=fff',
                    ),

                TextColumn::make('full_name')
                    ->label(__('beneficiaries.full_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label(__('beneficiaries.code'))
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('serviceGroup.name')
                    ->label(__('beneficiaries.service_group'))
                    ->badge()
                    ->color('info'),

                TextColumn::make('assignedServant.name')
                    ->label(__('beneficiaries.assigned_servant'))
                    ->default('—'),

                TextColumn::make('status')
                    ->label(__('beneficiaries.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'gray',
                        'moved'    => 'info',
                        'deceased' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __("beneficiaries.{$state}")),

                TextColumn::make('financial_status')
                    ->label(__('beneficiaries.financial_status'))
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'good'     => 'success',
                        'moderate' => 'warning',
                        'poor', 'very_poor' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state
                            ? __("beneficiaries.{$state}") : '—',
                    ),

                // آخر زيارة — من withMax المحسوب في Resource (بدون queries إضافية)
                TextColumn::make('last_visit_date')
                    ->label(__('beneficiaries.last_visit'))
                    ->getStateUsing(fn ($record) => $record->visits_max_visit_date)
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return __('beneficiaries.never_visited');
                        }
                        $days = (int) now()->diffInDays(Carbon::parse($state));

                        return __('beneficiaries.days_ago', ['days' => $days]);
                    })
                    ->badge()
                    ->color(function ($record) {
                        $last = $record->visits_max_visit_date;
                        if (! $last) {
                            return 'danger';
                        }

                        $days = (int) now()->diffInDays(Carbon::parse($last));

                        return match (true) {
                            $days > 30 => 'danger',
                            $days > 14 => 'warning',
                            default    => 'success',
                        };
                    }),

                TextColumn::make('disability_type')
                    ->label(__('beneficiaries.disability_type'))
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('phone')
                    ->label(__('beneficiaries.phone'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // ── فلتر الأسرة ──
                SelectFilter::make('service_group_id')
                    ->label(__('beneficiaries.service_group'))
                    ->options(fn () => CacheService::getServiceGroupsForUser(Auth::user()))
                    ->searchable(),

                // ── فلتر الخادم ──
                SelectFilter::make('assigned_servant_id')
                    ->label(__('beneficiaries.assigned_servant'))
                    ->options(fn () => CacheService::getActiveServantsForUser(Auth::user()))
                    ->searchable(),

                // ── فلتر الحالة ──
                SelectFilter::make('status')
                    ->label(__('beneficiaries.status'))
                    ->options([
                        'active'   => __('beneficiaries.active'),
                        'inactive' => __('beneficiaries.inactive'),
                        'moved'    => __('beneficiaries.moved'),
                        'deceased' => __('beneficiaries.deceased'),
                    ]),

                // ── فلتر الوضع المادي ──
                SelectFilter::make('financial_status')
                    ->label(__('beneficiaries.financial_status'))
                    ->options([
                        'good'      => __('beneficiaries.good'),
                        'moderate'  => __('beneficiaries.moderate'),
                        'poor'      => __('beneficiaries.poor'),
                        'very_poor' => __('beneficiaries.very_poor'),
                    ]),

                // ── فلتر درجة الإعاقة ──
                SelectFilter::make('disability_degree')
                    ->label(__('beneficiaries.disability_degree'))
                    ->options([
                        'mild'     => __('beneficiaries.mild'),
                        'moderate' => __('beneficiaries.moderate'),
                        'severe'   => __('beneficiaries.severe'),
                    ]),

                // ── فلتر الجنس ──
                SelectFilter::make('gender')
                    ->label(__('beneficiaries.gender'))
                    ->options([
                        'male'   => __('beneficiaries.male'),
                        'female' => __('beneficiaries.female'),
                    ]),

                // ── فلتر حالة الأب ──
                SelectFilter::make('father_status')
                    ->label(__('beneficiaries.father_status'))
                    ->options([
                        'alive'    => __('beneficiaries.alive'),
                        'deceased' => __('beneficiaries.deceased'),
                        'unknown'  => __('beneficiaries.unknown'),
                    ]),

                // ── فلتر حالة الأم ──
                SelectFilter::make('mother_status')
                    ->label(__('beneficiaries.mother_status'))
                    ->options([
                        'alive'    => __('beneficiaries.alive'),
                        'deceased' => __('beneficiaries.deceased'),
                        'unknown'  => __('beneficiaries.unknown'),
                    ]),

                // ── فلتر المحافظة ──
                SelectFilter::make('governorate')
                    ->label(__('beneficiaries.governorate'))
                    ->options(fn () => CacheService::getGovernorates())
                    ->searchable(),

                // ── فلتر عنده أدوية نشطة ──
                TernaryFilter::make('has_medications')
                    ->label(__('beneficiaries.has_medications'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('activeMedications'),
                        false: fn (Builder $query) => $query->whereDoesntHave('activeMedications'),
                        blank: fn (Builder $query) => $query,
                    ),

                // ── فلتر آخر زيارة ──
                Filter::make('last_visit_range')
                    ->label(__('beneficiaries.last_visit'))
                    ->form([
                        Select::make('visit_range')
                            ->label(__('beneficiaries.period'))
                            ->options([
                                '7'     => __('beneficiaries.last_7_days'),
                                '14'    => __('beneficiaries.last_14_days'),
                                '30'    => __('beneficiaries.last_30_days'),
                                'no30'  => __('beneficiaries.not_visited_30'),
                                'no60'  => __('beneficiaries.not_visited_60'),
                                'never' => __('beneficiaries.never_visited'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $range = $data['visit_range'] ?? null;
                        if (! $range) {
                            return $query;
                        }

                        return match ($range) {
                            '7'    => $query->whereHas('visits', fn ($q) => $q->where('visit_date', '>=', now()->subDays(7))),
                            '14'   => $query->whereHas('visits', fn ($q) => $q->where('visit_date', '>=', now()->subDays(14))),
                            '30'   => $query->whereHas('visits', fn ($q) => $q->where('visit_date', '>=', now()->subDays(30))),
                            'no30' => $query->where(fn ($q) => $q->whereDoesntHave('visits')
                                ->orWhereHas('visits', fn ($vq) => $vq->havingRaw('MAX(visit_date) < ?', [now()->subDays(30)]),
                                ),
                            ),
                            'no60' => $query->where(fn ($q) => $q->whereDoesntHave('visits')
                                ->orWhereHas('visits', fn ($vq) => $vq->havingRaw('MAX(visit_date) < ?', [now()->subDays(60)]),
                                ),
                            ),
                            'never' => $query->whereDoesntHave('visits'),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['visit_range'] ?? null)) {
                            return null;
                        }

                        $options = [
                            '7'     => __('beneficiaries.last_7_days'),
                            '14'    => __('beneficiaries.last_14_days'),
                            '30'    => __('beneficiaries.last_30_days'),
                            'no30'  => __('beneficiaries.not_visited_30'),
                            'no60'  => __('beneficiaries.not_visited_60'),
                            'never' => __('beneficiaries.never_visited'),
                        ];

                        return $options[$data['visit_range']] ?? null;
                    }),

            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn () => in_array(
                            Auth::user()?->role,
                            [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader],
                        )),

                    Action::make('whatsapp_beneficiary')
                        ->label(__('beneficiaries.whatsapp_beneficiary'))
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->url(fn ($record) => $record->whatsapp_url)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => filled($record->phone) || filled($record->whatsapp),
                        ),

                    Action::make('whatsapp_guardian')
                        ->label(__('beneficiaries.whatsapp_guardian'))
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('info')
                        ->url(fn ($record) => $record->guardian_whatsapp_url)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => filled($record->guardian_phone)),

                    Action::make('download_pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->url(fn ($record) => route('reports.beneficiary.pdf', $record))
                        ->openUrlInNewTab(),

                    DeleteAction::make()
                        ->visible(fn () => in_array(
                            Auth::user()?->role,
                            [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader],
                        )),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->role === UserRole::SuperAdmin),
                ]),
            ])
            ->defaultSort('full_name')
            ->emptyStateHeading(__('beneficiaries.no_records'))
            ->emptyStateIcon('heroicon-o-heart');
    }
}
