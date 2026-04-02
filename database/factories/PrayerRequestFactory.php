<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrayerRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'title'          => $this->faker->sentence(4),
            'body'           => $this->faker->paragraph(),
            'status'         => $this->faker->randomElement(['pending', 'answered']),
            'created_by'     => User::factory(),
            'answered_at'    => null,
        ];
    }

    public function answered(): static
    {
        return $this->state(fn () => [
            'status'      => 'answered',
            'answered_at' => now(),
        ]);
    }
}
