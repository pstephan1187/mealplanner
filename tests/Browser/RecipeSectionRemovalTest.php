<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeSection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/hot'));
});

it('shows confirmation dialog when removing sections', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    $ingredient = Ingredient::factory()->for($user)->create(['name' => 'Butter']);

    $recipe = Recipe::factory()->for($user)->create([
        'name' => 'Sectioned Recipe',
        'servings' => 4,
    ]);

    $section = RecipeSection::factory()->for($recipe)->create([
        'name' => 'Sauce',
        'sort_order' => 0,
        'instructions' => '<p>Mix the sauce.</p>',
    ]);

    DB::table('ingredient_recipe')->insert([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'recipe_section_id' => $section->id,
        'quantity' => 1,
        'unit' => 'cup',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $page = visit('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard');

    $page->navigate("/recipes/{$recipe->id}/edit")
        ->assertSee('Recipe sections')
        ->click('Remove sections')
        ->assertSee('Remove sections?')
        ->assertSee('Section instructions will be merged')
        ->assertNoJavascriptErrors();
});

it('merges section instructions with headers when removing sections', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    $ingredient = Ingredient::factory()->for($user)->create(['name' => 'Flour']);

    $recipe = Recipe::factory()->for($user)->create([
        'name' => 'Multi Section Recipe',
        'servings' => 2,
    ]);

    $sauceSection = RecipeSection::factory()->for($recipe)->create([
        'name' => 'Sauce',
        'sort_order' => 0,
        'instructions' => '<p>Mix the sauce.</p>',
    ]);

    $doughSection = RecipeSection::factory()->for($recipe)->create([
        'name' => 'Dough',
        'sort_order' => 1,
        'instructions' => '<p>Knead the dough.</p>',
    ]);

    DB::table('ingredient_recipe')->insert([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'recipe_section_id' => $sauceSection->id,
        'quantity' => 1,
        'unit' => 'cup',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $page = visit('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard');

    $page->navigate("/recipes/{$recipe->id}/edit")
        ->click('Remove sections')
        ->click('@confirm-remove-sections')
        ->wait(500);

    // After confirming removal, section names should appear as headers in instructions
    $page->assertSee('Sauce')
        ->assertSee('Dough')
        ->assertSee('Mix the sauce')
        ->assertSee('Knead the dough')
        ->assertNoJavascriptErrors();

    // Submit the form and verify the database has merged instructions with headers
    $page->click('Save changes')
        ->wait(1000);

    $recipe->refresh();

    expect($recipe->instructions)->toContain('<h2>Sauce</h2>');
    expect($recipe->instructions)->toContain('<h2>Dough</h2>');
    expect($recipe->instructions)->toContain('Mix the sauce');
    expect($recipe->instructions)->toContain('Knead the dough');

    // Sections should be removed from the database
    expect($recipe->sections()->count())->toBe(0);
});

it('cancels section removal when clicking cancel', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    $recipe = Recipe::factory()->for($user)->create([
        'name' => 'Keep Sections Recipe',
        'servings' => 2,
    ]);

    RecipeSection::factory()->for($recipe)->create([
        'name' => 'Filling',
        'sort_order' => 0,
        'instructions' => '<p>Make the filling.</p>',
    ]);

    $page = visit('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard');

    $page->navigate("/recipes/{$recipe->id}/edit")
        ->click('Remove sections')
        ->assertSee('Remove sections?')
        ->click('@cancel-remove-sections')
        ->wait(300)
        ->assertSee('Recipe sections')
        ->assertNoJavascriptErrors();
});
