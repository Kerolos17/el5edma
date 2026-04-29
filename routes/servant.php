<?php

use App\Http\Controllers\Servant\OfflineVisitSyncController;
use App\Livewire\Servant\BeneficiaryDetail;
use App\Livewire\Servant\BeneficiaryList;
use App\Livewire\Servant\Dashboard;
use App\Livewire\Servant\PrayerRequestList;
use App\Livewire\Servant\Profile;
use App\Livewire\Servant\ScheduledVisitList;
use App\Livewire\Servant\VisitList;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'servant.access'])
    ->prefix('servant')
    ->name('servant.')
    ->group(function () {
        Route::redirect('/', '/servant/dashboard');
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/beneficiaries', BeneficiaryList::class)->name('beneficiaries');
        Route::get('/beneficiaries/{beneficiary}', BeneficiaryDetail::class)->name('beneficiaries.show');
        Route::get('/visits', VisitList::class)->name('visits');
        Route::get('/scheduled-visits', ScheduledVisitList::class)->name('scheduled-visits');
        Route::get('/profile', Profile::class)->name('profile');
        Route::get('/prayer-requests', PrayerRequestList::class)->name('prayer-requests');
        Route::post('/visits/sync', OfflineVisitSyncController::class)->name('visits.sync');
    });
