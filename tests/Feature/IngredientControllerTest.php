<?php

use App\Models\Ingredient;
use App\Models\User;

it('stores ingredients', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('ingredients.store'), [
        'name' => 'Paprika',
    ]);

    $ingredient = Ingredient::firstOrFail();

    $response->assertRedirect(route('ingredients.show', $ingredient));

    expect($ingredient->name)->toBe('Paprika');
});

it('updates ingredients', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->for($user)->create(['name' => 'Paprika']);

    $response = $this->actingAs($user)->patch(route('ingredients.update', $ingredient), [
        'name' => 'Smoked Paprika',
    ]);

    $response->assertRedirect(route('ingredients.show', $ingredient));

    expect($ingredient->refresh()->name)->toBe('Smoked Paprika');
});

it('deletes ingredients', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('ingredients.destroy', $ingredient));

    $response->assertRedirect(route('ingredients.index'));

    expect(Ingredient::query()->whereKey($ingredient->id)->exists())->toBeFalse();
});

it('stores ingredients via quick endpoint and returns JSON', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.store-quick'), [
        'name' => 'Fresh Basil',
    ]);

    $response->assertCreated();
    $response->assertJsonStructure([
        'ingredient' => ['id', 'name'],
    ]);

    expect($response->json('ingredient.name'))->toBe('Fresh Basil');
    expect(Ingredient::where('name', 'Fresh Basil')->where('user_id', $user->id)->exists())->toBeTrue();
});

it('validates ingredient name uniqueness per user on quick endpoint', function () {
    $user = User::factory()->create();
    Ingredient::factory()->for($user)->create(['name' => 'Garlic']);

    $response = $this->actingAs($user)->postJson(route('ingredients.store-quick'), [
        'name' => 'Garlic',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['name']);
});
