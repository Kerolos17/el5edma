<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class CodeLoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'min:4', 'max:6'],
        ]);

        $codeHash = hash('sha256', $request->code);

        $user = User::where('personal_code_hash', $codeHash)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return back()->withErrors([
                'code' => __('auth.invalid_code'),
            ]);
        }

        Auth::login($user); // No "remember me" — sessions expire on browser close for security

        $user->update(['last_login_at' => now()]);

        App::setLocale($user->locale ?? 'ar');

        return redirect('/admin');
    }
}
