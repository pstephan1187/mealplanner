<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;

it('stores recipes with fraction quantities', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Fraction recipe',
        'instructions' => 'Mix ingredients.',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'ingredients' => [
            [
                'ingredient_id' => $ingredient->id,
                'quantity' => '1/2',
                'unit' => 'cup',
            ],
        ],
    ]);

    $response->assertRedirect();

    $recipe = Recipe::query()->where('name', 'Fraction recipe')->first();
    expect($recipe)->not->toBeNull();
    expect((float) $recipe->ingredients->first()->pivot->quantity)->toBe(0.5);
});

it('stores recipes with mixed number quantities', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Mixed number recipe',
        'instructions' => 'Mix ingredients.',
        'servings' => 4,
        'flavor_profile' => 'Sweet',
        'ingredients' => [
            [
                'ingredient_id' => $ingredient->id,
                'quantity' => '1 3/4',
                'unit' => 'cups',
            ],
        ],
    ]);

    $response->assertRedirect();

    $recipe = Recipe::query()->where('name', 'Mixed number recipe')->first();
    expect((float) $recipe->ingredients->first()->pivot->quantity)->toBe(1.75);
});

it('still accepts decimal quantities', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Decimal recipe',
        'instructions' => 'Mix ingredients.',
        'servings' => 1,
        'flavor_profile' => 'Mild',
        'ingredients' => [
            [
                'ingredient_id' => $ingredient->id,
                'quantity' => '2.5',
                'unit' => 'tbsp',
            ],
        ],
    ]);

    $response->assertRedirect();

    $recipe = Recipe::query()->where('name', 'Decimal recipe')->first();
    expect((float) $recipe->ingredients->first()->pivot->quantity)->toBe(2.5);
});

it('rejects invalid quantity formats', function (string $quantity) {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Invalid recipe',
        'instructions' => 'Mix ingredients.',
        'servings' => 1,
        'flavor_profile' => 'Mild',
        'ingredients' => [
            [
                'ingredient_id' => $ingredient->id,
                'quantity' => $quantity,
                'unit' => 'cup',
            ],
        ],
    ]);

    $response->assertSessionHasErrors('ingredients.0.quantity');
})->with([
    'text' => ['abc'],
    'zero' => ['0'],
    'empty' => [''],
]);

it('displays quantities as fractions on show page', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->create(['user_id' => $user->id, 'name' => 'Flour']);

    $recipe = Recipe::factory()->for($user)->create();
    $recipe->ingredients()->attach($ingredient->id, [
        'quantity' => 0.5,
        'unit' => 'cup',
        'note' => null,
    ]);

    $response = $this->actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertSuccessful();

    $ingredientData = $response->original->getData()['page']['props']['recipe']['data']['ingredients'][0];
    expect($ingredientData['pivot']['quantity'])->toBe('1/2');
});
