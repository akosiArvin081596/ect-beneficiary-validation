<?php

use App\Models\Beneficiary;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can view dashboard with all stats props', function () {
    $user = User::factory()->create();

    Beneficiary::factory()->create([
        'classify_extent_of_damaged_house' => 'Totally Damaged (Severely)',
        'nhts_pr_classification' => 'Poor',
        'municipality' => 'Butuan City',
    ]);

    Beneficiary::factory()->create([
        'classify_extent_of_damaged_house' => 'Partially Damaged (Slightly)',
        'nhts_pr_classification' => 'Near Poor',
        'municipality' => 'Butuan City',
    ]);

    Beneficiary::factory()->create([
        'classify_extent_of_damaged_house' => 'Totally Damaged (Severely)',
        'nhts_pr_classification' => 'Not Poor',
        'municipality' => 'Cabadbaran City',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('total_beneficiaries')
            ->has('totally_damaged')
            ->has('partially_damaged')
            ->has('nhts_poor')
            ->has('nhts_near_poor')
            ->has('nhts_not_poor')
            ->has('by_municipality')
            ->has('recent_beneficiaries')
        );
});

test('stats counts match database state', function () {
    $user = User::factory()->create();

    Beneficiary::factory()->count(2)->create([
        'classify_extent_of_damaged_house' => 'Totally Damaged (Severely)',
        'nhts_pr_classification' => 'Poor',
    ]);

    Beneficiary::factory()->count(3)->create([
        'classify_extent_of_damaged_house' => 'Partially Damaged (Slightly)',
        'nhts_pr_classification' => 'Near Poor',
    ]);

    Beneficiary::factory()->create([
        'classify_extent_of_damaged_house' => 'Totally Damaged (Severely)',
        'nhts_pr_classification' => 'Not Poor',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('total_beneficiaries', 6)
            ->where('totally_damaged', 3)
            ->where('partially_damaged', 3)
            ->where('nhts_poor', 2)
            ->where('nhts_near_poor', 3)
            ->where('nhts_not_poor', 1)
            ->has('by_municipality')
            ->has('recent_beneficiaries', 6)
        );
});
