<?php

namespace App\Providers;

use App\Broadcasting\TestBroadcaster;
use App\Livewire\NotificationsBell;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use App\Observers\BeneficiaryObserver;
use App\Observers\ServiceGroupObserver;
use App\Observers\UserObserver;
use App\Observers\VisitObserver;
use App\Services\QueryMonitoringService;
use App\Services\RegistrationLinkService;
use App\Services\RegistrationService;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // تسجيل خدمات التسجيل الذاتي للخدام
        $this->app->singleton(RegistrationLinkService::class);
        $this->app->singleton(RegistrationService::class);


        // ضمان معرفة Firebase بالـ Project ID دائماً
        $this->app->extend(Factory::class, function (Factory $factory) {
            $projectId = config('firebase.projects.app.project_id')
                ?? env('FIREBASE_PROJECT_ID')
                ?? env('GOOGLE_CLOUD_PROJECT');

            if ($projectId) {
                return $factory->withProjectId($projectId);
            }

            return $factory;
        });
    }

    public function boot(): void
    {
        // Register a 'test' broadcast driver for use in automated tests.
        // This driver validates channel auth without requiring real WebSocket credentials.
        if ($this->app->environment('testing')) {
            /** @var BroadcastManager $manager */
            $manager = $this->app->make(BroadcastManager::class);
            $manager->extend('test', fn ($app) => $app->make(TestBroadcaster::class));
        }

        // تسجيل الـ Observers
        Beneficiary::observe(BeneficiaryObserver::class);
        Visit::observe(VisitObserver::class);
        User::observe(UserObserver::class);
        ServiceGroup::observe(ServiceGroupObserver::class);

        // تسجيل الـ Livewire component
        Livewire::component(
            'notifications-bell',
            NotificationsBell::class,
        );

        // Enable query monitoring in production
        if (app()->environment('production')) {
            QueryMonitoringService::enable();
        }
    }
}
