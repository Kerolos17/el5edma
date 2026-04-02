<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'model_type' => User::class,
            'model_id'   => 1,
            'action'     => $this->faker->randomElement(['created', 'updated', 'deleted']),
            'old_values' => null,
            'new_values' => ['name' => $this->faker->name()],
            'ip_address' => $this->faker->ipv4(),
            'created_at' => now(),
        ];
    }
}
