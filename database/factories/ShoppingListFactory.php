<?php

namespace Database\Factories;

use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShoppingList>
 */
class ShoppingListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'meal_plan_id' => MealPlan::factory(),
            'display_mode' => 'manual',
        ];
    }

    public function shared(): static
    {
        return $this->state(fn () => [
            'share_token' => Str::uuid()->toString(),
        ]);
    }
}
