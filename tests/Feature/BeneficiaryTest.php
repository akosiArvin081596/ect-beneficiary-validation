<?php

use App\Models\Beneficiary;
use App\Models\User;

function validBeneficiaryData(array $overrides = []): array
{
    return array_merge([
        'timestamp' => '2026-01-15',
        'province' => 'Agusan del Norte',
        'municipality' => 'Butuan City',
        'barangay' => 'Barangay 1',
        'purok' => 'Purok 2',
        'last_name' => 'Santos',
        'first_name' => 'Juan',
        'middle_name' => 'Cruz',
        'extension_name' => null,
        'sex' => 'Male',
        'birth_date' => '1990-06-15',
        'classify_extent_of_damaged_house' => 'Totally Damaged',
        'nhts_pr_classification' => 'Poor',
        'applicable_sector' => [],
        'civil_status' => 'Single',
        'living_with_father' => false,
        'living_with_mother' => false,
        'living_with_siblings' => false,
        'siblings_count' => 0,
        'siblings' => [],
        'living_with_spouse' => false,
        'living_with_children' => false,
        'children_count' => 0,
        'children' => [],
        'living_with_relatives' => false,
        'relatives_count' => 0,
        'relatives' => [],
    ], $overrides);
}

test('guests are redirected to login for beneficiaries index', function () {
    $this->get(route('beneficiaries.index'))
        ->assertRedirect(route('login'));
});

test('guests are redirected to login for beneficiaries create', function () {
    $this->get(route('beneficiaries.create'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit beneficiaries index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('beneficiaries.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Beneficiaries/Index'));
});

test('authenticated users can visit beneficiaries create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('beneficiaries.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Beneficiaries/Create'));
});

test('valid submission stores beneficiary and redirects to index', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('beneficiaries.store'), validBeneficiaryData());

    $response->assertRedirect(route('beneficiaries.index'));
    $this->assertDatabaseHas('beneficiaries', [
        'last_name' => 'Santos',
        'first_name' => 'Juan',
        'municipality' => 'Butuan City',
    ]);
});

test('submission with living_with_father stores father details correctly', function () {
    $user = User::factory()->create();

    $data = validBeneficiaryData([
        'living_with_father' => true,
        'father_last_name' => 'Santos',
        'father_first_name' => 'Pedro',
        'father_middle_name' => 'Cruz',
        'father_extension_name' => null,
        'father_birth_date' => '1960-03-20',
    ]);

    $this->actingAs($user)
        ->post(route('beneficiaries.store'), $data)
        ->assertRedirect(route('beneficiaries.index'));

    $this->assertDatabaseHas('beneficiaries', [
        'father_last_name' => 'Santos',
        'father_first_name' => 'Pedro',
        'father_birth_date' => '1960-03-20',
    ]);
});

test('siblings with missing last_name fails siblings.0.last_name validation', function () {
    $user = User::factory()->create();

    $data = validBeneficiaryData([
        'living_with_siblings' => true,
        'siblings_count' => 1,
        'siblings' => [
            ['last_name' => '', 'first_name' => 'Maria', 'middle_name' => '', 'birth_date' => '1995-01-01'],
        ],
    ]);

    $this->actingAs($user)
        ->post(route('beneficiaries.store'), $data)
        ->assertSessionHasErrors('siblings.0.last_name');
});

test('missing required fields returns validation errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('beneficiaries.store'), [])
        ->assertSessionHasErrors(['last_name', 'first_name', 'province', 'municipality', 'barangay', 'sex', 'birth_date', 'civil_status']);
});

test('factory-created beneficiaries appear on index page', function () {
    $user = User::factory()->create();
    $beneficiaries = Beneficiary::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('beneficiaries.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Beneficiaries/Index')
            ->has('beneficiaries.data', 3)
        );
});
