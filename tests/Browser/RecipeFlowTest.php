<?php

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/hot'));
});

dataset('recipeDevices', [
    'mobile' => 'mobile',
    'tablet' => 'tablet',
]);

it('creates a recipe with a photo', function (string $device) {
    $user = User::factory()->create();
    Ingredient::factory()->count(2)->create();

    $this->actingAs($user);

    $page = visit('/recipes/create');

    $page = $device === 'mobile'
        ? $page->on()->iPhone14Pro()
        : $page->on()->iPadPro();

    $page->fill('name', 'Crispy Tofu Bowl')
        ->fill('servings', '2')
        ->fill('flavor_profile', 'Savory')
        ->click('@meal-type-dinner')
        ->fill('instructions', 'Bake the tofu until crisp.')
        ->attach('photo', base_path('tests/Fixtures/recipe.jpg'))
        ->submit()
        ->wait(500)
        ->assertPathContains('/recipes/')
        ->assertSee('Crispy Tofu Bowl')
        ->assertNoJavascriptErrors();
})->with('recipeDevices');
