<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $withFather = $this->boolean('living_with_father');
        $withMother = $this->boolean('living_with_mother');
        $withSiblings = $this->boolean('living_with_siblings');
        $withSpouse = $this->boolean('living_with_spouse');
        $withChildren = $this->boolean('living_with_children');
        $withRelatives = $this->boolean('living_with_relatives');

        return [
            // Basic info
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'purok' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'extension_name' => ['nullable', 'string', 'max:50'],
            'sex' => ['required', 'in:Male,Female'],
            'birth_date' => ['required', 'date', 'before:today'],
            'classify_extent_of_damaged_house' => ['required', 'in:Totally Damaged (Severely),Partially Damaged (Slightly)'],
            'nhts_pr_classification' => ['nullable', 'in:Poor,Near Poor,Not Poor'],
            'applicable_sector' => ['nullable', 'array'],
            'applicable_sector.*' => ['string', 'max:255'],
            'civil_status' => ['required', 'in:Single,Married,Common Law,Widowed,Separated,Annulled'],

            // Father
            'living_with_father' => ['nullable', 'boolean'],
            'father_last_name' => [Rule::requiredIf($withFather), 'nullable', 'string', 'max:255'],
            'father_first_name' => [Rule::requiredIf($withFather), 'nullable', 'string', 'max:255'],
            'father_middle_name' => ['nullable', 'string', 'max:255'],
            'father_extension_name' => ['nullable', 'string', 'max:50'],
            'father_birth_date' => [Rule::requiredIf($withFather), 'nullable', 'date', 'before:today'],

            // Mother
            'living_with_mother' => ['nullable', 'boolean'],
            'mother_last_name' => [Rule::requiredIf($withMother), 'nullable', 'string', 'max:255'],
            'mother_first_name' => [Rule::requiredIf($withMother), 'nullable', 'string', 'max:255'],
            'mother_middle_name' => ['nullable', 'string', 'max:255'],
            'mother_birth_date' => [Rule::requiredIf($withMother), 'nullable', 'date', 'before:today'],

            // Siblings
            'living_with_siblings' => ['nullable', 'boolean'],
            'siblings_count' => ['nullable', 'integer', 'max:20', Rule::when($withSiblings, ['required', 'min:1'], ['min:0'])],
            'siblings' => [Rule::requiredIf($withSiblings), 'nullable', 'array'],
            'siblings.*.last_name' => ['required', 'string', 'max:255'],
            'siblings.*.first_name' => ['required', 'string', 'max:255'],
            'siblings.*.middle_name' => ['nullable', 'string', 'max:255'],
            'siblings.*.birth_date' => ['required', 'date', 'before:today'],

            // Spouse
            'living_with_spouse' => ['nullable', 'boolean'],
            'spouse_last_name' => [Rule::requiredIf($withSpouse), 'nullable', 'string', 'max:255'],
            'spouse_first_name' => [Rule::requiredIf($withSpouse), 'nullable', 'string', 'max:255'],
            'spouse_middle_name' => ['nullable', 'string', 'max:255'],
            'spouse_extension_name' => ['nullable', 'string', 'max:50'],
            'spouse_birth_date' => [Rule::requiredIf($withSpouse), 'nullable', 'date', 'before:today'],

            // Children (18+)
            'living_with_children' => ['nullable', 'boolean'],
            'children_count' => ['nullable', 'integer', 'max:20', Rule::when($withChildren, ['required', 'min:1'], ['min:0'])],
            'children' => [Rule::requiredIf($withChildren), 'nullable', 'array'],
            'children.*.last_name' => ['required', 'string', 'max:255'],
            'children.*.first_name' => ['required', 'string', 'max:255'],
            'children.*.middle_name' => ['nullable', 'string', 'max:255'],
            'children.*.birth_date' => ['required', 'date', 'before:today'],

            // Relatives
            'living_with_relatives' => ['nullable', 'boolean'],
            'relatives_count' => ['nullable', 'integer', 'max:20', Rule::when($withRelatives, ['required', 'min:1'], ['min:0'])],
            'relatives' => [Rule::requiredIf($withRelatives), 'nullable', 'array'],
            'relatives.*.last_name' => ['required', 'string', 'max:255'],
            'relatives.*.first_name' => ['required', 'string', 'max:255'],
            'relatives.*.middle_name' => ['nullable', 'string', 'max:255'],
            'relatives.*.birth_date' => ['required', 'date', 'before:today'],
            'relatives.*.relationship' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'father_last_name.required' => "Father's last name is required when living with father.",
            'father_first_name.required' => "Father's first name is required when living with father.",
            'father_birth_date.required' => "Father's birth date is required when living with father.",
            'mother_last_name.required' => "Mother's last name is required when living with mother.",
            'mother_first_name.required' => "Mother's first name is required when living with mother.",
            'mother_birth_date.required' => "Mother's birth date is required when living with mother.",
            'siblings_count.required' => 'Number of siblings is required when living with siblings.',
            'siblings.required' => 'Sibling details are required when living with siblings.',
            'spouse_last_name.required' => "Spouse's last name is required when living with spouse.",
            'spouse_first_name.required' => "Spouse's first name is required when living with spouse.",
            'spouse_birth_date.required' => "Spouse's birth date is required when living with spouse.",
            'children_count.required' => 'Number of children is required when living with children.',
            'children.required' => 'Children details are required when living with children.',
            'relatives_count.required' => 'Number of relatives is required when living with other relatives.',
            'relatives.required' => 'Relative details are required when living with other relatives.',
        ];
    }
}
