<?php

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\User;

it('stores shopping list items', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create();
    $ingredient = Ingredient::factory()->create();

    $response = $this->actingAs($user)->post(route('shopping-list-items.store'), [
        'shopping_list_id' => $shoppingList->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.25,
        'unit' => 'cup',
        'is_purchased' => false,
    ]);

    $item = ShoppingListItem::firstOrFail();

    $response->assertRedirect(route('shopping-list-items.show', $item));

    expect($item->shopping_list_id)->toBe($shoppingList->id);
    expect($item->ingredient_id)->toBe($ingredient->id);
    expect($item->quantity)->toBe('1.25');
    expect($item->unit)->toBe('cup');
});

it('updates shopping list items', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create();
    $ingredientA = Ingredient::factory()->create();
    $ingredientB = Ingredient::factory()->create();

    $item = ShoppingListItem::factory()
        ->for($shoppingList)
        ->for($ingredientA)
        ->create([
            'quantity' => 1,
            'unit' => 'cup',
            'is_purchased' => false,
        ]);

    $response = $this->actingAs($user)->patch(route('shopping-list-items.update', $item), [
        'shopping_list_id' => $shoppingList->id,
        'ingredient_id' => $ingredientB->id,
        'quantity' => 2,
        'unit' => 'tbsp',
        'is_purchased' => true,
        'sort_order' => 2,
    ]);

    $response->assertRedirect();

    $item->refresh();

    expect($item->ingredient_id)->toBe($ingredientB->id);
    expect($item->quantity)->toBe('2.00');
    expect($item->unit)->toBe('tbsp');
    expect($item->is_purchased)->toBeTrue();
    expect($item->sort_order)->toBe(2);
});

it('deletes shopping list items', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create();
    $item = ShoppingListItem::factory()->for($shoppingList)->create();

    $response = $this->actingAs($user)->delete(route('shopping-list-items.destroy', $item));

    $response->assertRedirect(route('shopping-list-items.index'));

    expect(ShoppingListItem::query()->whereKey($item->id)->exists())->toBeFalse();
});

it('rejects storing items for other users lists', function () {
    $user = User::factory()->create();
    $shoppingList = ShoppingList::factory()->create();
    $ingredient = Ingredient::factory()->create();

    $response = $this->actingAs($user)->post(route('shopping-list-items.store'), [
        'shopping_list_id' => $shoppingList->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1,
        'unit' => 'cup',
    ]);

    $response->assertNotFound();
});

it('prevents updating items for other users', function () {
    $user = User::factory()->create();
    $item = ShoppingListItem::factory()->create();

    $response = $this->actingAs($user)->patch(route('shopping-list-items.update', $item), [
        'quantity' => 3,
    ]);

    $response->assertNotFound();
});
