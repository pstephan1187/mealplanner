<?php

use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/hot'));
});

dataset('mealPlanDevices', [
    'mobile' => 'mobile',
    'tablet' => 'tablet',
]);

it('assigns and replaces meals in the week view', function (string $device) {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    $mealPlan = MealPlan::factory()->for($user)->create([
        'name' => 'Week One',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
    ]);

    $recipeA = Recipe::factory()->for($user)->create([
        'name' => 'Lemon Chicken',
        'servings' => 2,
    ]);

    $recipeB = Recipe::factory()->for($user)->create([
        'name' => 'Veggie Bowls',
        'servings' => 2,
    ]);

    $page = $device === 'mobile'
        ? visit('/login')->on()->iPhone14Pro()
        : visit('/login')->on()->iPadPro();

    $page->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard');

    $url = "/meal-plans/{$mealPlan->id}";

    $page->navigate($url)
        ->click('@meal-slot-2026-01-01-breakfast')
        ->select('recipe_id', (string) $recipeA->id)
        ->fill('servings', '2')
        ->press('Add meal')
        ->wait(500)
        ->assertSee('Lemon Chicken');

    $mealPlanRecipe = MealPlanRecipe::query()
        ->where('meal_plan_id', $mealPlan->id)
        ->firstOrFail();

    $page->click("@meal-item-{$mealPlanRecipe->id}")
        ->select('recipe_id', (string) $recipeB->id)
        ->press('Save changes')
        ->wait(500)
        ->assertSee('Veggie Bowls')
        ->assertNoJavascriptErrors();
})->with('mealPlanDevices');
