<?php

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates shopping list display mode', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()
        ->for($user)
        ->for($mealPlan)
        ->create(['display_mode' => 'manual']);

    $response = $this->actingAs($user)->patch(
        route('shopping-lists.update', $shoppingList),
        ['display_mode' => 'alphabetical']
    );

    $response->assertRedirect(route('shopping-lists.show', $shoppingList));

    expect($shoppingList->refresh()->display_mode)->toBe('alphabetical');
});

it('updates shopping list display mode to store', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()
        ->for($user)
        ->for($mealPlan)
        ->create(['display_mode' => 'manual']);

    $response = $this->actingAs($user)->patch(
        route('shopping-lists.update', $shoppingList),
        ['display_mode' => 'store']
    );

    $response->assertRedirect(route('shopping-lists.show', $shoppingList));

    expect($shoppingList->refresh()->display_mode)->toBe('store');
});

it('toggles shopping list items as purchased', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create();
    $ingredient = Ingredient::factory()->create();
    $item = ShoppingListItem::factory()
        ->for($shoppingList)
        ->for($ingredient)
        ->create(['is_purchased' => false]);

    $response = $this->actingAs($user)->patch(
        route('shopping-list-items.update', $item),
        ['is_purchased' => true]
    );

    $response->assertRedirect(route('shopping-list-items.show', $item));

    expect($item->refresh()->is_purchased)->toBeTrue();
});

it('updates shopping list item order', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create();

    $ingredientA = Ingredient::factory()->create();
    $ingredientB = Ingredient::factory()->create();

    $first = ShoppingListItem::factory()
        ->for($shoppingList)
        ->for($ingredientA)
        ->create(['sort_order' => 1]);
    $second = ShoppingListItem::factory()
        ->for($shoppingList)
        ->for($ingredientB)
        ->create(['sort_order' => 2]);

    $response = $this->actingAs($user)->patch(
        route('shopping-lists.items.order', $shoppingList),
        [
            'items' => [
                ['id' => $first->id, 'sort_order' => 2],
                ['id' => $second->id, 'sort_order' => 1],
            ],
        ]
    );

    $response->assertRedirect();

    expect($first->refresh()->sort_order)->toBe(2);
    expect($second->refresh()->sort_order)->toBe(1);
});
