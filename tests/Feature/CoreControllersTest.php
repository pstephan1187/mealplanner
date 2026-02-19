<?php

use App\Http\Resources\RecipeResource;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('stores recipes with photos and ingredients', function () {
    Config::set('filesystems.default', 'local');
    Storage::fake('local');
    Storage::fake('public');

    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->for($user)->create();

    $payload = [
        'name' => 'Honey Garlic Salmon',
        'instructions' => 'Bake and glaze until flaky.',
        'servings' => 2,
        'flavor_profile' => 'Sweet',
        'photo' => UploadedFile::fake()->image('salmon.jpg', 1200, 1200),
        'prep_time_minutes' => 10,
        'cook_time_minutes' => 25,
        'ingredients' => [
            [
                'ingredient_id' => $ingredient->id,
                'quantity' => 1.5,
                'unit' => 'tbsp',
                'note' => 'minced',
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/recipes', $payload);

    $recipe = Recipe::firstOrFail();

    $response->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->user_id)->toBe($user->id);
    expect($recipe->ingredients)->toHaveCount(1);
    expect($recipe->photo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($recipe->photo_path);

    $resource = RecipeResource::make($recipe)->resolve();

    expect($resource['photo_url'])->toBe(Storage::disk('public')->url($recipe->photo_path));
});

it('scopes recipes to the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $recipe = Recipe::factory()->for($otherUser)->create();

    $response = $this->actingAs($user)->get("/recipes/{$recipe->id}");

    $response->assertNotFound();
});

it('stores meal plans for the authenticated user', function () {
    $user = User::factory()->create();

    $payload = [
        'name' => 'Healthy Week',
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-07',
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $payload);

    $mealPlan = MealPlan::firstOrFail();

    $response->assertRedirect(route('meal-plans.show', $mealPlan));

    expect($mealPlan->user_id)->toBe($user->id);
});

it('prevents accessing another users meal plan', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create();

    $response = $this->actingAs($user)->get("/meal-plans/{$mealPlan->id}");

    $response->assertNotFound();
});

it('shows meal plan recipes with recipe details', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->for($user)->create();

    MealPlanRecipe::factory()
        ->for($mealPlan)
        ->for($recipe)
        ->create();

    $this->actingAs($user)
        ->get("/meal-plans/{$mealPlan->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('meal-plans/Show')
            ->where('mealPlan.data.id', $mealPlan->id)
        );
});

it('provides recipe options on meal plan show', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    Recipe::factory()->for($user)->count(2)->create();
    Recipe::factory()->create();

    $this->actingAs($user)
        ->get("/meal-plans/{$mealPlan->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('meal-plans/Show')
            ->has('recipes.data', 2)
            ->where('recipes.data.0.user_id', $user->id)
            ->where('recipes.data.1.user_id', $user->id)
        );
});

it('includes shopping list details on meal plan show', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create();

    $this->actingAs($user)
        ->get("/meal-plans/{$mealPlan->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('meal-plans/Show')
            ->where('mealPlan.data.shopping_list.id', $shoppingList->id)
        );
});

it('creates shopping lists for owned meal plans', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $response = $this->actingAs($user)->post('/shopping-lists', [
        'meal_plan_id' => $mealPlan->id,
    ]);

    $shoppingList = ShoppingList::firstOrFail();

    $response->assertRedirect(route('shopping-lists.show', $shoppingList));

    expect($shoppingList->user_id)->toBe($user->id);
});

