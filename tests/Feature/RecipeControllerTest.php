<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeSection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

it('updates recipes with new photos and ingredients', function () {
    Config::set('filesystems.default', 'local');
    Storage::fake('local');
    Storage::fake('public');

    $user = User::factory()->create();
    $ingredientA = Ingredient::factory()->for($user)->create();
    $ingredientB = Ingredient::factory()->for($user)->create();

    $recipe = Recipe::factory()->for($user)->create([
        'name' => 'Old Recipe',
        'instructions' => 'Old instructions',
        'servings' => 2,
        'flavor_profile' => 'Old',
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
    ]);

    $response->assertNotFound();
});

it('rejects creating a recipe with another users ingredient', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherIngredient = Ingredient::factory()->for($otherUser)->create();

    $response = $this->actingAs($user)->postJson(route('recipes.store'), [
        'name' => 'Test Recipe',
        'instructions' => 'Test instructions',
        'servings' => 4,
        'flavor_profile' => 'Savory',
        'ingredients' => [
            [
                'ingredient_id' => $otherIngredient->id,
                'quantity' => '1',
                'unit' => 'cup',
            ],
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ingredients.0.ingredient_id']);
});

it('rejects updating a recipe with another users ingredient', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherIngredient = Ingredient::factory()->for($otherUser)->create();
    $recipe = Recipe::factory()->for($user)->create();

    $response = $this->actingAs($user)->patchJson(route('recipes.update', $recipe), [
        'ingredients' => [
            [
                'ingredient_id' => $otherIngredient->id,
                'quantity' => '1',
                'unit' => 'cup',
            ],
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ingredients.0.ingredient_id']);
});

it('rejects creating a recipe section with another users ingredient', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherIngredient = Ingredient::factory()->for($otherUser)->create();

    $response = $this->actingAs($user)->postJson(route('recipes.store'), [
        'name' => 'Test Recipe',
        'servings' => 4,
        'flavor_profile' => 'Savory',
        'sections' => [
            [
                'name' => 'Section 1',
                'sort_order' => 0,
                'instructions' => null,
                'ingredients' => [
                    [
                        'ingredient_id' => $otherIngredient->id,
                        'quantity' => '1',
                        'unit' => 'cup',
                    ],
                ],
            ],
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['sections.0.ingredients.0.ingredient_id']);
});

it('prevents deleting recipes for other users', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $response = $this->actingAs($user)->delete(route('recipes.destroy', $recipe));

    $response->assertNotFound();
});

it('duplicates a flat recipe', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->for($user)->create();

    $recipe = Recipe::factory()->for($user)->create([
        'name' => 'Original Recipe',
        'instructions' => 'Do the thing',
        'servings' => 4,
        'flavor_profile' => 'Savory',
    ]);

    $recipe->ingredients()->attach($ingredient->id, [
        'quantity' => 1.5,
        'unit' => 'cup',
        'note' => 'chopped',
    ]);

    $response = $this->actingAs($user)->post(route('recipes.duplicate', $recipe));

    $newRecipe = Recipe::query()
        ->where('name', 'Original Recipe (Copy)')
        ->first();

    expect($newRecipe)->not->toBeNull();
    $response->assertRedirect(route('recipes.edit', $newRecipe));

    expect($newRecipe->instructions)->toBe('Do the thing');
    expect($newRecipe->servings)->toBe(4);
    expect($newRecipe->photo_path)->toBeNull();

    $newRecipe->load('ingredients');
    expect($newRecipe->ingredients)->toHaveCount(1);
    expect($newRecipe->ingredients->first()->pivot->quantity)->toBe(1.5);
    expect($newRecipe->ingredients->first()->pivot->unit)->toBe('cup');
    expect($newRecipe->ingredients->first()->pivot->note)->toBe('chopped');
});

it('duplicates a sectioned recipe', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->for($user)->create();

    $recipe = Recipe::factory()->for($user)->create(['name' => 'Sectioned Recipe']);
    $section = RecipeSection::factory()->for($recipe)->create([
        'name' => 'Sauce',
        'sort_order' => 0,
        'instructions' => 'Mix well',
    ]);

    \Illuminate\Support\Facades\DB::table('ingredient_recipe')->insert([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'recipe_section_id' => $section->id,
        'quantity' => 2,
        'unit' => 'tbsp',
        'note' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('recipes.duplicate', $recipe));

    $newRecipe = Recipe::query()
        ->where('name', 'Sectioned Recipe (Copy)')
        ->first();

    expect($newRecipe)->not->toBeNull();

    $newRecipe->load(['sections.ingredients']);
    expect($newRecipe->sections)->toHaveCount(1);
    expect($newRecipe->sections->first()->name)->toBe('Sauce');
    expect($newRecipe->sections->first()->instructions)->toBe('Mix well');
    expect($newRecipe->sections->first()->ingredients)->toHaveCount(1);
});

it('prevents duplicating recipes for other users', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $response = $this->actingAs($user)->post(route('recipes.duplicate', $recipe));

    $response->assertNotFound();
});

it('filters recipes by search query', function () {
    $user = User::factory()->create();
    Recipe::factory()->for($user)->create(['name' => 'Chicken Parmesan']);
    Recipe::factory()->for($user)->create(['name' => 'Beef Stew']);
    Recipe::factory()->for($user)->create(['name' => 'Chicken Alfredo']);

    $response = $this->actingAs($user)->get(route('recipes.index', ['search' => 'chicken']));

    $response->assertSuccessful();
    $response->assertInertia(function ($page) {
        $page->component('recipes/Index')
            ->has('recipes.data', 2)
            ->where('filters.search', 'chicken');
    });
});

it('includes sections count on recipe index', function () {
    $user = User::factory()->create();

    $flat = Recipe::factory()->for($user)->create(['name' => 'Flat Recipe']);
    $sectioned = Recipe::factory()->for($user)->create(['name' => 'Sectioned Recipe']);
    RecipeSection::factory()->for($sectioned)->create(['sort_order' => 0]);
    RecipeSection::factory()->for($sectioned)->create(['sort_order' => 1]);

    $response = $this->actingAs($user)->get(route('recipes.index'));

    $response->assertSuccessful();
    $response->assertInertia(function ($page) use ($flat, $sectioned) {
        $page->component('recipes/Index')->has('recipes.data', 2);

        $recipes = collect($page->toArray()['props']['recipes']['data']);
        $sectionedData = $recipes->firstWhere('id', $sectioned->id);
        $flatData = $recipes->firstWhere('id', $flat->id);

        expect($sectionedData['sections_count'])->toBe(2);
        expect($flatData['sections_count'])->toBe(0);
    });
});
