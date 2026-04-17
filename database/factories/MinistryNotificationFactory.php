<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MinistryNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type'    => $this->faker->randomElement([
                'birthday',
                'critical_case',
                'visit_reminder',
                'unvisited_alert',
                'new_beneficiary',
            ]),
            'title'      => $this->faker->sentence(4),
            'body'       => $this->faker->sentence(10),
            'data'       => null,
            'read_at'    => null,
            'created_at' => now(),
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }

    public function unread(): static
    {
        return $this->state(fn () => ['read_at' => null]);
    }
}
