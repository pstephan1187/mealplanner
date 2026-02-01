<?php

namespace App\Http\Controllers;

use App\Http\Resources\MealPlanResource;
use App\Http\Resources\RecipeResource;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\ShoppingList;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $stats = [
            'recipes' => Recipe::query()->currentUser()->count(),
            'meal_plans' => MealPlan::query()->currentUser()->count(),
            'shopping_lists' => ShoppingList::query()->currentUser()->count(),
        ];

        $recentRecipes = Recipe::query()
            ->currentUser()
            ->latest()
            ->limit(3)
            ->get();

        $recentMealPlans = MealPlan::query()
            ->currentUser()
            ->latest()
            ->limit(3)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentRecipes' => RecipeResource::collection($recentRecipes),
            'recentMealPlans' => MealPlanResource::collection($recentMealPlans),
        ]);
    }
}
