<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('web-app.layouts.app')]
class ProfilePage extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $locale = 'ar';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public mixed $newPhoto = null;

    public bool $showPhotoForm = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) ($user->phone ?? '');
        $this->locale = $user->locale ?? 'ar';
    }

    public function updatedNewPhoto(): void
    {
        $this->validate([
            'newPhoto' => ['image', 'max:1024', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);

        $user = auth()->user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $this->newPhoto->store('users/photos', 'public');
        $user->update(['profile_photo' => $path]);
        $this->newPhoto = null;
        $this->showPhotoForm = false;

        $this->dispatch('toast', message: __('web_app.toasts.profile_updated'), type: 'success');
    }

    public function removePhoto(): void
    {
        $user = auth()->user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->update(['profile_photo' => null]);
        }

        $this->dispatch('toast', message: __('web_app.toasts.profile_updated'), type: 'success');
    }

    public function saveProfile(): void
    {
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'locale' => ['required', 'in:ar,en'],
        ]);

        $user->update([
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'locale' => $this->locale,
        ]);

        session(['locale' => $this->locale]);

        $this->dispatch('toast', message: __('web_app.toasts.profile_updated'), type: 'success');
    }

    public function updatePassword(): void
    {
        $user = auth()->user();

        $this->validate([
            'currentPassword' => ['required'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed:newPasswordConfirmation'],
        ]);

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', __('auth.password'));

            return;
        }

        $user->update(['password' => Hash::make($this->newPassword)]);

        $this->reset('currentPassword', 'newPassword', 'newPasswordConfirmation');

        $this->dispatch('toast', message: __('web_app.profile.password_updated'), type: 'success');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.web-app.profile-page', [
            'user' => auth()->user(),
        ]);
    }
}
