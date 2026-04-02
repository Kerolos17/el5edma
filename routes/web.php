<?php

use App\Http\Controllers\MedicalFileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileAccessController;

Route::get('/', fn() => redirect('/admin'));

Route::get('/private-files/{path}', [FileAccessController::class, 'show'])
    ->name('private.file')
    ->middleware('auth');

// Language Switcher (authenticated users)
Route::post('/language/{locale}', [\App\Http\Controllers\LocaleController::class , 'switch'])
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
Route::post('/login-code', [\App\Http\Controllers\Auth\CodeLoginController::class , 'login'])
    ->name('login.code')
    ->middleware('throttle:5,1');

// PDF Reports — خارج Livewire
Route::middleware(['web', 'auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/beneficiaries-pdf', [\App\Http\Controllers\ReportController::class , 'beneficiariesPdf'])
        ->name('beneficiaries.pdf');
    Route::get('/visits-pdf', [\App\Http\Controllers\ReportController::class , 'visitsPdf'])
        ->name('visits.pdf');
    Route::get('/unvisited-pdf', [\App\Http\Controllers\ReportController::class , 'unvisitedPdf'])
        ->name('unvisited.pdf');
    Route::get('/beneficiary/{beneficiary}', [\App\Http\Controllers\ReportController::class , 'singleBeneficiaryPdf'])
        ->name('beneficiary.pdf');  
    // تقرير الأسرة
    Route::get('/service-group/{serviceGroup}', [\App\Http\Controllers\ReportController::class , 'serviceGroupPdf'])
        ->name('service-group.pdf');
    // تقرير مخدومي الأسرة
    Route::get('/service-group/{serviceGroup}/beneficiaries', [\App\Http\Controllers\ReportController::class , 'serviceGroupBeneficiariesPdf'])
        ->name('service-group.beneficiaries.pdf');
});
// Medical Files Download
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/medical-files/{medicalFile}/download',
        [MedicalFileController::class, 'download']
    )->name('medical-files.download');
});
