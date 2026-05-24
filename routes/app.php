<?php

use App\Livewire\WebApp\Dashboard;
use App\Livewire\WebApp\BeneficiariesPage;
use App\Livewire\WebApp\MedicalFilesPage;
use App\Livewire\WebApp\PrayerRequestsPage;
use App\Livewire\WebApp\ReportsPage;
use App\Livewire\WebApp\ScheduledVisitsPage;
use App\Livewire\WebApp\AuditLogsPage;
use App\Livewire\WebApp\BeneficiaryProfilePage;
use App\Livewire\WebApp\NotificationsPage;
use App\Livewire\WebApp\ProfilePage;
use App\Livewire\WebApp\ServiceGroupsPage;
use App\Livewire\WebApp\UsersPage;
use App\Livewire\WebApp\VisitsPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'app.access'])
    ->prefix('app')
    ->name('app.')
    ->group(function () {
        Route::redirect('/', '/app/dashboard')->name('home');
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        Route::get('/beneficiaries', BeneficiariesPage::class)->name('beneficiaries');
        Route::get('/visits', VisitsPage::class)->name('visits');
        Route::get('/scheduled-visits', ScheduledVisitsPage::class)->name('scheduled-visits');
        Route::get('/prayer-requests', PrayerRequestsPage::class)->name('prayer-requests');
        Route::get('/medical-files', MedicalFilesPage::class)->name('medical-files');
        Route::get('/reports', ReportsPage::class)->name('reports');
        Route::get('/users', UsersPage::class)->name('users');
        Route::get('/service-groups', ServiceGroupsPage::class)->name('service-groups');
        Route::get('/notifications', NotificationsPage::class)->name('notifications');
        Route::get('/audit-logs', AuditLogsPage::class)->name('audit-logs');
        Route::get('/beneficiary/{beneficiary}', BeneficiaryProfilePage::class)->name('beneficiary-profile');
        Route::get('/profile', ProfilePage::class)->name('profile');
    });
