<?php

declare(strict_types=1);

namespace Database\Seeders;

use RuntimeException;

trait PreventsProductionDemoSeeding
{
    protected function preventProductionDemoSeeding(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo data seeders are disabled in production.');
        }
    }
}
