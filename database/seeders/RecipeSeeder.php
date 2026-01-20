<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => config('seeder.user.email')],
            [
                'name' => config('seeder.user.name'),
                'password' => config('seeder.user.password'),
            ]
        );

        $recipes = [
            [
                'name' => 'Honey Garlic Salmon',
                'instructions' => "1. Pat salmon dry and season with salt and pepper.\n2. Whisk honey, soy sauce, garlic, and lemon juice.\n3. Sear salmon, pour sauce, and bake until flaky.\n4. Spoon glaze over the top before serving.",
                'servings' => 2,
                'flavor_profile' => 'Sweet and savory',
                'meal_types' => ['Dinner'],
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 15,
                'ingredients' => [
                    ['name' => 'Salmon fillet', 'quantity' => 1, 'unit' => 'lb', 'note' => 'portion into fillets'],
                    ['name' => 'Honey', 'quantity' => 2, 'unit' => 'tbsp'],
                    ['name' => 'Soy sauce', 'quantity' => 2, 'unit' => 'tbsp'],
                    ['name' => 'Garlic', 'quantity' => 2, 'unit' => 'clove', 'note' => 'minced'],
                    ['name' => 'Lemon', 'quantity' => 0.5, 'unit' => 'piece', 'note' => 'juiced'],
                    ['name' => 'Olive oil', 'quantity' => 1, 'unit' => 'tbsp'],
                ],
            ],
            [
                'name' => 'Sheet Pan Chicken Fajitas',
                'instructions' => "1. Slice peppers and onion, toss with oil and spices.\n2. Add chicken strips and coat evenly.\n3. Roast until chicken is cooked through.\n4. Serve with tortillas and lime.",
                'servings' => 4,
                'flavor_profile' => 'Smoky and bright',
                'meal_types' => ['Dinner'],
                'prep_time_minutes' => 15,
                'cook_time_minutes' => 20,
                'ingredients' => [
                    ['name' => 'Chicken breast', 'quantity' => 1.5, 'unit' => 'lb', 'note' => 'sliced'],
                    ['name' => 'Bell pepper', 'quantity' => 2, 'unit' => 'piece', 'note' => 'sliced'],
                    ['name' => 'Yellow onion', 'quantity' => 1, 'unit' => 'piece', 'note' => 'sliced'],
                    ['name' => 'Cumin', 'quantity' => 2, 'unit' => 'tsp'],
                    ['name' => 'Paprika', 'quantity' => 2, 'unit' => 'tsp'],
                    ['name' => 'Olive oil', 'quantity' => 2, 'unit' => 'tbsp'],
                    ['name' => 'Tortillas', 'quantity' => 8, 'unit' => 'piece'],
                    ['name' => 'Lime', 'quantity' => 1, 'unit' => 'piece'],
                ],
            ],
            [
                'name' => 'Veggie Fried Rice',
                'instructions' => "1. Scramble eggs and set aside.\n2. Saute garlic, carrots, peas, and green onion.\n3. Stir in rice and soy sauce.\n4. Fold in eggs and finish with sesame oil.",
                'servings' => 3,
                'flavor_profile' => 'Savory',
                'meal_types' => ['Lunch', 'Dinner'],
                'prep_time_minutes' => 15,
                'cook_time_minutes' => 15,
                'ingredients' => [
                    ['name' => 'Jasmine rice', 'quantity' => 2, 'unit' => 'cup', 'note' => 'cooked'],
                    ['name' => 'Eggs', 'quantity' => 2, 'unit' => 'piece'],
                    ['name' => 'Garlic', 'quantity' => 2, 'unit' => 'clove', 'note' => 'minced'],
                    ['name' => 'Carrots', 'quantity' => 1, 'unit' => 'piece', 'note' => 'diced'],
                    ['name' => 'Frozen peas', 'quantity' => 1, 'unit' => 'cup'],
                    ['name' => 'Green onion', 'quantity' => 2, 'unit' => 'piece', 'note' => 'sliced'],
                    ['name' => 'Soy sauce', 'quantity' => 2, 'unit' => 'tbsp'],
                    ['name' => 'Sesame oil', 'quantity' => 1, 'unit' => 'tsp'],
                ],
            ],
            [
                'name' => 'Greek Yogurt Parfait',
                'instructions' => "1. Layer yogurt, granola, and berries in a glass.\n2. Drizzle with honey and finish with a pinch of cinnamon.",
                'servings' => 1,
                'flavor_profile' => 'Fresh and sweet',
                'meal_types' => ['Breakfast'],
                'prep_time_minutes' => 5,
                'cook_time_minutes' => 0,
                'ingredients' => [
                    ['name' => 'Greek yogurt', 'quantity' => 1, 'unit' => 'cup'],
                    ['name' => 'Granola', 'quantity' => 0.5, 'unit' => 'cup'],
                    ['name' => 'Mixed berries', 'quantity' => 0.5, 'unit' => 'cup'],
                    ['name' => 'Honey', 'quantity' => 1, 'unit' => 'tsp'],
                ],
            ],
            [
                'name' => 'Turkey Chili',
                'instructions' => "1. Saute onion and garlic, then brown turkey.\n2. Add spices, tomatoes, and beans.\n3. Simmer until thick and flavorful.",
                'servings' => 5,
                'flavor_profile' => 'Hearty and spicy',
                'meal_types' => ['Lunch', 'Dinner'],
                'prep_time_minutes' => 15,
                'cook_time_minutes' => 35,
                'ingredients' => [
                    ['name' => 'Ground turkey', 'quantity' => 1, 'unit' => 'lb'],
                    ['name' => 'Yellow onion', 'quantity' => 1, 'unit' => 'piece', 'note' => 'diced'],
                    ['name' => 'Garlic', 'quantity' => 3, 'unit' => 'clove', 'note' => 'minced'],
                    ['name' => 'Canned tomatoes', 'quantity' => 1, 'unit' => 'can'],
                    ['name' => 'Black beans', 'quantity' => 1, 'unit' => 'can'],
                    ['name' => 'Chili powder', 'quantity' => 2, 'unit' => 'tsp'],
                    ['name' => 'Cumin', 'quantity' => 1, 'unit' => 'tsp'],
                ],
            ],
            [
                'name' => 'Caprese Pasta',
                'instructions' => "1. Cook pasta until al dente.\n2. Toss with olive oil, tomatoes, and basil.\n3. Fold in mozzarella and a splash of pasta water.",
                'servings' => 4,
                'flavor_profile' => 'Bright and herbaceous',
                'meal_types' => ['Lunch', 'Dinner'],
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 15,
                'ingredients' => [
                    ['name' => 'Pasta', 'quantity' => 12, 'unit' => 'oz'],
                    ['name' => 'Cherry tomatoes', 'quantity' => 2, 'unit' => 'cup'],
                    ['name' => 'Mozzarella cheese', 'quantity' => 1, 'unit' => 'cup', 'note' => 'torn'],
                    ['name' => 'Basil', 'quantity' => 0.5, 'unit' => 'cup', 'note' => 'torn'],
                    ['name' => 'Olive oil', 'quantity' => 2, 'unit' => 'tbsp'],
                    ['name' => 'Garlic', 'quantity' => 1, 'unit' => 'clove', 'note' => 'minced'],
                ],
            ],
            [
                'name' => 'Overnight Oats',
                'instructions' => "1. Combine oats, milk, and chia seeds.\n2. Stir in honey and refrigerate overnight.\n3. Top with berries before serving.",
                'servings' => 2,
                'flavor_profile' => 'Creamy and lightly sweet',
                'meal_types' => ['Breakfast'],
                'prep_time_minutes' => 5,
                'cook_time_minutes' => 0,
                'ingredients' => [
                    ['name' => 'Rolled oats', 'quantity' => 1, 'unit' => 'cup'],
                    ['name' => 'Milk', 'quantity' => 1, 'unit' => 'cup'],
                    ['name' => 'Chia seeds', 'quantity' => 1, 'unit' => 'tbsp'],
                    ['name' => 'Honey', 'quantity' => 1, 'unit' => 'tbsp'],
                    ['name' => 'Mixed berries', 'quantity' => 0.5, 'unit' => 'cup'],
                ],
            ],
            [
                'name' => 'Lemon Herb Roasted Veggies',
                'instructions' => "1. Toss veggies with olive oil, lemon, and herbs.\n2. Roast until tender and caramelized.\n3. Finish with parmesan.",
                'servings' => 4,
                'flavor_profile' => 'Fresh and savory',
                'meal_types' => ['Dinner'],
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 25,
                'ingredients' => [
                    ['name' => 'Zucchini', 'quantity' => 2, 'unit' => 'piece', 'note' => 'sliced'],
                    ['name' => 'Bell pepper', 'quantity' => 1, 'unit' => 'piece', 'note' => 'sliced'],
                    ['name' => 'Carrots', 'quantity' => 2, 'unit' => 'piece', 'note' => 'sliced'],
                    ['name' => 'Olive oil', 'quantity' => 2, 'unit' => 'tbsp'],
                    ['name' => 'Lemon', 'quantity' => 1, 'unit' => 'piece', 'note' => 'zested'],
                    ['name' => 'Oregano', 'quantity' => 1, 'unit' => 'tsp'],
                    ['name' => 'Parmesan cheese', 'quantity' => 0.25, 'unit' => 'cup'],
                ],
            ],
        ];

        foreach ($recipes as $recipeData) {
            $ingredientRows = Arr::pull($recipeData, 'ingredients', []);

            $recipe = Recipe::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $recipeData['name'],
                ],
                $recipeData
            );

            $syncData = [];
            foreach ($ingredientRows as $ingredientRow) {
                $ingredient = Ingredient::firstOrCreate([
                    'name' => $ingredientRow['name'],
                ]);

                $syncData[$ingredient->id] = [
                    'quantity' => $ingredientRow['quantity'],
                    'unit' => $ingredientRow['unit'],
                    'note' => $ingredientRow['note'] ?? null,
                ];
            }

            if ($syncData !== []) {
                $recipe->ingredients()->sync($syncData);
            }
        }
    }
}
