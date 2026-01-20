<?php

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds a realistic dataset', function () {
    $this->seed();

    expect(User::count())->toBeGreaterThan(0);
    expect(Ingredient::count())->toBeGreaterThan(10);
    expect(Recipe::count())->toBeGreaterThan(3);
    expect(MealPlan::count())->toBeGreaterThan(0);
    expect(MealPlanRecipe::count())->toBeGreaterThan(0);
    expect(ShoppingList::count())->toBeGreaterThan(0);
    expect(ShoppingListItem::count())->toBeGreaterThan(0);

    $recipe = Recipe::query()->with('ingredients')->first();
    $mealPlan = MealPlan::query()->with('mealPlanRecipes')->first();

    expect($recipe?->ingredients)->not->toBeEmpty();
    expect($mealPlan?->mealPlanRecipes)->not->toBeEmpty();
});
