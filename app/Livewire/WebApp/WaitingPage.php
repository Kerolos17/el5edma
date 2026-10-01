<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * صفحة انتظار صاحب طلب الانضمام: تعرض حالة طلبه وتاريخ إرساله وملاحظة
 * المراجعة إن وُجدت. لا يصل إليها إلا صاحب الحساب نفسه، ولا تعرض أي بيانات
 * غير بيانات طلبه.
 */
#[Layout('web-app.layouts.waiting')]
#[Title('حالة طلب الانضمام')]
class WaitingPage extends Component
{
    public function mount(): mixed
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // An approved member has nothing to wait for.
        if ($user->is_active) {
            return redirect()->route($user->homeRoute());
        }

        return null;
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.web-app.waiting-page', [
            'joinRequest' => $user?->joinRequest()->with('serviceGroup')->first(),
        ]);
    }
}
