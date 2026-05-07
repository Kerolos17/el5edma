<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('servant.layouts.app')]
#[Title('الملف الشخصي')]
class Profile extends Component
{
    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('filament.admin.auth.login'), navigate: false);
    }

    public function render()
    {
        return view('livewire.servant.profile', [
            'user' => auth()->user()->load('serviceGroup'),
        ]);
    }
}
