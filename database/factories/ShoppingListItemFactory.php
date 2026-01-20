<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\ShoppingList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShoppingListItem>
 */
class ShoppingListItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shopping_list_id' => ShoppingList::factory(),
            'ingredient_id' => Ingredient::factory(),
            'quantity' => fake()->randomFloat(2, 0.25, 10),
            'unit' => fake()->randomElement(['g', 'ml', 'cup', 'tbsp', 'tsp']),
            'is_purchased' => false,
            'sort_order' => fake()->optional()->numberBetween(1, 200),
        ];
    }
}
