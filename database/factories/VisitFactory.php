<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'type'           => $this->faker->randomElement([
                'home_visit', 'phone_call', 'church_meeting',
            ]),
            'visit_date'         => $this->faker->dateTimeBetween('-6 months', 'now'),
            'duration_minutes'   => $this->faker->numberBetween(15, 120),
            'beneficiary_status' => $this->faker->randomElement([
                'great', 'good', 'needs_follow',
            ]),
            'feedback'             => $this->faker->sentence(),
            'is_critical'          => false,
            'needs_family_leader'  => false,
            'needs_service_leader' => false,
            'created_by'           => User::factory(),
        ];
    }
}
