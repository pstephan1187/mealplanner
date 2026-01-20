<?php

namespace Database\Factories;

use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MealPlanRecipe>
 */
class MealPlanRecipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_plan_id' => MealPlan::factory(),
            'recipe_id' => Recipe::factory(),
            'date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'meal_type' => fake()->randomElement(['Breakfast', 'Lunch', 'Dinner']),
            'servings' => fake()->numberBetween(1, 8),
        ];
    }
}
