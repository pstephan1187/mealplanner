<?php

namespace Database\Factories;

use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeSection>
 */
class RecipeSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'name' => fake()->randomElement([
                'Crust', 'Filling', 'Sauce', 'Topping', 'Dressing',
                'Marinade', 'Batter', 'Frosting', 'Glaze', 'Garnish',
            ]),
            'sort_order' => 0,
            'instructions' => fake()->paragraphs(2, true),
        ];
    }
}
