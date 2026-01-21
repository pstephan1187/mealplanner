<?php

namespace Database\Factories;

use App\Models\GroceryStore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroceryStoreSection>
 */
class GroceryStoreSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grocery_store_id' => GroceryStore::factory(),
            'name' => fake()->unique()->randomElement([
                'Produce',
                'Dairy',
                'Meat',
                'Bakery',
                'Frozen',
                'Canned Goods',
                'Snacks',
                'Beverages',
                'Condiments',
                'Spices',
            ]),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
