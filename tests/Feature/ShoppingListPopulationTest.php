<?php

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use App\Models\RecipeSection;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('populates with no scaling when servings match', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $ingredient = Ingredient::factory()->for($user)->create(['name' => 'Salt']);

    $recipe = Recipe::factory()->for($user)->create(['servings' => 4]);
    $recipe->ingredients()->attach($ingredient->id, [
        'quantity' => 2,
        'unit' => 'tsp',
        'note' => null,
    ]);

    MealPlanRecipe::factory()->for($mealPlan)->for($recipe)->create([
        'servings' => 4,
    ]);

    $response = $this->actingAs($user)->post('/shopping-lists', [
        'meal_plan_id' => $mealPlan->id,
    ]);

    $shoppingList = ShoppingList::firstOrFail();
    $response->assertRedirect(route('shopping-lists.show', $shoppingList));

    $shoppingList->load('items');

    expect($shoppingList->items)->toHaveCount(1);

    $item = $shoppingList->items->first();
    expect($item->ingredient_id)->toBe($ingredient->id);
    expect($item->quantity)->toBe('2.00');
    expect($item->unit)->toBe('tsp');
});

it('includes ingredients from sectioned recipes', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $flour = Ingredient::factory()->for($user)->create(['name' => 'Flour']);
    $sugar = Ingredient::factory()->for($user)->create(['name' => 'Sugar']);

    $recipe = Recipe::factory()->for($user)->create(['servings' => 2]);
    $section = RecipeSection::factory()->for($recipe)->create(['name' => 'Dry Ingredients', 'sort_order' => 0]);

    DB::table('ingredient_recipe')->insert([
        [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $flour->id,
            'recipe_section_id' => $section->id,
            'quantity' => 3,
            'unit' => 'cup',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $sugar->id,
            'recipe_section_id' => $section->id,
            'quantity' => 0.5,
            'unit' => 'cup',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    MealPlanRecipe::factory()->for($mealPlan)->for($recipe)->create([
        'servings' => 4,
    ]);

    $this->actingAs($user)->post('/shopping-lists', [
        'meal_plan_id' => $mealPlan->id,
    ]);

    $shoppingList = ShoppingList::firstOrFail();
    $shoppingList->load('items');

    expect($shoppingList->items)->toHaveCount(2);

    $flourItem = $shoppingList->items->firstWhere('ingredient_id', $flour->id);
    $sugarItem = $shoppingList->items->firstWhere('ingredient_id', $sugar->id);

    // scale = 4/2 = 2x
    expect($flourItem->quantity)->toBe('6.00');
    expect($sugarItem->quantity)->toBe('1.00');
});

it('creates separate items for same ingredient in different units', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $butter = Ingredient::factory()->for($user)->create(['name' => 'Butter']);

    $recipeA = Recipe::factory()->for($user)->create(['servings' => 2]);
    $recipeA->ingredients()->attach($butter->id, [
        'quantity' => 1,
        'unit' => 'cup',
        'note' => null,
    ]);

    $recipeB = Recipe::factory()->for($user)->create(['servings' => 2]);
    $recipeB->ingredients()->attach($butter->id, [
        'quantity' => 2,
        'unit' => 'tbsp',
        'note' => null,
    ]);

    MealPlanRecipe::factory()->for($mealPlan)->for($recipeA)->create(['servings' => 2]);
    MealPlanRecipe::factory()->for($mealPlan)->for($recipeB)->create(['servings' => 2]);

    $this->actingAs($user)->post('/shopping-lists', [
        'meal_plan_id' => $mealPlan->id,
    ]);

    $shoppingList = ShoppingList::firstOrFail();
    $shoppingList->load('items');

    expect($shoppingList->items)->toHaveCount(2);

    $cupItem = $shoppingList->items->firstWhere('unit', 'cup');
    $tbspItem = $shoppingList->items->firstWhere('unit', 'tbsp');

    expect($cupItem->ingredient_id)->toBe($butter->id);
    expect($cupItem->quantity)->toBe('1.00');

    expect($tbspItem->ingredient_id)->toBe($butter->id);
    expect($tbspItem->quantity)->toBe('2.00');
});

it('handles a recipe with no ingredients', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $recipe = Recipe::factory()->for($user)->create(['servings' => 2]);
    // No ingredients attached

    MealPlanRecipe::factory()->for($mealPlan)->for($recipe)->create([
        'servings' => 2,
    ]);

    $response = $this->actingAs($user)->post('/shopping-lists', [
        'meal_plan_id' => $mealPlan->id,
    ]);

    $shoppingList = ShoppingList::firstOrFail();
    $response->assertRedirect(route('shopping-lists.show', $shoppingList));

    $shoppingList->load('items');

    expect($shoppingList->items)->toHaveCount(0);
});

it('handles an empty meal plan with no recipes', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    // No MealPlanRecipes created

    $response = $this->actingAs($user)->post('/shopping-lists', [
        'meal_plan_id' => $mealPlan->id,
    ]);

    $shoppingList = ShoppingList::firstOrFail();
    $response->assertRedirect(route('shopping-lists.show', $shoppingList));

    $shoppingList->load('items');

    expect($shoppingList->items)->toHaveCount(0);
});

it('deduplicates same ingredient across three recipes', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $garlic = Ingredient::factory()->for($user)->create(['name' => 'Garlic']);

    // Recipe A: 2 servings, 3 cloves garlic
    $recipeA = Recipe::factory()->for($user)->create(['servings' => 2]);
    $recipeA->ingredients()->attach($garlic->id, [
        'quantity' => 3,
        'unit' => 'cloves',
        'note' => null,
    ]);

    // Recipe B: 4 servings, 8 cloves garlic
    $recipeB = Recipe::factory()->for($user)->create(['servings' => 4]);
    $recipeB->ingredients()->attach($garlic->id, [
        'quantity' => 8,
        'unit' => 'cloves',
        'note' => null,
    ]);

    // Recipe C: 1 serving, 2 cloves garlic
    $recipeC = Recipe::factory()->for($user)->create(['servings' => 1]);
    $recipeC->ingredients()->attach($garlic->id, [
        'quantity' => 2,
        'unit' => 'cloves',
        'note' => null,
    ]);

    // All at 2 servings
    MealPlanRecipe::factory()->for($mealPlan)->for($recipeA)->create(['servings' => 2]); // scale 1x → 3
    MealPlanRecipe::factory()->for($mealPlan)->for($recipeB)->create(['servings' => 2]); // scale 0.5x → 4
    MealPlanRecipe::factory()->for($mealPlan)->for($recipeC)->create(['servings' => 2]); // scale 2x → 4

    // Expected: 3 + 4 + 4 = 11 cloves

    $this->actingAs($user)->post('/shopping-lists', [
        'meal_plan_id' => $mealPlan->id,
    ]);

    $shoppingList = ShoppingList::firstOrFail();
    $shoppingList->load('items');

    expect($shoppingList->items)->toHaveCount(1);

    $item = $shoppingList->items->first();
    expect($item->ingredient_id)->toBe($garlic->id);
    expect($item->quantity)->toBe('11.00');
    expect($item->unit)->toBe('cloves');
});
