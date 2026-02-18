<?php

namespace Database\Factories;

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
        $livingWithSiblings = fake()->boolean(50);
        $livingWithSpouse = fake()->boolean(40);
        $livingWithChildren = fake()->boolean(30);
        $livingWithRelatives = fake()->boolean(20);

        $siblingsCount = $livingWithSiblings ? fake()->numberBetween(1, 5) : 0;
        $childrenCount = $livingWithChildren ? fake()->numberBetween(1, 4) : 0;
        $relativesCount = $livingWithRelatives ? fake()->numberBetween(1, 3) : 0;

        $siblings = [];
        for ($i = 0; $i < $siblingsCount; $i++) {
            $siblings[] = [
                'last_name' => fake()->lastName(),
                'first_name' => fake()->firstName(),
                'middle_name' => fake()->lastName(),
                'birth_date' => fake()->dateTimeBetween('-60 years', '-1 year')->format('Y-m-d'),
            ];
        }

        $children = [];
        for ($i = 0; $i < $childrenCount; $i++) {
            $children[] = [
                'last_name' => fake()->lastName(),
                'first_name' => fake()->firstName(),
                'middle_name' => fake()->lastName(),
                'birth_date' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            ];
        }

        $relatives = [];
        $relationships = ['Uncle', 'Aunt', 'Cousin', 'Nephew', 'Niece', 'Grandparent'];
        for ($i = 0; $i < $relativesCount; $i++) {
            $relatives[] = [
                'last_name' => fake()->lastName(),
                'first_name' => fake()->firstName(),
                'middle_name' => fake()->lastName(),
                'birth_date' => fake()->dateTimeBetween('-70 years', '-1 year')->format('Y-m-d'),
                'relationship' => fake()->randomElement($relationships),
            ];
        }

        return [
            'timestamp' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'province' => fake()->randomElement(['Agusan del Norte', 'Agusan del Sur', 'Dinagat Islands', 'Surigao del Norte', 'Surigao del Sur']),
            'municipality' => fake()->city(),
            'barangay' => 'Barangay '.fake()->numberBetween(1, 50),
            'purok' => fake()->optional(0.7)->randomElement(['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5']),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional(0.8)->lastName(),
            'extension_name' => fake()->optional(0.1)->randomElement(['Jr.', 'Sr.', 'II', 'III']),
            'sex' => fake()->randomElement(['Male', 'Female']),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'classify_extent_of_damaged_house' => fake()->randomElement(['Totally Damaged', 'Partially Damaged']),
            'nhts_pr_classification' => fake()->randomElement(['Poor', 'Near Poor', 'Not Poor']),
            'applicable_sector' => fake()->optional(0.6)->randomElements(['Senior Citizen', 'PWD', 'Solo Parent', 'Indigenous People'], fake()->numberBetween(1, 2)),
            'civil_status' => fake()->randomElement(['Single', 'Married', 'Widowed', 'Separated', 'Annulled']),

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

            'living_with_siblings' => $livingWithSiblings,
            'siblings_count' => $siblingsCount,
            'siblings' => $livingWithSiblings ? $siblings : null,

            'living_with_spouse' => $livingWithSpouse,
            'spouse_last_name' => $livingWithSpouse ? fake()->lastName() : null,
            'spouse_first_name' => $livingWithSpouse ? fake()->firstName() : null,
            'spouse_middle_name' => $livingWithSpouse ? fake()->lastName() : null,
            'spouse_extension_name' => $livingWithSpouse ? fake()->optional(0.1)->randomElement(['Jr.', 'Sr.', 'II', 'III']) : null,
            'spouse_birth_date' => $livingWithSpouse ? fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d') : null,

            'living_with_children' => $livingWithChildren,
            'children_count' => $childrenCount,
            'children' => $livingWithChildren ? $children : null,

            'living_with_relatives' => $livingWithRelatives,
            'relatives_count' => $relativesCount,
            'relatives' => $livingWithRelatives ? $relatives : null,
        ];
    }
}
