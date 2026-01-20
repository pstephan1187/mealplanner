<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

it('updates recipes with new photos and ingredients', function () {
    Config::set('filesystems.default', 'local');
    Storage::fake('local');
    Storage::fake('public');

    $user = User::factory()->create();
    $ingredientA = Ingredient::factory()->create();
    $ingredientB = Ingredient::factory()->create();

    $recipe = Recipe::factory()->for($user)->create([
        'name' => 'Old Recipe',
        'instructions' => 'Old instructions',
        'servings' => 2,
        'flavor_profile' => 'Old',
        'meal_types' => ['Dinner'],
        'photo_path' => 'recipes/old.jpg',
    ]);

    Storage::disk('public')->put($recipe->photo_path, 'old');

    $recipe->ingredients()->attach($ingredientA->id, [
        'quantity' => 1,
        'unit' => 'cup',
        'note' => null,
    ]);

    $response = $this->actingAs($user)->patch(route('recipes.update', $recipe), [
        'name' => 'New Recipe',
        'instructions' => 'New instructions',
        'servings' => 4,
        'flavor_profile' => 'Bright',
        'meal_types' => ['Lunch'],
        'photo' => UploadedFile::fake()->image('new.jpg', 1200, 1200),
        'ingredients' => [
            [
                'ingredient_id' => $ingredientB->id,
                'quantity' => 2.5,
                'unit' => 'tbsp',
                'note' => 'ground',
            ],
        ],
    ]);

    $recipe->refresh();

    $response->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->name)->toBe('New Recipe');
    expect($recipe->photo_path)->not->toBeNull();
    Storage::disk('public')->assertMissing('recipes/old.jpg');
    Storage::disk('public')->assertExists($recipe->photo_path);

    $recipe->load('ingredients');

    expect($recipe->ingredients)->toHaveCount(1);
    expect($recipe->ingredients->first()?->id)->toBe($ingredientB->id);
    expect($recipe->ingredients->first()?->pivot->quantity)->toBe(2.5);
    expect($recipe->ingredients->first()?->pivot->unit)->toBe('tbsp');
    expect($recipe->ingredients->first()?->pivot->note)->toBe('ground');
});

it('deletes recipes and photos', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create([
        'photo_path' => 'recipes/delete-me.jpg',
    ]);

    Storage::disk('public')->put($recipe->photo_path, 'old');

    $response = $this->actingAs($user)->delete(route('recipes.destroy', $recipe));

    $response->assertRedirect(route('recipes.index'));

    expect(Recipe::query()->whereKey($recipe->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing('recipes/delete-me.jpg');
});

it('prevents editing recipes for other users', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $response = $this->actingAs($user)->patch(route('recipes.update', $recipe), [
        'name' => 'Not allowed',
        'instructions' => 'Nope',
        'servings' => 1,
        'flavor_profile' => 'None',
        'meal_types' => ['Breakfast'],
    ]);

    $response->assertNotFound();
});

it('prevents deleting recipes for other users', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $response = $this->actingAs($user)->delete(route('recipes.destroy', $recipe));

    $response->assertNotFound();
});
