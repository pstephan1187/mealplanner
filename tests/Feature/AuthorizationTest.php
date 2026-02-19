<?php

use App\Models\GroceryStore;
use App\Models\GroceryStoreSection;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\User;

describe('IngredientController', function () {
    it('prevents showing ingredients for other users', function () {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create();

        $this->actingAs($user)
            ->get("/ingredients/{$ingredient->id}")
            ->assertNotFound();
    });

    it('prevents editing ingredients for other users', function () {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create();

        $this->actingAs($user)
            ->get("/ingredients/{$ingredient->id}/edit")
            ->assertNotFound();
    });

    it('prevents updating ingredients for other users', function () {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create();

        $this->actingAs($user)
            ->patch("/ingredients/{$ingredient->id}", ['name' => 'Stolen'])
            ->assertNotFound();
    });

    it('prevents deleting ingredients for other users', function () {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create();

        $this->actingAs($user)
            ->delete("/ingredients/{$ingredient->id}")
            ->assertNotFound();
    });
});

describe('RecipeController', function () {
    it('prevents showing recipes for other users', function () {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create();

        $this->actingAs($user)
            ->get("/recipes/{$recipe->id}")
            ->assertNotFound();
    });
});

describe('MealPlanController', function () {
    it('prevents showing meal plans for other users', function () {
        $user = User::factory()->create();
        $mealPlan = MealPlan::factory()->create();

        $this->actingAs($user)
            ->get("/meal-plans/{$mealPlan->id}")
            ->assertNotFound();
    });
});

describe('ShoppingListController', function () {
    it('prevents showing shopping lists for other users', function () {
        $user = User::factory()->create();
        $shoppingList = ShoppingList::factory()->create();

        $this->actingAs($user)
            ->get("/shopping-lists/{$shoppingList->id}")
            ->assertNotFound();
    });

    it('prevents reordering items on other users shopping lists', function () {
        $user = User::factory()->create();
        $shoppingList = ShoppingList::factory()->create();
        $item = ShoppingListItem::factory()->for($shoppingList)->create();

        $this->actingAs($user)
            ->patchJson("/shopping-lists/{$shoppingList->id}/items/order", [
                'items' => [
                    ['id' => $item->id, 'sort_order' => 1],
                ],
            ])
            ->assertNotFound();
    });
});

describe('GroceryStoreController', function () {
    it('prevents editing grocery stores for other users', function () {
        $user = User::factory()->create();
        $groceryStore = GroceryStore::factory()->create();

        $this->actingAs($user)
            ->get("/grocery-stores/{$groceryStore->id}/edit")
            ->assertNotFound();
    });
});

describe('GroceryStoreSectionController', function () {
    it('prevents updating sections on other users stores', function () {
        $user = User::factory()->create();
        $store = GroceryStore::factory()->create();
        $section = GroceryStoreSection::factory()->for($store)->create();

        $this->actingAs($user)
            ->patch("/grocery-stores/{$store->id}/sections/{$section->id}", ['name' => 'Stolen'])
            ->assertNotFound();
    });
});

describe('MealPlanRecipeController', function () {
    it('prevents showing meal plan recipes for other users', function () {
        $user = User::factory()->create();
        $mealPlanRecipe = MealPlanRecipe::factory()->create();

        $this->actingAs($user)
            ->get("/meal-plan-recipes/{$mealPlanRecipe->id}")
            ->assertNotFound();
    });

    it('prevents deleting meal plan recipes for other users', function () {
        $user = User::factory()->create();
        $mealPlanRecipe = MealPlanRecipe::factory()->create();

        $this->actingAs($user)
            ->delete("/meal-plan-recipes/{$mealPlanRecipe->id}")
            ->assertNotFound();
    });
});

describe('ShoppingListItemController', function () {
    it('prevents showing shopping list items for other users', function () {
        $user = User::factory()->create();
        $item = ShoppingListItem::factory()->create();

        $this->actingAs($user)
            ->get("/shopping-list-items/{$item->id}")
            ->assertNotFound();
    });

    it('prevents deleting shopping list items for other users', function () {
        $user = User::factory()->create();
        $item = ShoppingListItem::factory()->create();

        $this->actingAs($user)
            ->delete("/shopping-list-items/{$item->id}")
            ->assertNotFound();
    });
});
