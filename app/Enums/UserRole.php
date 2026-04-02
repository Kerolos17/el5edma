<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case ServiceLeader = 'service_leader';
    case FamilyLeader = 'family_leader';
    case Servant = 'servant';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('users.roles.super_admin'),
            self::ServiceLeader => __('users.roles.service_leader'),
            self::FamilyLeader => __('users.roles.family_leader'),
            self::Servant => __('users.roles.servant'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->toArray();
    }

    public function isAdminLevel(): bool
    {
        return in_array($this, [self::SuperAdmin, self::ServiceLeader]);
    }
}
