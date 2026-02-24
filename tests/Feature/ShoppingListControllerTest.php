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

it('renders the print page for the owner', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create([
        'display_mode' => 'alphabetical',
    ]);

    $response = $this->actingAs($user)->get(route('shopping-lists.print', $shoppingList));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('shopping-lists/Print')
        ->has('shoppingList.data')
        ->where('displayMode', 'alphabetical')
    );
});

it('uses the query mode parameter for print page', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create([
        'display_mode' => 'manual',
    ]);

    $response = $this->actingAs($user)->get(route('shopping-lists.print', [
        'shopping_list' => $shoppingList,
        'mode' => 'store',
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('displayMode', 'store')
    );
});

it('prevents viewing the print page for other users', function () {
    $user = User::factory()->create();
    $shoppingList = ShoppingList::factory()->create();

    $response = $this->actingAs($user)->get(route('shopping-lists.print', $shoppingList));

    $response->assertNotFound();
});