it('populates shopping lists from meal plan recipes', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'Garlic']);

    $recipeA = Recipe::factory()->for($user)->create(['servings' => 4]);
    $recipeA->ingredients()->attach($ingredient->id, [
        'quantity' => 2,
        'unit' => 'cup',
        'note' => null,
    ]);

    $recipeB = Recipe::factory()->for($user)->create(['servings' => 2]);
    $recipeB->ingredients()->attach($ingredient->id, [
        'quantity' => 1,
        'unit' => 'cup',
        'note' => null,
    ]);

    MealPlanRecipe::factory()->for($mealPlan)->for($recipeA)->create([
        'date' => '2026-01-02',
        'meal_type' => 'Dinner',
        'servings' => 2,
    ]);

    MealPlanRecipe::factory()->for($mealPlan)->for($recipeB)->create([
        'date' => '2026-01-03',
        'meal_type' => 'Lunch',
        'servings' => 4,
    ]);

    $response = $this->actingAs($user)->post('/shopping-lists', [
        'meal_plan_id' => $mealPlan->id,
    ]);

    $shoppingList = ShoppingList::firstOrFail();

    $response->assertRedirect(route('shopping-lists.show', $shoppingList));

    $shoppingList->load('items');

    expect($shoppingList->items)->toHaveCount(1);

    $item = $shoppingList->items->firstOrFail();

    expect($item->ingredient_id)->toBe($ingredient->id);
    expect($item->unit)->toBe('cup');
    expect($item->quantity)->toBe('3.00');
});

it('aggregates fractional ingredient quantities across meal plan recipes', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-07',
    ]);

    $flour = Ingredient::factory()->create(['name' => 'Flour']);
    $butter = Ingredient::factory()->create(['name' => 'Butter']);

    // Recipe A: base 2 servings — 1/2 cup flour, 1/4 cup butter
    $recipeA = Recipe::factory()->for($user)->create(['servings' => 2]);
    $recipeA->ingredients()->attach([
        $flour->id => ['quantity' => 0.5, 'unit' => 'cup', 'note' => null],
        $butter->id => ['quantity' => 0.25, 'unit' => 'cup', 'note' => null],
    ]);

    // Recipe B: base 4 servings — 3/4 cup flour, 1/3 cup butter
    $recipeB = Recipe::factory()->for($user)->create(['servings' => 4]);
    $recipeB->ingredients()->attach([
        $flour->id => ['quantity' => 0.75, 'unit' => 'cup', 'note' => null],
        $butter->id => ['quantity' => 1 / 3, 'unit' => 'cup', 'note' => null],
    ]);

    // Meal plan: Recipe A at 4 servings (scale 2x), Recipe B at 2 servings (scale 0.5x)
    MealPlanRecipe::factory()->for($mealPlan)->for($recipeA)->create([
        'date' => '2026-02-02',
        'meal_type' => 'Dinner',
        'servings' => 4,
    ]);

    MealPlanRecipe::factory()->for($mealPlan)->for($recipeB)->create([
        'date' => '2026-02-03',
        'meal_type' => 'Lunch',
        'servings' => 2,
    ]);

    // Expected flour: (0.5 * 4/2) + (0.75 * 2/4) = 1.0 + 0.375 = 1.375
    // Expected butter: (0.25 * 4/2) + (0.333.. * 2/4) = 0.5 + 0.1666.. ≈ 0.67

    $response = $this->actingAs($user)->post('/shopping-lists', [
        'meal_plan_id' => $mealPlan->id,
    ]);

    $shoppingList = ShoppingList::firstOrFail();
    $response->assertRedirect(route('shopping-lists.show', $shoppingList));

    $shoppingList->load('items');
    $items = $shoppingList->items->sortBy('ingredient_id')->values();

    expect($items)->toHaveCount(2);

    $flourItem = $items->firstWhere('ingredient_id', $flour->id);
    $butterItem = $items->firstWhere('ingredient_id', $butter->id);

    expect($flourItem->unit)->toBe('cup');
    expect($flourItem->quantity)->toBe('1.38');

    expect($butterItem->unit)->toBe('cup');
    expect($butterItem->quantity)->toBe('0.67');
});

it('rejects shopping list creation for other users meal plans', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create();

    $response = $this->actingAs($user)->post('/shopping-lists', [
        'meal_plan_id' => $mealPlan->id,
    ]);

    $response->assertNotFound();
});

it('shows dashboard stats for the authenticated user', function () {
    $user = User::factory()->create();

    Recipe::factory()->for($user)->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    ShoppingList::factory()->for($user)->for($mealPlan)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.recipes', 1)
            ->where('stats.meal_plans', 1)
            ->where('stats.shopping_lists', 1)
            ->has('recentRecipes.data', 1)
            ->has('recentMealPlans.data', 1)
        );
});
