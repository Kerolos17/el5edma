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

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {}

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
        \Livewire\Livewire::component(
            'notifications-bell-widget',
            \App\Filament\Widgets\NotificationsBellWidget::class
        );

        // Enable query monitoring in production
        if (app()->environment('production')) {
            QueryMonitoringService::enable();
        }
    }
}
