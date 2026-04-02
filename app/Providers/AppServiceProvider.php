<?php
namespace App\Providers;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use App\Observers\BeneficiaryObserver;
use App\Observers\ServiceGroupObserver;
use App\Observers\UserObserver;
use App\Observers\VisitObserver;
use App\Services\QueryMonitoringService;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // تسجيل خدمات التسجيل الذاتي للخدام
        $this->app->singleton(\App\Services\RegistrationLinkService::class);
        $this->app->singleton(\App\Services\RegistrationService::class);
    }

    public function boot(): void
    {
        // تعيين اللغة
        if (Auth::check()) {
            $locale = Auth::user()->locale ?? 'ar';
            App::setLocale($locale);
            Carbon::setLocale($locale);
        }

        // تسجيل الـ Observers
        Beneficiary::observe(BeneficiaryObserver::class);
        Visit::observe(VisitObserver::class);
        User::observe(UserObserver::class);
        ServiceGroup::observe(ServiceGroupObserver::class);

        // تسجيل الـ Livewire component
        Livewire::component(
            'notifications-bell',
            \App\Livewire\NotificationsBell::class,
        );

        // Enable query monitoring in production
        if (app()->environment('production')) {
            QueryMonitoringService::enable();
        }
    }
}
