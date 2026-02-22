<?php

use App\Models\Beneficiary;
use App\Models\User;

// ── Access control ──────────────────────────────────────────────────────────

test('guests are redirected to login for deduplication index', function () {
    $this->get(route('deduplication.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users get 403 on deduplication index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('deduplication.index'))
        ->assertForbidden();
});

test('admin users can access deduplication index', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('deduplication.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Deduplication/Index'));
});

// ── Fuzzy detection ─────────────────────────────────────────────────────────

test('detects fuzzy duplicates with similar first names in same municipality', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->create([
        'first_name' => 'Arvin',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'municipality' => 'Butuan City',
    ]);

    Beneficiary::factory()->create([
        'first_name' => 'Arven',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'municipality' => 'Butuan City',
    ]);

    $this->actingAs($admin)
        ->get(route('deduplication.index', ['municipality' => 'Butuan City']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Deduplication/Index')
            ->has('groups', 1)
            ->where('groups.0.records', fn ($records) => count($records) === 2)
        );
});

test('does not group beneficiaries from different municipalities', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->create([
        'first_name' => 'Arvin',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'municipality' => 'Butuan City',
    ]);

    Beneficiary::factory()->create([
        'first_name' => 'Arven',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'municipality' => 'Cabadbaran City',
    ]);

    $this->actingAs($admin)
        ->get(route('deduplication.index', ['municipality' => 'Butuan City']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Deduplication/Index')
            ->has('groups', 0)
        );
});

test('does not group beneficiaries with different last names', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->create([
        'first_name' => 'Arvin',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'municipality' => 'Butuan City',
    ]);

    Beneficiary::factory()->create([
        'first_name' => 'Arven',
        'last_name' => 'Reyes',
        'middle_name' => 'Cruz',
        'municipality' => 'Butuan City',
    ]);

    $this->actingAs($admin)
        ->get(route('deduplication.index', ['municipality' => 'Butuan City']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Deduplication/Index')
            ->has('groups', 0)
        );
});

test('municipality filter is required to show results', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->create([
        'first_name' => 'Arvin',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'municipality' => 'Butuan City',
    ]);

    Beneficiary::factory()->create([
        'first_name' => 'Arven',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'municipality' => 'Butuan City',
    ]);

    $this->actingAs($admin)
        ->get(route('deduplication.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Deduplication/Index')
            ->has('groups', 0)
        );
});

test('allows higher levenshtein threshold when birth dates match', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->create([
        'first_name' => 'Arvin',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'municipality' => 'Butuan City',
        'birth_date' => '1990-06-15',
    ]);

    // Distance of 3 — would not match with default threshold of 2, but matches because birth_date is the same
    Beneficiary::factory()->create([
        'first_name' => 'Arvino',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'municipality' => 'Butuan City',
        'birth_date' => '1990-06-15',
    ]);

    $this->actingAs($admin)
        ->get(route('deduplication.index', ['municipality' => 'Butuan City']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Deduplication/Index')
            ->has('groups', 1)
        );
});

// ── Mark / Unmark ───────────────────────────────────────────────────────────

test('admin can mark a beneficiary as duplicate', function () {
    $admin = User::factory()->admin()->create();
    $beneficiary = Beneficiary::factory()->create();

    $this->actingAs($admin)
        ->patch(route('deduplication.mark', $beneficiary))
        ->assertOk();

    $beneficiary->refresh();
    expect($beneficiary->marked_as_duplicate)->toBeTrue();
});

test('admin can unmark a beneficiary as duplicate', function () {
    $admin = User::factory()->admin()->create();
    $beneficiary = Beneficiary::factory()->create(['marked_as_duplicate' => true]);

    $this->actingAs($admin)
        ->patch(route('deduplication.unmark', $beneficiary))
        ->assertOk();

    $beneficiary->refresh();
    expect($beneficiary->marked_as_duplicate)->toBeFalse();
});

test('non-admin cannot mark a beneficiary as duplicate', function () {
    $user = User::factory()->create();
    $beneficiary = Beneficiary::factory()->create();

    $this->actingAs($user)
        ->patch(route('deduplication.mark', $beneficiary))
        ->assertForbidden();

    $beneficiary->refresh();
    expect($beneficiary->marked_as_duplicate)->toBeFalse();
});

// ── Export ───────────────────────────────────────────────────────────────────

test('export clean list excludes marked duplicates', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->create([
        'first_name' => 'Juan',
        'last_name' => 'Santos',
        'marked_as_duplicate' => false,
    ]);

    Beneficiary::factory()->create([
        'first_name' => 'Juana',
        'last_name' => 'Santos',
        'marked_as_duplicate' => true,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('deduplication.export-clean-list'))
        ->assertOk();

    $content = $response->streamedContent();
    expect($content)->toContain('Juan');
    expect($content)->not->toContain('Juana');
});

test('export clean list respects municipality filter', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->create([
        'first_name' => 'Juan',
        'last_name' => 'Santos',
        'municipality' => 'Butuan City',
        'marked_as_duplicate' => false,
    ]);

    Beneficiary::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'municipality' => 'Cabadbaran City',
        'marked_as_duplicate' => false,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('deduplication.export-clean-list', ['municipality' => 'Butuan City']))
        ->assertOk();

    $content = $response->streamedContent();
    expect($content)->toContain('Juan');
    expect($content)->not->toContain('Maria');
});
