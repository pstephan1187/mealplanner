<?php

namespace Database\Seeders;

use App\Models\MealPlan;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShoppingListSeeder extends Seeder
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

        $mealPlans = MealPlan::query()
            ->where('user_id', $user->id)
            ->get();

        foreach ($mealPlans as $mealPlan) {
            ShoppingList::firstOrCreate(
                ['meal_plan_id' => $mealPlan->id],
                ['user_id' => $user->id]
            );
        }
    }
}
