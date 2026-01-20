<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => config('seeder.user.email')],
            [
                'name' => config('seeder.user.name'),
                'password' => config('seeder.user.password'),
            ]
        );

        $this->call([
            IngredientSeeder::class,
            RecipeSeeder::class,
            MealPlanSeeder::class,
            MealPlanRecipeSeeder::class,
            ShoppingListSeeder::class,
            ShoppingListItemSeeder::class,
        ]);
    }
}
