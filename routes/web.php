<?php

use App\Http\Controllers\Auth\CodeLoginController;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\FileAccessController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MedicalFileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UiPreviewController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app/dashboard');

Route::get('/private-files/{path}', [FileAccessController::class, 'show'])
    ->name('private.file')
    ->middleware('auth');

Route::post('/language/{locale}', [LocaleController::class, 'switch'])
    ->name('language.switch');

Route::post('/language-guest/{locale}', [LocaleController::class, 'switchGuest'])
    ->name('language.switch.guest');

Route::post('/login-code', [CodeLoginController::class, 'login'])
    ->name('login.code')
    ->middleware('throttle:5,1');

Route::middleware(['web', 'auth'])->post('/logout', function () {
    auth()->guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

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
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/medical-files/{medicalFile}/download', [MedicalFileController::class, 'download'])
        ->name('medical-files.download');

    Route::post('/fcm-token', [FcmTokenController::class, 'store'])
        ->name('fcm-token.store')
        ->middleware('throttle:10,1');
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
