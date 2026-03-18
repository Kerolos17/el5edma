<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $locale = Auth::user()->locale ?? config('app.locale');
        } else {
            $locale = session('locale', config('app.locale'));
        }

        $availableLocales = config('app.available_locales', ['ar', 'en']);
        if (! in_array($locale, $availableLocales)) {
            $locale = config('app.fallback_locale');
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
