<?php

namespace App\Http\Controllers;

use App\Http\Requests\MealPlans\StoreMealPlanRequest;
use App\Http\Requests\MealPlans\UpdateMealPlanRequest;
use App\Http\Resources\MealPlanResource;
use App\Http\Resources\RecipeResource;
use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MealPlanController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $mealPlans = MealPlan::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate();

        return Inertia::render('meal-plans/Index', [
            'mealPlans' => MealPlanResource::collection($mealPlans),
        ]);
    }

    public function create(): InertiaResponse
    {
        $today = Date::today();

        return Inertia::render('meal-plans/Create', [
            'defaultStartDate' => $today->toDateString(),
            'defaultEndDate' => $today->addWeek()->toDateString(),
        ]);
    }

    public function store(StoreMealPlanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['name'])) {
            $data['name'] = $this->generateNameFromDates($data['start_date'], $data['end_date']);
        }

        $mealPlan = $request->user()->mealPlans()->create($data);

        return redirect()->route('meal-plans.show', $mealPlan);
    }

    protected function generateNameFromDates(string $startDate, string $endDate): string
    {
        $start = Date::parse($startDate);
        $end = Date::parse($endDate);

        if ($start->isSameDay($end)) {
            return $start->format('F j, Y');
        }

        if ($start->isSameMonth($end)) {
            return $start->format('F j').' - '.$end->format('j, Y');
        }

        if ($start->isSameYear($end)) {
            return $start->format('F j').' - '.$end->format('F j, Y');
        }

        return $start->format('F j, Y').' - '.$end->format('F j, Y');
    }

    public function show(Request $request, MealPlan $mealPlan): InertiaResponse
    {
        $this->ensureMealPlanOwner($request, $mealPlan);

        $mealPlan->load([
            'mealPlanRecipes.recipe',
            'shoppingList.items.ingredient',
        ]);

        $recipes = Recipe::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('meal-plans/Show', [
            'mealPlan' => MealPlanResource::make($mealPlan),
            'recipes' => RecipeResource::collection($recipes),
        ]);
    }

    public function edit(Request $request, MealPlan $mealPlan): InertiaResponse
    {
        $this->ensureMealPlanOwner($request, $mealPlan);

        return Inertia::render('meal-plans/Edit', [
            'mealPlan' => MealPlanResource::make($mealPlan),
        ]);
    }

    public function update(UpdateMealPlanRequest $request, MealPlan $mealPlan): RedirectResponse
    {
        $this->ensureMealPlanOwner($request, $mealPlan);

        $mealPlan->update($request->validated());

        return redirect()->route('meal-plans.show', $mealPlan);
    }

    public function destroy(Request $request, MealPlan $mealPlan): RedirectResponse
    {
        $this->ensureMealPlanOwner($request, $mealPlan);

        $mealPlan->delete();

        return redirect()->route('meal-plans.index');
    }

    protected function ensureMealPlanOwner(Request $request, MealPlan $mealPlan): void
    {
        if ($mealPlan->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
