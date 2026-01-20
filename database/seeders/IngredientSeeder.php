<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
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

        $ingredients = [
            'Olive oil',
            'Kosher salt',
            'Black pepper',
            'Garlic',
            'Yellow onion',
            'Red onion',
            'Lemon',
            'Lime',
            'Butter',
            'All-purpose flour',
            'Basmati rice',
            'Jasmine rice',
            'Soy sauce',
            'Honey',
            'Dijon mustard',
            'Apple cider vinegar',
            'Canned tomatoes',
            'Chicken broth',
            'Parmesan cheese',
            'Mozzarella cheese',
            'Greek yogurt',
            'Eggs',
            'Milk',
            'Brown sugar',
            'Granola',
            'Rolled oats',
            'Chia seeds',
            'Cumin',
            'Paprika',
            'Chili powder',
            'Oregano',
            'Basil',
            'Cilantro',
            'Spinach',
            'Bell pepper',
            'Zucchini',
            'Carrots',
            'Celery',
            'Cherry tomatoes',
            'Black beans',
            'Chicken breast',
            'Ground turkey',
            'Salmon fillet',
            'Pasta',
            'Tortillas',
            'Avocado',
            'Frozen peas',
            'Green onion',
            'Sesame oil',
            'Mixed berries',
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::firstOrCreate(
                ['user_id' => $user->id, 'name' => $ingredient],
            );
        }
    }
}
