<?php

namespace Database\Factories;

use App\Models\BeneficiaryChild;
use App\Models\BeneficiaryRelative;
use App\Models\BeneficiarySibling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    public function definition(): array
    {
        $livingWithFather = fake()->boolean(60);
        $livingWithMother = fake()->boolean(60);
        $livingWithSpouse = fake()->boolean(40);

        return [
            'timestamp' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'province' => fake()->randomElement(['Agusan del Norte', 'Agusan del Sur', 'Dinagat Islands', 'Surigao del Norte', 'Surigao del Sur']),
            'municipality' => fake()->city(),
            'barangay' => 'Barangay '.fake()->numberBetween(1, 50),
            'purok' => fake()->randomElement(['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5']),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional(0.8)->lastName(),
            'extension_name' => fake()->optional(0.1)->randomElement(['Jr.', 'Sr.', 'II', 'III']),
            'sex' => fake()->randomElement(['Male', 'Female']),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'classify_extent_of_damaged_house' => fake()->randomElement(['Totally Damaged (Severely)', 'Partially Damaged (Slightly)']),
            'nhts_pr_classification' => fake()->optional(0.8)->randomElement(['Poor', 'Near Poor', 'Not Poor']),
            'applicable_sector' => fake()->optional(0.6)->randomElements(['4Ps', 'Farmer', 'Fisherfolk', 'Indigenous People', 'Senior Citizen', 'Solo Parent', 'Pregnant Women', 'Lactating Mother', 'PWD', 'Out-of-School Youth', 'Former Rebel/Decommissioned Combatant', 'YAKAP Bayan/Drug Surrenderee', 'LGBTQIA+'], fake()->numberBetween(1, 3)),
            'civil_status' => fake()->randomElement(['Single', 'Married', 'Common Law', 'Widowed', 'Separated', 'Annulled']),

            'living_with_father' => $livingWithFather,
            'father_last_name' => $livingWithFather ? fake()->lastName() : null,
            'father_first_name' => $livingWithFather ? fake()->firstName('male') : null,
            'father_middle_name' => $livingWithFather ? fake()->lastName() : null,
            'father_extension_name' => $livingWithFather ? fake()->optional(0.2)->randomElement(['Jr.', 'Sr.', 'II', 'III']) : null,
            'father_birth_date' => $livingWithFather ? fake()->dateTimeBetween('-90 years', '-40 years')->format('Y-m-d') : null,

            'living_with_mother' => $livingWithMother,
            'mother_last_name' => $livingWithMother ? fake()->lastName() : null,
            'mother_first_name' => $livingWithMother ? fake()->firstName('female') : null,
            'mother_middle_name' => $livingWithMother ? fake()->lastName() : null,
            'mother_birth_date' => $livingWithMother ? fake()->dateTimeBetween('-90 years', '-35 years')->format('Y-m-d') : null,

            'living_with_siblings' => false,

            'living_with_spouse' => $livingWithSpouse,
            'spouse_last_name' => $livingWithSpouse ? fake()->lastName() : null,
            'spouse_first_name' => $livingWithSpouse ? fake()->firstName() : null,
            'spouse_middle_name' => $livingWithSpouse ? fake()->lastName() : null,
            'spouse_extension_name' => $livingWithSpouse ? fake()->optional(0.1)->randomElement(['Jr.', 'Sr.', 'II', 'III']) : null,
            'spouse_birth_date' => $livingWithSpouse ? fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d') : null,

            'living_with_children' => false,
            'living_with_relatives' => false,
        ];
    }

    public function withSiblings(int $count = 2): static
    {
        return $this->has(BeneficiarySibling::factory()->count($count), 'siblings')
            ->state(['living_with_siblings' => true]);
    }

    public function withChildren(int $count = 2): static
    {
        return $this->has(BeneficiaryChild::factory()->count($count), 'children')
            ->state(['living_with_children' => true]);
    }

    public function withRelatives(int $count = 2): static
    {
        return $this->has(BeneficiaryRelative::factory()->count($count), 'relatives')
            ->state(['living_with_relatives' => true]);
    }

    public function withAllFamily(): static
    {
        return $this->withSiblings()->withChildren()->withRelatives();
    }
}
