<?php

use App\Http\Requests\Ingredients\StoreIngredientRequest;
use App\Http\Requests\Ingredients\UpdateIngredientRequest;
use App\Http\Requests\MealPlanRecipes\StoreMealPlanRecipeRequest;
use App\Http\Requests\MealPlanRecipes\UpdateMealPlanRecipeRequest;
use App\Http\Requests\MealPlans\StoreMealPlanRequest;
use App\Http\Requests\MealPlans\UpdateMealPlanRequest;
use App\Http\Requests\Recipes\StoreRecipeRequest;
use App\Http\Requests\Recipes\UpdateRecipeRequest;
use App\Http\Requests\ShoppingListItems\StoreShoppingListItemRequest;
use App\Http\Requests\ShoppingListItems\UpdateShoppingListItemRequest;
use App\Http\Requests\ShoppingLists\StoreShoppingListRequest;
use App\Http\Requests\ShoppingLists\UpdateShoppingListRequest;
use App\Http\Resources\IngredientResource;
use App\Http\Resources\MealPlanRecipeResource;
use App\Http\Resources\MealPlanResource;
use App\Http\Resources\RecipeResource;
use App\Http\Resources\ShoppingListItemResource;
use App\Http\Resources\ShoppingListResource;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;

it('resources omit timestamps', function (string $resourceClass, string $modelClass) {
    $model = $modelClass::factory()->create();

    $payload = $resourceClass::make($model)->toArray(request());

    expect($payload)
        ->not->toHaveKey('created_at')
        ->not->toHaveKey('updated_at');
})->with([
    'recipe' => [RecipeResource::class, Recipe::class],
    'ingredient' => [IngredientResource::class, Ingredient::class],
    'meal plan' => [MealPlanResource::class, MealPlan::class],
    'meal plan recipe' => [MealPlanRecipeResource::class, MealPlanRecipe::class],
    'shopping list' => [ShoppingListResource::class, ShoppingList::class],
    'shopping list item' => [ShoppingListItemResource::class, ShoppingListItem::class],
]);

it('defines form request rules', function (string $requestClass, array $keys) {
    $rules = (new $requestClass)->rules();

    expect($rules)->toHaveKeys($keys);
})->with([
    'store recipe' => [StoreRecipeRequest::class, [
        'name',
        'instructions',
        'servings',
        'flavor_profile',
        'meal_types',
        'meal_types.*',
        'photo',
        'prep_time_minutes',
        'cook_time_minutes',
        'ingredients',
        'ingredients.*.ingredient_id',
        'ingredients.*.quantity',
        'ingredients.*.unit',
        'ingredients.*.note',
    ]],
    'update recipe' => [UpdateRecipeRequest::class, [
        'name',
        'instructions',
        'servings',
        'flavor_profile',
        'meal_types',
        'meal_types.*',
        'photo',
        'prep_time_minutes',
        'cook_time_minutes',
        'ingredients',
        'ingredients.*.ingredient_id',
        'ingredients.*.quantity',
        'ingredients.*.unit',
        'ingredients.*.note',
    ]],
    'store ingredient' => [StoreIngredientRequest::class, ['name']],
    'update ingredient' => [UpdateIngredientRequest::class, ['name']],
    'store meal plan' => [StoreMealPlanRequest::class, ['name', 'start_date', 'end_date']],
    'update meal plan' => [UpdateMealPlanRequest::class, ['name', 'start_date', 'end_date']],
    'store meal plan recipe' => [StoreMealPlanRecipeRequest::class, [
        'meal_plan_id',
        'recipe_id',
        'date',
        'meal_type',
        'servings',
    ]],
    'update meal plan recipe' => [UpdateMealPlanRecipeRequest::class, [
        'meal_plan_id',
        'recipe_id',
        'date',
        'meal_type',
        'servings',
    ]],
    'store shopping list' => [StoreShoppingListRequest::class, ['meal_plan_id']],
    'update shopping list' => [UpdateShoppingListRequest::class, ['meal_plan_id']],
    'store shopping list item' => [StoreShoppingListItemRequest::class, [
        'shopping_list_id',
        'ingredient_id',
        'quantity',
        'unit',
        'is_purchased',
        'sort_order',
    ]],
    'update shopping list item' => [UpdateShoppingListItemRequest::class, [
        'shopping_list_id',
        'ingredient_id',
        'quantity',
        'unit',
        'is_purchased',
        'sort_order',
    ]],
]);
