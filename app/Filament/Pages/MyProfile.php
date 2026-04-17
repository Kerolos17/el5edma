<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class MyProfile extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.my-profile';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?int $navigationSort = 99;

    public string $locale = 'ar';
    public $newPhoto      = null;

    public static function canAccess(): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.management');
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
        $user         = Auth::user();
        $this->locale = $user->locale ?? 'ar';
    }

    public function updatedNewPhoto(): void
    {
        $this->validate([
            'newPhoto' => ['image', 'max:1024', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);

        $user = Auth::user();

        // Delete old photo
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $this->newPhoto->store('users/photos', 'public');
        $user->update(['profile_photo' => $path]);
        $this->newPhoto = null;

        Notification::make()
            ->title(__('users.profile_updated'))
            ->success()
            ->send();
    }

    public function removePhoto(): void
    {
        $user = Auth::user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->update(['profile_photo' => null]);
        }

        Notification::make()
            ->title(__('users.profile_updated'))
            ->success()
            ->send();
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
