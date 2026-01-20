<?php

use App\Models\MealPlan;
use App\Models\User;

it('creates meal plans with a custom name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('meal-plans.store'), [
        'name' => 'My Custom Plan',
        'start_date' => '2026-01-20',
        'end_date' => '2026-01-26',
    ]);

    $mealPlan = MealPlan::firstOrFail();

    $response->assertRedirect(route('meal-plans.show', $mealPlan));
    expect($mealPlan->name)->toBe('My Custom Plan');
});

it('generates a name from date range when no name is provided', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('meal-plans.store'), [
        'name' => '',
        'start_date' => '2026-01-20',
        'end_date' => '2026-01-26',
    ]);

    $mealPlan = MealPlan::firstOrFail();

    $response->assertRedirect(route('meal-plans.show', $mealPlan));
    expect($mealPlan->name)->toBe('January 20 - 26, 2026');
});

it('generates a name for single day meal plans', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('meal-plans.store'), [
        'start_date' => '2026-03-15',
        'end_date' => '2026-03-15',
    ]);

    $mealPlan = MealPlan::firstOrFail();

    expect($mealPlan->name)->toBe('March 15, 2026');
});

it('generates a name for cross-month date ranges', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('meal-plans.store'), [
        'start_date' => '2026-01-28',
        'end_date' => '2026-02-03',
    ]);

    $mealPlan = MealPlan::firstOrFail();

    expect($mealPlan->name)->toBe('January 28 - February 3, 2026');
});

it('updates meal plans for the owner', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'name' => 'Starter Plan',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);

    $response = $this->actingAs($user)->patch(route('meal-plans.update', $mealPlan), [
        'name' => 'Updated Plan',
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-07',
    ]);

    $response->assertRedirect(route('meal-plans.show', $mealPlan));

    $mealPlan->refresh();

    expect($mealPlan->name)->toBe('Updated Plan');
    expect($mealPlan->start_date?->toDateString())->toBe('2026-02-01');
    expect($mealPlan->end_date?->toDateString())->toBe('2026-02-07');
});

it('deletes meal plans for the owner', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('meal-plans.destroy', $mealPlan));

    $response->assertRedirect(route('meal-plans.index'));

    expect(MealPlan::query()->whereKey($mealPlan->id)->exists())->toBeFalse();
});

it('prevents updating meal plans for other users', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create();

    $response = $this->actingAs($user)->patch(route('meal-plans.update', $mealPlan), [
        'name' => 'No access',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);

    $response->assertNotFound();
});

it('prevents deleting meal plans for other users', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create();

    $response = $this->actingAs($user)->delete(route('meal-plans.destroy', $mealPlan));

    $response->assertNotFound();
});
