<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'name'           => $this->faker->word() . ' ' . $this->faker->randomElement(['500mg', '250mg', '100mg']),
            'dosage'         => $this->faker->randomElement(['1 قرص', '2 قرص', '5 مل']),
            'frequency'      => $this->faker->randomElement([1, 2, 3]),
            'timing'         => $this->faker->randomElement(['morning', 'evening', 'with_food', 'as_needed']),
            'notes'          => $this->faker->optional()->sentence(),
            'is_active'      => true,
        ];
    }
}
