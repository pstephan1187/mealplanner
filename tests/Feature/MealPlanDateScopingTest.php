<?php

use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

it('defaults meal plan create dates to today and one week from today', function () {
    Date::setTestNow('2026-01-14 12:00:00');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/meal-plans/create')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('meal-plans/Create')
            ->where('defaultStartDate', '2026-01-14')
            ->where('defaultEndDate', '2026-01-21')
        );
});

it('provides meal plan dates as YYYY-MM-DD strings for the meal plan recipe date picker', function () {
    $user = User::factory()->create();

    MealPlan::factory()->for($user)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);

    $this->actingAs($user)
        ->get('/meal-plan-recipes/create')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('meal-plan-recipes/Create')
            ->has('mealPlans.data', 1)
            ->where('mealPlans.data.0.start_date', '2026-01-01')
            ->where('mealPlans.data.0.end_date', '2026-01-07')
        );
});

it('rejects meal plan recipes outside the meal plan date range', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);

    $recipe = Recipe::factory()->for($user)->create();

    $this->actingAs($user)
        ->post('/meal-plan-recipes', [
            'meal_plan_id' => $mealPlan->id,
            'recipe_id' => $recipe->id,
            'date' => '2026-01-08',
            'meal_type' => 'Dinner',
            'servings' => 2,
        ])
        ->assertSessionHasErrors('date');
});

it('allows meal plan recipes within the meal plan date range', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);

    $recipe = Recipe::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->post('/meal-plan-recipes', [
            'meal_plan_id' => $mealPlan->id,
            'recipe_id' => $recipe->id,
            'date' => '2026-01-07',
            'meal_type' => 'Dinner',
            'servings' => 2,
        ]);

    $mealPlanRecipe = MealPlanRecipe::firstOrFail();

    $response->assertRedirect(route('meal-plans.show', $mealPlan));
});

it('rejects updating a meal plan recipe date outside the meal plan date range', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);

    $recipe = Recipe::factory()->for($user)->create();

    $mealPlanRecipe = MealPlanRecipe::factory()
        ->for($mealPlan)
        ->for($recipe)
        ->create([
            'date' => '2026-01-02',
            'meal_type' => 'Dinner',
            'servings' => 2,
        ]);

    $this->actingAs($user)
        ->patch(route('meal-plan-recipes.update', $mealPlanRecipe), [
            'date' => '2026-01-08',
        ])
        ->assertSessionHasErrors('date');
});
