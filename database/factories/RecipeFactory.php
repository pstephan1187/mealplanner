<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
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
            'name' => fake()->sentence(3),
            'instructions' => fake()->paragraphs(3, true),
            'servings' => fake()->numberBetween(1, 8),
            'flavor_profile' => fake()->randomElement(['Sweet', 'Savory', 'Spicy', 'Fresh', 'Umami']),
            'photo_path' => null,
            'prep_time_minutes' => fake()->numberBetween(5, 45),
            'cook_time_minutes' => fake()->numberBetween(5, 90),
        ];
    }
}
