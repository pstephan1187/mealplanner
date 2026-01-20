<?php

namespace Database\Seeders;

use App\Models\MealPlanRecipe;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShoppingListItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->where('email', config('seeder.user.email'))->first();

        if (! $user) {
            return;
        }

        $shoppingLists = ShoppingList::query()
            ->where('user_id', $user->id)
            ->with('mealPlan')
            ->get();

        $mealPlanIds = $shoppingLists
            ->pluck('meal_plan_id')
            ->filter()
            ->unique()
            ->values();

        $mealPlanRecipesByPlan = MealPlanRecipe::query()
            ->whereIn('meal_plan_id', $mealPlanIds)
            ->with('recipe.ingredients')
            ->get()
            ->groupBy('meal_plan_id');

        foreach ($shoppingLists as $shoppingList) {
            if (! $shoppingList->mealPlan) {
                continue;
            }

            ShoppingListItem::query()
                ->where('shopping_list_id', $shoppingList->id)
                ->delete();

            $mealPlanRecipes = $mealPlanRecipesByPlan->get($shoppingList->meal_plan_id, collect());
            $items = [];

            foreach ($mealPlanRecipes as $mealPlanRecipe) {
                $recipe = $mealPlanRecipe->recipe;

                if (! $recipe) {
                    continue;
                }

                $recipeServings = $recipe->servings ?: 1;
                $scale = $mealPlanRecipe->servings / $recipeServings;

                foreach ($recipe->ingredients as $ingredient) {
                    $pivot = $ingredient->pivot;

                    if (! $pivot) {
                        continue;
                    }

                    $key = $ingredient->id.'|'.$pivot->unit;
                    $quantity = (float) $pivot->quantity * $scale;

                    if (! isset($items[$key])) {
                        $items[$key] = [
                            'ingredient_id' => $ingredient->id,
                            'unit' => $pivot->unit,
                            'quantity' => 0,
                        ];
                    }

                    $items[$key]['quantity'] += $quantity;
                }
            }

            $sortOrder = 1;
            foreach ($items as $item) {
                ShoppingListItem::updateOrCreate(
                    [
                        'shopping_list_id' => $shoppingList->id,
                        'ingredient_id' => $item['ingredient_id'],
                        'unit' => $item['unit'],
                    ],
                    [
                        'quantity' => round($item['quantity'], 2),
                        'is_purchased' => false,
                        'sort_order' => $sortOrder,
                    ]
                );

                $sortOrder++;
            }
        }
    }
}
