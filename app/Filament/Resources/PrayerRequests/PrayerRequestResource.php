<?php
namespace App\Filament\Resources\PrayerRequests;

use App\Filament\Resources\PrayerRequests\Pages\CreatePrayerRequest;
use App\Filament\Resources\PrayerRequests\Pages\EditPrayerRequest;
use App\Filament\Resources\PrayerRequests\Pages\ListPrayerRequests;
use App\Filament\Resources\PrayerRequests\Pages\ViewPrayerRequest;
use App\Filament\Resources\PrayerRequests\Schemas\PrayerRequestForm;
use App\Filament\Resources\PrayerRequests\Schemas\PrayerRequestInfolist;
use App\Filament\Resources\PrayerRequests\Tables\PrayerRequestsTable;
use App\Models\PrayerRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class PrayerRequestResource extends Resource
{
    protected static ?string $model = PrayerRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.ministry');
    }

    public static function getNavigationLabel(): string
    {
        return __('prayer.title');
    }

    public static function getModelLabel(): string
    {
        return __('prayer.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('prayer.title');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['beneficiary', 'createdBy']);
        $user  = Auth::user();

        return match ($user?->role) {
            UserRole::FamilyLeader => $query->whereHas('beneficiary', fn($q) =>
                $q->where('service_group_id', $user->service_group_id)
            ),
            UserRole::Servant      => $query->where('created_by', $user->id),
            default                => $query,
        };
    }

    public static function form(Schema $schema): Schema
    {
        return PrayerRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PrayerRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrayerRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPrayerRequests::route('/'),
            'create' => CreatePrayerRequest::route('/create'),
            'view'   => ViewPrayerRequest::route('/{record}'),
            'edit'   => EditPrayerRequest::route('/{record}/edit'),
        ];
    }

    // ── Authorization: الخادم يستطيع الإنشاء فقط، لا يستطيع التعديل أو الحذف ──

    public static function canCreate(): bool
    {
        // الخادم يستطيع إنشاء طلبات صلاة
        return true;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // فقط المسؤولين يستطيعون التعديل
        return \App\Helpers\PermissionHelper::canModify();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // فقط المسؤولين يستطيعون الحذف
        return \App\Helpers\PermissionHelper::canModify();
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }
}
