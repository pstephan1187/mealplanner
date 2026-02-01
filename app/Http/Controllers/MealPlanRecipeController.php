<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOwnership;
use App\Http\Requests\MealPlanRecipes\StoreMealPlanRecipeRequest;
use App\Http\Requests\MealPlanRecipes\UpdateMealPlanRecipeRequest;
use App\Http\Resources\MealPlanRecipeResource;
use App\Http\Resources\MealPlanResource;
use App\Http\Resources\RecipeResource;
use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MealPlanRecipeController extends Controller
{
    use EnsuresOwnership;

    public function index(Request $request): InertiaResponse
    {
        $mealPlanRecipes = MealPlanRecipe::query()
            ->whereHas('mealPlan', function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id);
            })
            ->with('recipe')
            ->orderBy('date')
            ->paginate();

        return Inertia::render('meal-plan-recipes/Index', [
            'mealPlanRecipes' => MealPlanRecipeResource::collection($mealPlanRecipes),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $mealPlans = MealPlan::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('start_date')
            ->get();

        $recipes = Recipe::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('meal-plan-recipes/Create', [
            'mealPlans' => MealPlanResource::collection($mealPlans),
            'recipes' => RecipeResource::collection($recipes),
        ]);
    }

    public function store(StoreMealPlanRecipeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $mealPlan = $this->resolveMealPlan($request, $data['meal_plan_id']);
        $recipe = $this->resolveRecipe($request, $data['recipe_id']);

        $mealPlan->mealPlanRecipes()->create([
            'recipe_id' => $recipe->id,
            'date' => $data['date'],
            'meal_type' => $data['meal_type'],
            'servings' => $data['servings'],
        ]);

        return redirect()->route('meal-plans.show', $mealPlan);
    }

    public function show(Request $request, MealPlanRecipe $mealPlanRecipe): InertiaResponse
    {
        $this->ensureOwnership($request, $mealPlanRecipe, throughRelationship: 'mealPlan');

        return Inertia::render('meal-plan-recipes/Show', [
            'mealPlanRecipe' => MealPlanRecipeResource::make($mealPlanRecipe->load('recipe')),
        ]);
    }

    public function edit(Request $request, MealPlanRecipe $mealPlanRecipe): InertiaResponse
    {
        $this->ensureOwnership($request, $mealPlanRecipe, throughRelationship: 'mealPlan');

        $mealPlans = MealPlan::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('start_date')
            ->get();

        $recipes = Recipe::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('meal-plan-recipes/Edit', [
            'mealPlanRecipe' => MealPlanRecipeResource::make($mealPlanRecipe->load('recipe')),
            'mealPlans' => MealPlanResource::collection($mealPlans),
            'recipes' => RecipeResource::collection($recipes),
        ]);
    }

    public function update(
        UpdateMealPlanRecipeRequest $request,
        MealPlanRecipe $mealPlanRecipe
    ): RedirectResponse {
        $this->ensureOwnership($request, $mealPlanRecipe, throughRelationship: 'mealPlan');

        $data = $request->validated();

        if (array_key_exists('meal_plan_id', $data)) {
            $mealPlan = $this->resolveMealPlan($request, $data['meal_plan_id']);
            $mealPlanRecipe->meal_plan_id = $mealPlan->id;
        }

        if (array_key_exists('recipe_id', $data)) {
            $recipe = $this->resolveRecipe($request, $data['recipe_id']);
            $mealPlanRecipe->recipe_id = $recipe->id;
        }

        $mealPlanRecipe->fill(Arr::except($data, ['meal_plan_id', 'recipe_id']));
        $mealPlanRecipe->save();

        $mealPlanRecipe->load('mealPlan');

        return redirect()->route('meal-plans.show', $mealPlanRecipe->mealPlan);
    }

    public function destroy(Request $request, MealPlanRecipe $mealPlanRecipe): RedirectResponse
    {
        $this->ensureOwnership($request, $mealPlanRecipe, throughRelationship: 'mealPlan');

        $mealPlanRecipe->delete();

        return redirect()->route('meal-plan-recipes.index');
    }

    protected function resolveMealPlan(Request $request, int $mealPlanId): MealPlan
    {
        return MealPlan::query()
            ->whereKey($mealPlanId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    protected function resolveRecipe(Request $request, int $recipeId): Recipe
    {
        return Recipe::query()
            ->whereKey($recipeId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }
}
