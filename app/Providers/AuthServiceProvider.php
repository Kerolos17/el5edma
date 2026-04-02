<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\MinistryNotification;
use App\Models\PrayerRequest;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use App\Policies\AuditLogPolicy;
use App\Policies\BeneficiaryPolicy;
use App\Policies\MedicalFilePolicy;
use App\Policies\MinistryNotificationPolicy;
use App\Policies\PrayerRequestPolicy;
use App\Policies\ScheduledVisitPolicy;
use App\Policies\ServiceGroupPolicy;
use App\Policies\UserPolicy;
use App\Policies\VisitPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AuditLog::class             => AuditLogPolicy::class,
        Beneficiary::class          => BeneficiaryPolicy::class,
        MedicalFile::class          => MedicalFilePolicy::class,
        MinistryNotification::class => MinistryNotificationPolicy::class,
        PrayerRequest::class        => PrayerRequestPolicy::class,
        ScheduledVisit::class       => ScheduledVisitPolicy::class,
        ServiceGroup::class         => ServiceGroupPolicy::class,
        User::class                 => UserPolicy::class,
        Visit::class                => VisitPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
