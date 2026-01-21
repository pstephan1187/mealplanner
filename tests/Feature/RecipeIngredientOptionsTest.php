<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('provides ingredient options when editing a recipe', function () {
    $user = User::factory()->create();

    $recipe = Recipe::factory()->for($user)->create();

    Ingredient::factory()->for($user)->count(3)->create();

    $this->actingAs($user)
        ->get("/recipes/{$recipe->id}/edit")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('recipes/Edit')
            ->has('recipe.data')
            ->has('ingredients.data', 3)
            ->has('ingredients.data.0.id')
            ->has('ingredients.data.0.name')
            ->has('groceryStores.data')
        );
});
