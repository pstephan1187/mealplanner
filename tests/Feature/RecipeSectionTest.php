<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeSection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('stores a recipe with sections', function () {
    $user = User::factory()->create();
    $ingredientA = Ingredient::factory()->for($user)->create();
    $ingredientB = Ingredient::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Pie Recipe',
        'servings' => 4,
        'flavor_profile' => 'Sweet',
        'sections' => [
            [
                'name' => 'Crust',
                'sort_order' => 0,
                'instructions' => '<p>Mix and press.</p>',
                'ingredients' => [
                    ['ingredient_id' => $ingredientA->id, 'quantity' => '2', 'unit' => 'cups'],
                ],
            ],
            [
                'name' => 'Filling',
                'sort_order' => 1,
                'instructions' => '<p>Cook the filling.</p>',
                'ingredients' => [
                    ['ingredient_id' => $ingredientB->id, 'quantity' => '1', 'unit' => 'cup'],
                ],
            ],
        ],
    ]);

    $recipe = Recipe::query()->where('name', 'Pie Recipe')->first();
    $response->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->sections)->toHaveCount(2);
    expect($recipe->sections[0]->name)->toBe('Crust');
    expect($recipe->sections[0]->sort_order)->toBe(0);
    expect($recipe->sections[0]->instructions)->toContain('Mix and press');
    expect($recipe->sections[1]->name)->toBe('Filling');

    $crustIngredients = $recipe->sections[0]->ingredients;
    expect($crustIngredients)->toHaveCount(1);
    expect($crustIngredients->first()->id)->toBe($ingredientA->id);

    $fillingIngredients = $recipe->sections[1]->ingredients;
    expect($fillingIngredients)->toHaveCount(1);
    expect($fillingIngredients->first()->id)->toBe($ingredientB->id);
});

it('stores a recipe without sections (backward compat)', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Simple Recipe',
        'instructions' => '<p>Just cook it.</p>',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'ingredients' => [
            ['ingredient_id' => $ingredient->id, 'quantity' => '1', 'unit' => 'cup'],
        ],
    ]);

    $recipe = Recipe::query()->where('name', 'Simple Recipe')->first();
    $response->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->sections)->toHaveCount(0);
    expect($recipe->ingredients)->toHaveCount(1);
    expect($recipe->instructions)->toContain('Just cook it');
});

it('updates a flat recipe to add sections', function () {
    $user = User::factory()->create();
    $ingredientA = Ingredient::factory()->for($user)->create();
    $ingredientB = Ingredient::factory()->for($user)->create();

    $recipe = Recipe::factory()->for($user)->create();

    DB::table('ingredient_recipe')->insert([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredientA->id,
        'quantity' => 1,
        'unit' => 'cup',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)->patch(route('recipes.update', $recipe), [
        'sections' => [
            [
                'name' => 'Main',
                'sort_order' => 0,
                'instructions' => '<p>Do it.</p>',
                'ingredients' => [
                    ['ingredient_id' => $ingredientB->id, 'quantity' => '2', 'unit' => 'tbsp'],
                ],
            ],
        ],
    ]);

    $recipe->refresh()->load(['sections.ingredients', 'ingredients']);
    $response->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->sections)->toHaveCount(1);
    expect($recipe->sections[0]->name)->toBe('Main');
    expect($recipe->sections[0]->ingredients)->toHaveCount(1);

    // Old flat ingredients should be gone
    $flatPivotCount = DB::table('ingredient_recipe')
        ->where('recipe_id', $recipe->id)
        ->whereNull('recipe_section_id')
        ->count();
    expect($flatPivotCount)->toBe(0);
});

it('updates a sectioned recipe', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->for($user)->create();

    $recipe = Recipe::factory()->for($user)->create();
    $section = RecipeSection::factory()->for($recipe)->create(['name' => 'Old Section', 'sort_order' => 0]);

    DB::table('ingredient_recipe')->insert([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'recipe_section_id' => $section->id,
        'quantity' => 1,
        'unit' => 'cup',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)->patch(route('recipes.update', $recipe), [
        'sections' => [
            [
                'name' => 'New Section',
                'sort_order' => 0,
                'instructions' => '<p>New steps.</p>',
                'ingredients' => [
                    ['ingredient_id' => $ingredient->id, 'quantity' => '3', 'unit' => 'tbsp'],
                ],
            ],
        ],
    ]);

    $recipe->refresh()->load('sections.ingredients');
    $response->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->sections)->toHaveCount(1);
    expect($recipe->sections[0]->name)->toBe('New Section');
    expect($recipe->sections[0]->ingredients)->toHaveCount(1);
    expect((float) $recipe->sections[0]->ingredients->first()->pivot->quantity)->toBe(3.0);
});

