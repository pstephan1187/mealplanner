<?php

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/hot'));
});

dataset('shoppingListDevices', [
    'mobile' => 'mobile',
    'tablet' => 'tablet',
]);

it('creates shopping lists from meal plans and updates items', function (string $device) {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'name' => 'Grocery Week',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);

    $ingredientA = Ingredient::factory()->create(['name' => 'Onion']);
    $ingredientB = Ingredient::factory()->create(['name' => 'Tomato']);

    $recipe = Recipe::factory()->for($user)->create([
        'name' => 'Salsa Bowl',
        'servings' => 2,
    ]);

    $recipe->ingredients()->attach($ingredientA->id, [
        'quantity' => 1,
        'unit' => 'cup',
        'note' => null,
    ]);

    $recipe->ingredients()->attach($ingredientB->id, [
        'quantity' => 2,
        'unit' => 'cup',
        'note' => null,
    ]);

    MealPlanRecipe::factory()
        ->for($mealPlan)
        ->for($recipe)
        ->create([
            'date' => '2026-01-02',
            'meal_type' => 'Dinner',
            'servings' => 2,
        ]);

    $this->actingAs($user);

    $page = visit("/meal-plans/{$mealPlan->id}");

    $page = $device === 'mobile'
        ? $page->on()->iPhone14Pro()
        : $page->on()->iPadPro();

    $page->click('Create list')
        ->wait(500)
        ->assertPathContains('/shopping-lists/')
        ->assertSee('Onion')
        ->assertSee('Tomato');

    $shoppingList = ShoppingList::query()
        ->where('meal_plan_id', $mealPlan->id)
        ->with(['items.ingredient'])
        ->firstOrFail();

    $items = $shoppingList->items->sortBy('sort_order')->values();

    $firstItem = $items->first();
    $secondItem = $items->skip(1)->first();

    $page->assertSeeIn("@shopping-item-{$firstItem->id}", 'Order 1')
        ->click("@shopping-item-toggle-{$firstItem->id}")
        ->wait(500)
        ->assertAttribute("@shopping-item-toggle-{$firstItem->id}", 'data-state', 'checked')
        ->click("@shopping-item-down-{$firstItem->id}")
        ->wait(500)
        ->assertSeeIn("@shopping-item-{$firstItem->id}", 'Order 2')
        ->assertSeeIn("@shopping-item-{$secondItem->id}", 'Order 1')
        ->assertNoJavascriptErrors();
})->with('shoppingListDevices');
