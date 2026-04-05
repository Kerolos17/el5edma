<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class MyProfile extends Page
{
    protected string $view = 'filament.pages.my-profile';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?int $navigationSort = 99;

    public string $locale = 'ar';

    public static function canAccess(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('users.my_profile');
    }

    public function getTitle(): string
    {
        return __('users.my_profile');
    }

    public function mount(): void
    {
        $this->locale = Auth::user()->locale ?? 'ar';
    }

    public function saveLocale(): void
    {
        if (! in_array($this->locale, ['ar', 'en'])) {
            return;
        }

        Auth::user()->update(['locale' => $this->locale]);
        session(['locale' => $this->locale]);

        $this->redirect(static::getUrl(), navigate: false);
    }
}
