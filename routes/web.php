<?php

use App\Http\Controllers\Auth\CodeLoginController;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\FileAccessController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MedicalFileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/admin'));

Route::get('/private-files/{path}', [FileAccessController::class, 'show'])
    ->name('private.file')
    ->middleware('auth');

// Language Switcher (authenticated users)
Route::post('/language/{locale}', [LocaleController::class, 'switch'])
    ->name('language.switch');

// Language Switcher (guest — login page only)
Route::post('/language-guest/{locale}', function (string $locale) {
    if (! in_array($locale, ['ar', 'en'])) {
        abort(400);
    }
    session(['locale' => $locale]);

    return redirect()->back();
})->name('language.switch.guest');

// Personal Code Login
Route::post('/login-code', [CodeLoginController::class, 'login'])
    ->name('login.code')
    ->middleware('throttle:5,1');

// PDF Reports — خارج Livewire
Route::middleware(['web', 'auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/beneficiaries-pdf', [ReportController::class, 'beneficiariesPdf'])
        ->name('beneficiaries.pdf');
    Route::get('/visits-pdf', [ReportController::class, 'visitsPdf'])
        ->name('visits.pdf');
    Route::get('/unvisited-pdf', [ReportController::class, 'unvisitedPdf'])
        ->name('unvisited.pdf');
    Route::get('/beneficiary/{beneficiary}', [ReportController::class, 'singleBeneficiaryPdf'])
        ->name('beneficiary.pdf');
    // تقرير الأسرة
    Route::get('/service-group/{serviceGroup}', [ReportController::class, 'serviceGroupPdf'])
        ->name('service-group.pdf');
    // تقرير مخدومي الأسرة
    Route::get('/service-group/{serviceGroup}/beneficiaries', [ReportController::class, 'serviceGroupBeneficiariesPdf'])
        ->name('service-group.beneficiaries.pdf');
});
// Medical Files Download
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/medical-files/{medicalFile}/download',
        [MedicalFileController::class, 'download'],
    )->name('medical-files.download');

    Route::post('/fcm-token', [FcmTokenController::class, 'store'])
        ->name('fcm-token.store');
});
