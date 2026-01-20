<?php

namespace Database\Seeders;

use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class MealPlanRecipeSeeder extends Seeder
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

        $recipes = Recipe::query()
            ->where('user_id', $user->id)
            ->get();

        if ($recipes->isEmpty()) {
            return;
        }

        $mealPlans = MealPlan::query()
            ->where('user_id', $user->id)
            ->get();

        $recipesForMealType = function (string $mealType) use ($recipes) {
            return $recipes->filter(
                fn (Recipe $recipe) => in_array($mealType, $recipe->meal_types ?? [], true)
            )->values();
        };

        foreach ($mealPlans as $mealPlan) {
            $start = Carbon::parse($mealPlan->start_date);
            $end = Carbon::parse($mealPlan->end_date);

            $days = [];
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $days[] = $date->copy();
            }

            foreach ($days as $index => $day) {
                $this->assignMeal(
                    $mealPlan->id,
                    $day,
                    'Dinner',
                    $recipesForMealType('Dinner')
                );

                if ($index < 4) {
                    $this->assignMeal(
                        $mealPlan->id,
                        $day,
                        'Breakfast',
                        $recipesForMealType('Breakfast')
                    );
                }

                if ($index >= 2 && $index <= 4) {
                    $this->assignMeal(
                        $mealPlan->id,
                        $day,
                        'Lunch',
                        $recipesForMealType('Lunch')
                    );
                }
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Recipe>  $recipes
     */
    /**
     * @param  Collection<int, Recipe>  $recipes
     */
    protected function assignMeal(
        int $mealPlanId,
        Carbon $day,
        string $mealType,
        Collection $recipes
    ): void {
        if ($recipes->isEmpty()) {
            return;
        }

        /** @var Recipe $recipe */
        $recipe = $recipes->random();
        $servings = max(
            1,
            (int) round(
                $recipe->servings * fake()->randomFloat(2, 0.8, 1.2)
            )
        );

        MealPlanRecipe::updateOrCreate(
            [
                'meal_plan_id' => $mealPlanId,
                'recipe_id' => $recipe->id,
                'date' => $day->toDateString(),
                'meal_type' => $mealType,
            ],
            [
                'servings' => $servings,
            ]
        );
    }
}
