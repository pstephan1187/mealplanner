<?php

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/hot'));
});

dataset('recipeDevices', [
    'mobile' => 'mobile',
    'tablet' => 'tablet',
]);

it('creates a recipe with a photo', function (string $device) {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    Ingredient::factory()->count(2)->create();

    $page = $device === 'mobile'
        ? visit('/login')->on()->iPhone14Pro()
        : visit('/login')->on()->iPadPro();

    $page->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard');

    $page->navigate('/recipes/create')
        ->fill('name', 'Crispy Tofu Bowl')
        ->fill('servings', '2')
        ->fill('flavor_profile', 'Savory')
        ->fill('instructions', 'Bake the tofu until crisp.')
        ->attach('photo', base_path('tests/Fixtures/recipe.jpg'))
        ->submit()
        ->assertPathContains('/recipes/')
        ->assertSee('Crispy Tofu Bowl')
        ->assertNoJavascriptErrors();
})->with('recipeDevices');
