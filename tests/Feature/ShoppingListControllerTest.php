<?php

use App\Models\MealPlan;
use App\Models\ShoppingList;
use App\Models\User;

it('updates shopping lists to a new meal plan', function () {
    $user = User::factory()->create();
    $mealPlanA = MealPlan::factory()->for($user)->create();
    $mealPlanB = MealPlan::factory()->for($user)->create();

    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlanA)->create();

    $response = $this->actingAs($user)->patch(route('shopping-lists.update', $shoppingList), [
        'meal_plan_id' => $mealPlanB->id,
    ]);

    $response->assertRedirect(route('shopping-lists.show', $shoppingList));

    expect($shoppingList->refresh()->meal_plan_id)->toBe($mealPlanB->id);
});

it('deletes shopping lists for the owner', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create();

    $response = $this->actingAs($user)->delete(route('shopping-lists.destroy', $shoppingList));

    $response->assertRedirect(route('shopping-lists.index'));

    expect(ShoppingList::query()->whereKey($shoppingList->id)->exists())->toBeFalse();
});

it('prevents updating shopping lists for other users', function () {
    $user = User::factory()->create();
    $shoppingList = ShoppingList::factory()->create();

    $response = $this->actingAs($user)->patch(route('shopping-lists.update', $shoppingList), [
        'display_mode' => 'alphabetical',
    ]);

    $response->assertNotFound();
});

it('prevents deleting shopping lists for other users', function () {
    $user = User::factory()->create();
    $shoppingList = ShoppingList::factory()->create();

    $response = $this->actingAs($user)->delete(route('shopping-lists.destroy', $shoppingList));

    $response->assertNotFound();
});
