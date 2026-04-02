<?php

namespace Database\Factories;

use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BeneficiaryFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1;

        return [
            'full_name'         => $this->faker->name(),
            'code'              => 'SN-' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
            'birth_date'        => $this->faker->date('Y-m-d', '-5 years'),
            'gender'            => $this->faker->randomElement(['male', 'female']),
            'phone'             => $this->faker->phoneNumber(),
            'status'            => 'active',
            'disability_type'   => $this->faker->randomElement(['جسدية', 'ذهنية', 'بصرية']),
            'disability_degree' => $this->faker->randomElement(['mild', 'moderate', 'severe']),
            'financial_status'  => $this->faker->randomElement(['good', 'moderate', 'poor']),
            'governorate'       => $this->faker->randomElement(['القاهرة', 'الجيزة', 'الإسكندرية']),
            'area'              => $this->faker->city(),
            // ── Required FKs ──
            'service_group_id' => ServiceGroup::factory(),
            'created_by'       => User::factory(),
        ];
    }

    // State لو محتاج تضيف صورة
    public function withPhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'photo' => 'beneficiaries/photos/test-photo.jpg',
        ]);
    }
}
