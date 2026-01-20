<?php

use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use App\Models\User;

it('replaces meals on a meal plan', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);

    $recipeA = Recipe::factory()->for($user)->create();
    $recipeB = Recipe::factory()->for($user)->create();

    $mealPlanRecipe = MealPlanRecipe::factory()
        ->for($mealPlan)
        ->for($recipeA)
        ->create([
            'date' => '2026-01-02',
            'meal_type' => 'Dinner',
            'servings' => 2,
        ]);

    $response = $this->actingAs($user)->patch(route('meal-plan-recipes.update', $mealPlanRecipe), [
        'recipe_id' => $recipeB->id,
        'servings' => 3,
    ]);

    $response->assertRedirect(route('meal-plans.show', $mealPlan));

    $mealPlanRecipe->refresh();

    expect($mealPlanRecipe->recipe_id)->toBe($recipeB->id);
    expect($mealPlanRecipe->servings)->toBe(3);
});

it('rejects storing meal plan recipes for other users meal plans', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);
    $recipe = Recipe::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('meal-plan-recipes.store'), [
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2026-01-02',
        'meal_type' => 'Lunch',
        'servings' => 2,
    ]);

    $response->assertSessionHasErrors('meal_plan_id');
});

it('prevents updating meal plan recipes for other users', function () {
    $user = User::factory()->create();
    $mealPlanRecipe = MealPlanRecipe::factory()->create();

    $response = $this->actingAs($user)->patch(route('meal-plan-recipes.update', $mealPlanRecipe), [
        'servings' => 4,
    ]);

    $response->assertNotFound();
});

it('deletes meal plan recipes for the owner', function () {
    $user = User::factory()->create();
    $mealPlanRecipe = MealPlanRecipe::factory()
        ->for(MealPlan::factory()->for($user))
        ->for(Recipe::factory()->for($user))
        ->create();

    $response = $this->actingAs($user)->delete(route('meal-plan-recipes.destroy', $mealPlanRecipe));

    $response->assertRedirect(route('meal-plan-recipes.index'));

    expect(MealPlanRecipe::query()->whereKey($mealPlanRecipe->id)->exists())->toBeFalse();
});
