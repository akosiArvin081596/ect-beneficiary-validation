<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BeneficiaryRelative>
 */
class BeneficiaryRelativeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-1 year')->format('Y-m-d'),
            'relationship' => fake()->randomElement(['Uncle', 'Aunt', 'Cousin', 'Nephew', 'Niece', 'Grandparent']),
        ];
    }
}
