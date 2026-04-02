<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'file_path'      => 'medical-files/' . $this->faker->uuid() . '.pdf',
            'file_type'      => $this->faker->randomElement(['report', 'image', 'document']),
            'title'          => $this->faker->sentence(3),
            'uploaded_by'    => User::factory(),
            'created_at'     => now(),
        ];
    }
}
