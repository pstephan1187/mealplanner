<?php

use App\Actions\ParseRecipeFromUrl;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('imports a recipe from a url', function () {
    $user = User::factory()->create();

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Spaghetti Bolognese',
            'instructions' => '1. Cook pasta. 2. Make sauce.',
            'servings' => 4,
            'flavor_profile' => 'Savory',
            'meal_types' => ['Dinner'],
            'prep_time_minutes' => 15,
            'cook_time_minutes' => 30,
            'ingredients' => [
                ['name' => 'Spaghetti', 'quantity' => '400', 'unit' => 'g', 'note' => null],
            ],
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/recipe',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'name',
        'instructions',
        'servings',
        'flavor_profile',
        'meal_types',
        'prep_time_minutes',
        'cook_time_minutes',
        'ingredients' => [
            ['ingredient_id', 'name', 'quantity', 'unit', 'note'],
        ],
    ]);
    expect($response->json('name'))->toBe('Spaghetti Bolognese');
    expect($response->json('servings'))->toBe(4);
});

it('redirects unauthenticated users', function () {
    $response = $this->postJson(route('recipes.import'), [
        'url' => 'https://example.com/recipe',
    ]);

    $response->assertUnauthorized();
});

it('returns 403 when gate denies access', function () {
    Gate::define('can-import-recipes', fn () => false);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/recipe',
    ]);

    $response->assertForbidden();
});

it('validates that url is required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('recipes.import'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['url']);
});

it('validates that url must be a valid url', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'not-a-url',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['url']);
});

it('matches existing ingredients case-insensitively', function () {
    $user = User::factory()->create();
    $garlic = Ingredient::factory()->for($user)->create(['name' => 'Garlic']);
    $olive = Ingredient::factory()->for($user)->create(['name' => 'Olive Oil']);

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Garlic Bread',
            'instructions' => '1. Spread butter. 2. Add garlic.',
            'servings' => 2,
            'flavor_profile' => 'Savory',
            'meal_types' => ['Dinner'],
            'prep_time_minutes' => 5,
            'cook_time_minutes' => 10,
            'ingredients' => [
                ['name' => 'garlic', 'quantity' => '3', 'unit' => 'cloves', 'note' => null],
                ['name' => 'olive oil', 'quantity' => '2', 'unit' => 'tbsp', 'note' => 'extra virgin'],
                ['name' => 'Sourdough Bread', 'quantity' => '1', 'unit' => 'loaf', 'note' => null],
            ],
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/garlic-bread',
    ]);

    $response->assertSuccessful();

    $ingredients = $response->json('ingredients');

    expect($ingredients[0]['ingredient_id'])->toBe($garlic->id);
    expect($ingredients[0]['name'])->toBe('garlic');

    expect($ingredients[1]['ingredient_id'])->toBe($olive->id);
    expect($ingredients[1]['name'])->toBe('olive oil');

    expect($ingredients[2]['ingredient_id'])->toBeNull();
    expect($ingredients[2]['name'])->toBe('Sourdough Bread');
});

it('returns null ingredient_id for unmatched ingredients', function () {
    $user = User::factory()->create();

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Test Recipe',
            'instructions' => '1. Mix.',
            'servings' => 1,
            'flavor_profile' => null,
            'meal_types' => [],
            'prep_time_minutes' => null,
            'cook_time_minutes' => null,
            'ingredients' => [
                ['name' => 'Unknown Spice', 'quantity' => '1', 'unit' => 'tsp', 'note' => null],
            ],
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/recipe',
    ]);

    $response->assertSuccessful();
    expect($response->json('ingredients.0.ingredient_id'))->toBeNull();
    expect($response->json('ingredients.0.name'))->toBe('Unknown Spice');
});

it('handles partial data from ai extraction', function () {
    $user = User::factory()->create();

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Mystery Dish',
            'instructions' => null,
            'servings' => null,
            'flavor_profile' => null,
            'meal_types' => [],
            'prep_time_minutes' => null,
            'cook_time_minutes' => null,
            'ingredients' => [],
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/sparse-recipe',
    ]);

    $response->assertSuccessful();
    expect($response->json('name'))->toBe('Mystery Dish');
    expect($response->json('instructions'))->toBeNull();
    expect($response->json('servings'))->toBeNull();
    expect($response->json('ingredients'))->toBeEmpty();
});

it('returns 422 when nothing can be extracted', function () {
    $user = User::factory()->create();

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andThrow(new RuntimeException('Could not extract meaningful recipe data from the provided URL.'));

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/not-a-recipe',
    ]);

    $response->assertUnprocessable();
    expect($response->json('message'))->toBe('Could not extract meaningful recipe data from the provided URL.');
});

it('does not match ingredients from other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Ingredient::factory()->for($otherUser)->create(['name' => 'Butter']);

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Toast',
            'instructions' => '1. Toast bread. 2. Add butter.',
            'servings' => 1,
            'flavor_profile' => 'Savory',
            'meal_types' => ['Breakfast'],
            'prep_time_minutes' => 2,
            'cook_time_minutes' => 3,
            'ingredients' => [
                ['name' => 'Butter', 'quantity' => '1', 'unit' => 'tbsp', 'note' => null],
            ],
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/toast',
    ]);

    $response->assertSuccessful();
    expect($response->json('ingredients.0.ingredient_id'))->toBeNull();
});
