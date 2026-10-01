<?php

use App\Http\Controllers\Auth\CodeLoginController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\FileAccessController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaintenancePingController;
use App\Http\Controllers\MedicalFileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UiPreviewController;
use App\Livewire\WebApp\WaitingPage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app/dashboard');

Route::get('/_pwa/ping', fn () => response()->noContent());

// Public privacy policy (linked from the registration consent checkbox).
Route::view('/privacy', 'registration.privacy')->name('privacy.show');

// External cron replacement (GitHub Actions → hPanel unavailable crontab).
// CSRF is waived for this path in bootstrap/app.php — the shared token in
// X-Maintenance-Token is the only gate.
Route::post('/maintenance/ping', MaintenancePingController::class)
    ->middleware('throttle:4,1');

Route::get('/private-files/{path}', [FileAccessController::class, 'show'])
    ->name('private.file')
    ->middleware('auth', 'throttle:60,1');

Route::get('/beneficiary-photos/{beneficiary}', [FileAccessController::class, 'showPhoto'])
    ->name('beneficiary-photos.show')
    ->middleware('auth', 'throttle:60,1');

Route::post('/language/{locale}', [LocaleController::class, 'switch'])
    ->name('language.switch');

Route::post('/language-guest/{locale}', [LocaleController::class, 'switchGuest'])
    ->name('language.switch.guest');

Route::post('/login-code', [CodeLoginController::class, 'login'])
    ->name('login.code')
    ->middleware('throttle:5,1');

// Google OAuth: sign-in + the shared join-request path for new visitors.
// Credentials live in .env only; the callback URI must be registered in
// the Google Cloud Console for the current domain.
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');
Route::get('/register/google/{token}', [GoogleAuthController::class, 'showCompletionForm'])
    ->name('registration.google.form');
Route::post('/register/google/{token}', [GoogleAuthController::class, 'completeRegistration'])
    ->name('registration.google.complete')
    ->middleware('throttle:5,60');

Route::middleware(['web', 'auth'])->post('/logout', function () {
    auth()->guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->name('logout');

// Waiting page for join-request applicants: reachable only behind auth, and
// ConfineInactiveUsers confines open-request accounts to exactly this page.
Route::middleware(['web', 'auth'])->get('/registration/status', WaitingPage::class)
    ->name('registration.status');

Route::middleware(['web', 'auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/beneficiaries-pdf', [ReportController::class, 'beneficiariesPdf'])
        ->name('beneficiaries.pdf');
    Route::get('/visits-pdf', [ReportController::class, 'visitsPdf'])
        ->name('visits.pdf');
    Route::get('/unvisited-pdf', [ReportController::class, 'unvisitedPdf'])
        ->name('unvisited.pdf');
    Route::get('/beneficiary/{beneficiary}', [ReportController::class, 'singleBeneficiaryPdf'])
        ->name('beneficiary.pdf');
    Route::get('/service-group/{serviceGroup}', [ReportController::class, 'serviceGroupPdf'])
        ->name('service-group.pdf');
    Route::get('/service-group/{serviceGroup}/beneficiaries', [ReportController::class, 'serviceGroupBeneficiariesPdf'])
        ->name('service-group.beneficiaries.pdf');
    Route::get('/beneficiaries-excel', [ReportController::class, 'beneficiariesExcel'])
        ->name('beneficiaries.excel');
    Route::get('/visits-excel', [ReportController::class, 'visitsExcel'])
        ->name('visits.excel');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/medical-files/{medicalFile}/download', [MedicalFileController::class, 'download'])
        ->name('medical-files.download');

    Route::post('/fcm-token', [FcmTokenController::class, 'store'])
        ->name('fcm-token.store')
        ->middleware('throttle:30,1');

    Route::get('/fcm-token/status', [FcmTokenController::class, 'status'])
        ->name('fcm-token.status')
        ->middleware('throttle:30,1');

    Route::delete('/fcm-token', [FcmTokenController::class, 'destroy'])
        ->name('fcm-token.destroy')
        ->middleware('throttle:30,1');
});

Route::middleware(['web', 'auth'])->get('/ui-preview/servant', [UiPreviewController::class, 'servant'])
    ->name('ui-preview.servant');

Route::middleware(['web', 'auth'])->get('/ui-preview/full-demo', [UiPreviewController::class, 'fullDemo'])
    ->name('ui-preview.full-demo');

Route::get('/register/{token}', [RegistrationController::class, 'show'])
    ->name('registration.show');
Route::post('/register/{token}', [RegistrationController::class, 'store'])
    ->name('registration.store')
    ->middleware('throttle:5,60');
Route::get('/register', [RegistrationController::class, 'showPublic'])
    ->name('registration.public');
Route::post('/register', [RegistrationController::class, 'storePublic'])
    ->name('registration.public.store')
    ->middleware('throttle:5,60');

require __DIR__ . '/servant.php';
require __DIR__ . '/app.php';
