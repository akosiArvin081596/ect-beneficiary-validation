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
