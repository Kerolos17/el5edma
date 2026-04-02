<?php

namespace App\Services;

class EagerLoadingService
{
    /**
     * Get eager loading configuration for beneficiaries table
     */
    public static function beneficiariesTable(): array
    {
        return [
            'serviceGroup',
            'assignedServant',
            'createdBy',
        ];
    }

    /**
     * Get eager loading with aggregations for beneficiaries table
     */
    public static function beneficiariesTableWithAggregations(): array
    {
        return [
            'relationships' => self::beneficiariesTable(),
            'withMax'       => ['visits' => 'visit_date'],
        ];
    }

    /**
     * Get eager loading configuration for beneficiary infolist
     */
    public static function beneficiaryInfolist(): array
    {
        return [
            'serviceGroup',
            'assignedServant',
            'createdBy',
        ];
    }

    /**
     * Get eager loading configuration for visits table
     */
    public static function visitsTable(): array
    {
        return [
            'beneficiary.serviceGroup',
            'createdBy',
        ];
    }

    /**
     * Get eager loading configuration for scheduled visits table
     */
    public static function scheduledVisitsTable(): array
    {
        return [
            'beneficiary',
            'assignedServant',
        ];
    }

    /**
     * Get eager loading configuration for service groups table
     */
    public static function serviceGroupsTable(): array
    {
        return [
            'relationships' => ['leader', 'serviceLeader'],
            'withCount'     => ['servants', 'beneficiaries'],
        ];
    }

    /**
     * Get eager loading for single beneficiary PDF
     */
    public static function singleBeneficiaryPdf(): array
    {
        return [
            'serviceGroup',
            'assignedServant',
            'createdBy',
            'medications' => fn ($q) => $q->where('is_active', true),
            'visits'      => fn ($q) => $q->latest('visit_date')->limit(10),
            'visits.createdBy',
            'medicalFiles',
            'prayerRequests',
        ];
    }
}
