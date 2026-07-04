<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledVisitFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (ScheduledVisit $scheduledVisit): void {
            if ($scheduledVisit->assigned_servant_id) {
                $scheduledVisit->syncAssignedServants([$scheduledVisit->assigned_servant_id]);
            }
        });
    }

    public function definition(): array
    {
        return [
            'beneficiary_id'      => Beneficiary::factory(),
            'assigned_servant_id' => User::factory(),
            'scheduled_date'      => $this->faker->dateTimeBetween('now', '+30 days'),
            'scheduled_time'      => $this->faker->time('H:i'),
            'notes'               => $this->faker->sentence(),
            'status'              => 'pending',
            'reminder_sent_at'    => null,
            'completed_visit_id'  => null,
            'created_by'          => User::factory(),
        ];
    }
}
