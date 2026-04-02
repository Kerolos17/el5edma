<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => 'أسرة ' . $this->faker->word(),
            'description' => $this->faker->sentence(),
            'is_active'   => true,
        ];
    }
}
