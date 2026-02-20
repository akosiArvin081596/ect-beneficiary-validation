<?php

use App\Models\Beneficiary;
use App\Models\User;

// ── Access control ──────────────────────────────────────────────────────────

test('guests are redirected to login for masterlist index', function () {
    $this->get(route('masterlist.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users get 403 on masterlist index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('masterlist.index'))
        ->assertForbidden();
});

test('admin users can access masterlist index', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('masterlist.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Masterlist/Index'));
});

test('non-admin users get 403 on masterlist show', function () {
    $user = User::factory()->create();
    $beneficiary = Beneficiary::factory()->create();

    $this->actingAs($user)
        ->get(route('masterlist.show', $beneficiary))
        ->assertForbidden();
});

test('non-admin users get 403 on masterlist export', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('masterlist.export-csv'))
        ->assertForbidden();
});

// ── Index ───────────────────────────────────────────────────────────────────

test('index shows all beneficiaries with relation counts', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->withSiblings(2)->withChildren(3)->create();
    Beneficiary::factory()->withRelatives(1)->create();

    $this->actingAs($admin)
        ->get(route('masterlist.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Masterlist/Index')
            ->has('beneficiaries.data', 2)
            ->has('beneficiaries.data.0.siblings_count')
            ->has('beneficiaries.data.0.children_count')
            ->has('beneficiaries.data.0.relatives_count')
        );
});

test('index supports search filtering', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->create(['last_name' => 'Santos']);
    Beneficiary::factory()->create(['last_name' => 'Reyes']);

    $this->actingAs($admin)
        ->get(route('masterlist.index', ['search' => 'Santos']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Masterlist/Index')
            ->has('beneficiaries.data', 1)
            ->where('filters.search', 'Santos')
        );
});

// ── Show ────────────────────────────────────────────────────────────────────

test('show displays full beneficiary with relations', function () {
    $admin = User::factory()->admin()->create();

    $beneficiary = Beneficiary::factory()
        ->withSiblings(2)
        ->withChildren(1)
        ->withRelatives(1)
        ->create();

    $this->actingAs($admin)
        ->get(route('masterlist.show', $beneficiary))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Masterlist/Show')
            ->has('beneficiary.siblings', 2)
            ->has('beneficiary.children', 1)
            ->has('beneficiary.relatives', 1)
        );
});

test('show includes locations data', function () {
    $admin = User::factory()->admin()->create();
    $beneficiary = Beneficiary::factory()->create();

    $this->actingAs($admin)
        ->get(route('masterlist.show', $beneficiary))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Masterlist/Show')
            ->has('locations')
        );
});

// ── Update ─────────────────────────────────────────────────────────────────

test('admin can update beneficiary data', function () {
    $admin = User::factory()->admin()->create();

    $beneficiary = Beneficiary::factory()->withSiblings(1)->create([
        'last_name' => 'Santos',
        'first_name' => 'Juan',
        'sex' => 'Male',
    ]);

    $this->actingAs($admin)
        ->put(route('masterlist.update', $beneficiary), [
            'last_name' => 'Reyes',
            'first_name' => 'Maria',
            'middle_name' => '',
            'extension_name' => '',
            'sex' => 'Female',
            'birth_date' => '1990-01-15',
            'civil_status' => 'Single',
            'province' => $beneficiary->province,
            'municipality' => $beneficiary->municipality,
            'barangay' => $beneficiary->barangay,
            'purok' => $beneficiary->purok,
            'classify_extent_of_damaged_house' => $beneficiary->classify_extent_of_damaged_house,
            'nhts_pr_classification' => '',
            'applicable_sector' => [],
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
        ])
        ->assertRedirect(route('masterlist.show', $beneficiary));

    $beneficiary->refresh();
    expect($beneficiary->last_name)->toBe('Reyes');
    expect($beneficiary->first_name)->toBe('Maria');
    expect($beneficiary->sex)->toBe('Female');
    expect($beneficiary->siblings)->toHaveCount(0);
});

test('updated data persists correctly with relations', function () {
    $admin = User::factory()->admin()->create();

    $beneficiary = Beneficiary::factory()->create([
        'living_with_siblings' => false,
        'living_with_children' => false,
        'living_with_relatives' => false,
    ]);

    $this->actingAs($admin)
        ->put(route('masterlist.update', $beneficiary), [
            'last_name' => $beneficiary->last_name,
            'first_name' => $beneficiary->first_name,
            'middle_name' => '',
            'extension_name' => '',
            'sex' => $beneficiary->sex,
            'birth_date' => $beneficiary->birth_date->format('Y-m-d'),
            'civil_status' => $beneficiary->civil_status,
            'province' => $beneficiary->province,
            'municipality' => $beneficiary->municipality,
            'barangay' => $beneficiary->barangay,
            'purok' => $beneficiary->purok,
            'classify_extent_of_damaged_house' => $beneficiary->classify_extent_of_damaged_house,
            'nhts_pr_classification' => '',
            'applicable_sector' => ['4Ps', 'PWD'],
            'living_with_father' => false,
            'living_with_mother' => false,
            'living_with_siblings' => true,
            'siblings_count' => 1,
            'siblings' => [
                ['last_name' => 'Cruz', 'first_name' => 'Ana', 'middle_name' => '', 'birth_date' => '1995-06-01'],
            ],
            'living_with_spouse' => false,
            'living_with_children' => true,
            'children_count' => 1,
            'children' => [
                ['last_name' => 'Cruz', 'first_name' => 'Pedro', 'middle_name' => '', 'birth_date' => '2000-03-10'],
            ],
            'living_with_relatives' => false,
            'relatives_count' => 0,
            'relatives' => [],
        ])
        ->assertRedirect(route('masterlist.show', $beneficiary));

    $beneficiary->refresh()->load(['siblings', 'children', 'relatives']);
    expect($beneficiary->applicable_sector)->toBe(['4Ps', 'PWD']);
    expect($beneficiary->siblings)->toHaveCount(1);
    expect($beneficiary->siblings->first()->first_name)->toBe('Ana');
    expect($beneficiary->children)->toHaveCount(1);
    expect($beneficiary->children->first()->first_name)->toBe('Pedro');
    expect($beneficiary->relatives)->toHaveCount(0);
});

test('non-admin users get 403 on masterlist update', function () {
    $user = User::factory()->create();
    $beneficiary = Beneficiary::factory()->create();

    $this->actingAs($user)
        ->put(route('masterlist.update', $beneficiary), [])
        ->assertForbidden();
});

// ── CSV Export ──────────────────────────────────────────────────────────────

test('csv export returns proper content type and data', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->withSiblings(1)->create([
        'last_name' => 'Santos',
        'first_name' => 'Juan',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('masterlist.export-csv'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $response->assertDownload();
});

test('csv export respects search filter', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->create(['last_name' => 'Santos']);
    Beneficiary::factory()->create(['last_name' => 'Reyes']);

    $response = $this->actingAs($admin)
        ->get(route('masterlist.export-csv', ['search' => 'Santos']));

    $response->assertOk();

    $content = $response->streamedContent();
    expect($content)->toContain('Santos');
    expect($content)->not->toContain('Reyes');
});