it('allows the same ingredient in multiple sections', function () {
    $user = User::factory()->create();
    $butter = Ingredient::factory()->for($user)->create(['name' => 'Butter']);

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Butter Everywhere',
        'servings' => 4,
        'flavor_profile' => 'Rich',
        'sections' => [
            [
                'name' => 'Crust',
                'sort_order' => 0,
                'ingredients' => [
                    ['ingredient_id' => $butter->id, 'quantity' => '1/2', 'unit' => 'cup'],
                ],
            ],
            [
                'name' => 'Filling',
                'sort_order' => 1,
                'ingredients' => [
                    ['ingredient_id' => $butter->id, 'quantity' => '1/4', 'unit' => 'cup'],
                ],
            ],
        ],
    ]);

    $recipe = Recipe::query()->where('name', 'Butter Everywhere')->first();
    $response->assertRedirect(route('recipes.show', $recipe));

    $pivotRows = DB::table('ingredient_recipe')
        ->where('recipe_id', $recipe->id)
        ->where('ingredient_id', $butter->id)
        ->get();

    expect($pivotRows)->toHaveCount(2);
    expect((float) $pivotRows[0]->quantity)->toBe(0.5);
    expect((float) $pivotRows[1]->quantity)->toBe(0.25);
});

it('rejects sending both sections and ingredients', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->for($user)->create();

    $response = $this->actingAs($user)->postJson(route('recipes.store'), [
        'name' => 'Conflict',
        'instructions' => '<p>Steps.</p>',
        'servings' => 2,
        'flavor_profile' => 'Test',
        'ingredients' => [
            ['ingredient_id' => $ingredient->id, 'quantity' => '1', 'unit' => 'cup'],
        ],
        'sections' => [
            ['name' => 'Part 1', 'sort_order' => 0],
        ],
    ]);

    $response->assertUnprocessable();
});

it('requires section name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('recipes.store'), [
        'name' => 'Missing Section Name',
        'servings' => 2,
        'flavor_profile' => 'Test',
        'sections' => [
            ['sort_order' => 0, 'instructions' => 'Some text'],
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['sections.0.name']);
});

it('cascades section deletion when recipe is deleted', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->for($user)->create();

    $recipe = Recipe::factory()->for($user)->create();
    $section = RecipeSection::factory()->for($recipe)->create();

    DB::table('ingredient_recipe')->insert([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'recipe_section_id' => $section->id,
        'quantity' => 1,
        'unit' => 'cup',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)->delete(route('recipes.destroy', $recipe));

    expect(RecipeSection::query()->where('recipe_id', $recipe->id)->exists())->toBeFalse();
    expect(DB::table('ingredient_recipe')->where('recipe_id', $recipe->id)->exists())->toBeFalse();
});

it('loads sections on show page', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->for($user)->create();

    $recipe = Recipe::factory()->for($user)->create();
    $section = RecipeSection::factory()->for($recipe)->create(['name' => 'Sauce', 'sort_order' => 0]);

    DB::table('ingredient_recipe')->insert([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'recipe_section_id' => $section->id,
        'quantity' => 2,
        'unit' => 'tbsp',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('recipes/Show')
        ->has('recipe.data.sections', 1)
        ->where('recipe.data.sections.0.name', 'Sauce')
        ->has('recipe.data.sections.0.ingredients', 1)
    );
});

it('loads sections on edit page', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create();
    RecipeSection::factory()->for($recipe)->create(['name' => 'Base', 'sort_order' => 0]);

    $response = $this->actingAs($user)->get(route('recipes.edit', $recipe));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('recipes/Edit')
        ->has('recipe.data.sections', 1)
        ->where('recipe.data.sections.0.name', 'Base')
    );
});
