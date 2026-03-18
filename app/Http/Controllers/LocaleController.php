<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        if (! in_array($locale, ['ar', 'en'])) {
            abort(400);
        }

        if (! Auth::check()) {
            abort(401);
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $user->can('change-language')) {
            abort(403);
        }

        session(['locale' => $locale]);

        $user->update(['locale' => $locale]);

        return redirect()->back();
    }
}
