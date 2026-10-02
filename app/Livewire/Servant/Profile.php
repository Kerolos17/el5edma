<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/**
 * الملف الشخصي للخادم الميداني: عرض البيانات + إدارة الصورة الشخصية.
 * الصورة تُضغط في المتصفح قبل الرفع، وتُصغَّر خادميًا إلى أقصى 512px
 * (تظهر فقط كأفاتار)، والملف القديم يُحذف من القرص عند الاستبدال أو
 * الإزالة — لا تراكم بلا نهاية (~200KB كحد أقصى لكل مستخدم).
 */
#[Layout('servant.layouts.app')]
#[Title('الملف الشخصي')]
class Profile extends Component
{
    use WithFileUploads;

    public mixed $newPhoto = null;

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('filament.admin.auth.login'), navigate: false);
    }

    public function updatedNewPhoto(): void
    {
        $this->validate([
            // The browser compresses first; 2MB here is the server-side net.
            'newPhoto' => ['image', 'max:2048', 'mimes:jpeg,jpg,png,webp'],
        ], [
            'newPhoto.image' => __('servant.photo_invalid'),
            'newPhoto.max'   => __('servant.photo_too_large'),
            'newPhoto.mimes' => __('servant.photo_invalid'),
        ]);

        $user = auth()->user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $this->storeResizedPhoto();

        if ($path === null) {
            $this->addError('newPhoto', __('servant.photo_invalid'));
            $this->newPhoto = null;

            return;
        }

        $user->update(['profile_photo' => $path]);
        $this->newPhoto = null;

        $this->dispatch('toast', message: __('servant.photo_updated'), type: 'success');
    }

    public function removePhoto(): void
    {
        $user = auth()->user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->update(['profile_photo' => null]);
        }

        $this->dispatch('toast', message: __('servant.photo_removed'), type: 'success');
    }

    /**
     * Downscale to at most 512px on the longest side and re-encode as JPEG
     * (quality 85) so a profile photo never exceeds ~200KB on disk. Falls
     * back to storing the original when GD cannot process the image.
     */
    private function storeResizedPhoto(): ?string
    {
        $maxSide = 512;

        try {
            $binary = file_get_contents($this->newPhoto->getRealPath());
            $image  = $binary !== false ? @imagecreatefromstring($binary) : false;

            if ($image !== false) {
                $width  = imagesx($image);
                $height = imagesy($image);

                if (max($width, $height) > $maxSide) {
                    $scale = $maxSide / max($width, $height);
                    $image = imagescale(
                        $image,
                        max(1, (int) round($width * $scale)),
                        max(1, (int) round($height * $scale)),
                    );
                }

                ob_start();
                imagejpeg($image, null, 85);
                $encoded = (string) ob_get_clean();
                imagedestroy($image);

                if ($encoded !== '') {
                    $path = 'users/photos/' . Str::uuid() . '.jpg';
                    Storage::disk('public')->put($path, $encoded);

                    return $path;
                }
            }
        } catch (Throwable) {
            // Fall through to storing the original upload untouched.
        }

        return $this->newPhoto->store('users/photos', 'public');
    }

    public function render()
    {
        return view('livewire.servant.profile', [
            'user' => auth()->user()->load('serviceGroup'),
        ]);
    }
}
