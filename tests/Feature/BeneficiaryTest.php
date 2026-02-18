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
    Beneficiary::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('beneficiaries.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Beneficiaries/Index')
            ->has('beneficiaries.data', 3)
        );
});

// ── Normalized relations tests ──────────────────────────────────────────────

test('store creates siblings in normalized table', function () {
    $user = User::factory()->create();

    $data = validBeneficiaryData([
        'living_with_siblings' => true,
        'siblings_count' => 2,
        'siblings' => [
            ['last_name' => 'Santos', 'first_name' => 'Maria', 'middle_name' => 'Cruz', 'birth_date' => '1995-01-01'],
            ['last_name' => 'Santos', 'first_name' => 'Pedro', 'middle_name' => null, 'birth_date' => '1998-05-10'],
        ],
    ]);

    $this->actingAs($user)
        ->post(route('beneficiaries.store'), $data)
        ->assertRedirect(route('beneficiaries.index'));

    $beneficiary = Beneficiary::where('last_name', 'Santos')->where('first_name', 'Juan')->first();
    expect($beneficiary->siblings)->toHaveCount(2);
    $this->assertDatabaseHas('beneficiary_siblings', [
        'beneficiary_id' => $beneficiary->id,
        'first_name' => 'Maria',
    ]);
    $this->assertDatabaseHas('beneficiary_siblings', [
        'beneficiary_id' => $beneficiary->id,
        'first_name' => 'Pedro',
    ]);
});

test('store creates children in normalized table', function () {
    $user = User::factory()->create();

    $data = validBeneficiaryData([
        'living_with_children' => true,
        'children_count' => 1,
        'children' => [
            ['last_name' => 'Santos', 'first_name' => 'Ana', 'middle_name' => null, 'birth_date' => '2000-03-15'],
        ],
    ]);

    $this->actingAs($user)
        ->post(route('beneficiaries.store'), $data)
        ->assertRedirect(route('beneficiaries.index'));

    $beneficiary = Beneficiary::where('last_name', 'Santos')->where('first_name', 'Juan')->first();
    expect($beneficiary->children)->toHaveCount(1);
    $this->assertDatabaseHas('beneficiary_children', [
        'beneficiary_id' => $beneficiary->id,
        'first_name' => 'Ana',
    ]);
});

test('store creates relatives in normalized table', function () {
    $user = User::factory()->create();

    $data = validBeneficiaryData([
        'living_with_relatives' => true,
        'relatives_count' => 1,
        'relatives' => [
            ['last_name' => 'Reyes', 'first_name' => 'Carlos', 'middle_name' => null, 'birth_date' => '1970-08-20', 'relationship' => 'Uncle'],
        ],
    ]);

    $this->actingAs($user)
        ->post(route('beneficiaries.store'), $data)
        ->assertRedirect(route('beneficiaries.index'));

    $beneficiary = Beneficiary::where('last_name', 'Santos')->where('first_name', 'Juan')->first();
    expect($beneficiary->relatives)->toHaveCount(1);
    $this->assertDatabaseHas('beneficiary_relatives', [
        'beneficiary_id' => $beneficiary->id,
        'first_name' => 'Carlos',
        'relationship' => 'Uncle',
    ]);
});

test('offline sync creates beneficiary with relations', function () {
    $user = User::factory()->create();

    $data = validBeneficiaryData([
        'living_with_siblings' => true,
        'siblings_count' => 1,
        'siblings' => [
            ['last_name' => 'Santos', 'first_name' => 'Lily', 'middle_name' => null, 'birth_date' => '1992-02-14'],
        ],
    ]);

    $this->actingAs($user)
        ->postJson(route('beneficiaries.offline-sync'), $data)
        ->assertCreated()
        ->assertJson(['synced' => true]);

    $beneficiary = Beneficiary::where('last_name', 'Santos')->where('first_name', 'Juan')->first();
    expect($beneficiary->siblings)->toHaveCount(1);
    $this->assertDatabaseHas('beneficiary_siblings', [
        'beneficiary_id' => $beneficiary->id,
        'first_name' => 'Lily',
    ]);
});

// ── Search tests ────────────────────────────────────────────────────────────

test('search filters beneficiaries by last name', function () {
    $user = User::factory()->create();
    Beneficiary::factory()->create(['last_name' => 'Dela Cruz']);
    Beneficiary::factory()->create(['last_name' => 'Reyes']);

    $this->actingAs($user)
        ->get(route('beneficiaries.index', ['search' => 'Dela Cruz']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Beneficiaries/Index')
            ->has('beneficiaries.data', 1)
        );
});

test('search filters beneficiaries by municipality', function () {
    $user = User::factory()->create();
    Beneficiary::factory()->create(['municipality' => 'Butuan City']);
    Beneficiary::factory()->create(['municipality' => 'Cabadbaran']);

    $this->actingAs($user)
        ->get(route('beneficiaries.index', ['search' => 'Butuan']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Beneficiaries/Index')
            ->has('beneficiaries.data', 1)
        );
});

test('empty search returns all beneficiaries', function () {
    $user = User::factory()->create();
    Beneficiary::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('beneficiaries.index', ['search' => '']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Beneficiaries/Index')
            ->has('beneficiaries.data', 3)
        );
});

test('index passes filters prop', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('beneficiaries.index', ['search' => 'test']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Beneficiaries/Index')
            ->where('filters.search', 'test')
        );
});
