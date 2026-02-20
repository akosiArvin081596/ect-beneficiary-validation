<?php

use App\Models\Beneficiary;
use App\Models\User;

// ── Access control ──────────────────────────────────────────────────────────

test('guests are redirected to login for data cleansing index', function () {
    $this->get(route('data-cleansing.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users get 403 on data cleansing index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('data-cleansing.index'))
        ->assertForbidden();
});

test('admin users can access data cleansing index', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('data-cleansing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('DataCleansing/Index'));
});

// ── Duplicate detection ─────────────────────────────────────────────────────

test('index shows duplicate groups when duplicates exist', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->count(2)->create([
        'first_name' => 'Juan',
        'last_name' => 'Santos',
        'birth_date' => '1990-06-15',
    ]);

    // Non-duplicate
    Beneficiary::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'birth_date' => '1985-01-01',
    ]);

    $this->actingAs($admin)
        ->get(route('data-cleansing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('DataCleansing/Index')
            ->has('groups', 1)
            ->where('groups.0.records', fn ($records) => count($records) === 2)
        );
});

test('index shows empty groups when no duplicates exist', function () {
    $admin = User::factory()->admin()->create();

    Beneficiary::factory()->create(['first_name' => 'Juan', 'last_name' => 'Santos']);
    Beneficiary::factory()->create(['first_name' => 'Maria', 'last_name' => 'Reyes']);

    $this->actingAs($admin)
        ->get(route('data-cleansing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('DataCleansing/Index')
            ->has('groups', 0)
        );
});

// ── Delete ──────────────────────────────────────────────────────────────────

test('admin can delete a beneficiary record', function () {
    $admin = User::factory()->admin()->create();
    $beneficiary = Beneficiary::factory()->withSiblings(2)->create();

    $this->actingAs($admin)
        ->delete(route('data-cleansing.destroy', $beneficiary))
        ->assertRedirect();

    $this->assertDatabaseMissing('beneficiaries', ['id' => $beneficiary->id]);
    $this->assertDatabaseMissing('beneficiary_siblings', ['beneficiary_id' => $beneficiary->id]);
});

test('non-admin cannot delete via data cleansing', function () {
    $user = User::factory()->create();
    $beneficiary = Beneficiary::factory()->create();

    $this->actingAs($user)
        ->delete(route('data-cleansing.destroy', $beneficiary))
        ->assertForbidden();

    $this->assertDatabaseHas('beneficiaries', ['id' => $beneficiary->id]);
});

// ── Merge ───────────────────────────────────────────────────────────────────

test('admin can merge duplicate records and transfer relations', function () {
    $admin = User::factory()->admin()->create();

    $keep = Beneficiary::factory()->withSiblings(1)->create([
        'first_name' => 'Juan',
        'last_name' => 'Santos',
        'birth_date' => '1990-06-15',
    ]);

    $remove = Beneficiary::factory()->withChildren(2)->withRelatives(1)->create([
        'first_name' => 'Juan',
        'last_name' => 'Santos',
        'birth_date' => '1990-06-15',
    ]);

    $this->actingAs($admin)
        ->post(route('data-cleansing.merge'), [
            'keep_id' => $keep->id,
            'remove_ids' => [$remove->id],
        ])
        ->assertRedirect();

    // Kept record still exists
    $this->assertDatabaseHas('beneficiaries', ['id' => $keep->id]);

    // Removed record is deleted
    $this->assertDatabaseMissing('beneficiaries', ['id' => $remove->id]);

    // Relations were transferred
    $keep->refresh();
    expect($keep->siblings)->toHaveCount(1);
    expect($keep->children)->toHaveCount(2);
    expect($keep->relatives)->toHaveCount(1);
});

test('merge validates keep_id cannot be in remove_ids', function () {
    $admin = User::factory()->admin()->create();
    $beneficiary = Beneficiary::factory()->create();

    $this->actingAs($admin)
        ->post(route('data-cleansing.merge'), [
            'keep_id' => $beneficiary->id,
            'remove_ids' => [$beneficiary->id],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('keep_id');
});

test('merge requires at least one remove_id', function () {
    $admin = User::factory()->admin()->create();
    $beneficiary = Beneficiary::factory()->create();

    $this->actingAs($admin)
        ->post(route('data-cleansing.merge'), [
            'keep_id' => $beneficiary->id,
            'remove_ids' => [],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('remove_ids');
});

test('non-admin cannot merge via data cleansing', function () {
    $user = User::factory()->create();
    $keep = Beneficiary::factory()->create();
    $remove = Beneficiary::factory()->create();

    $this->actingAs($user)
        ->post(route('data-cleansing.merge'), [
            'keep_id' => $keep->id,
            'remove_ids' => [$remove->id],
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('beneficiaries', ['id' => $remove->id]);
});
