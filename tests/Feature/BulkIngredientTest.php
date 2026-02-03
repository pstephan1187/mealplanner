<?php

use App\Models\GroceryStore;
use App\Models\GroceryStoreSection;
use App\Models\Ingredient;
use App\Models\User;

it('bulk creates ingredients', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => 'Olive Oil', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
            ['name' => 'Fresh Basil', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
        ],
    ]);

    $response->assertCreated();
    $response->assertJsonCount(2, 'ingredients');
    $response->assertJsonStructure([
        'ingredients' => [
            ['id', 'name'],
        ],
    ]);

    expect(Ingredient::where('user_id', $user->id)->count())->toBe(2);
    expect(Ingredient::where('name', 'Olive Oil')->where('user_id', $user->id)->exists())->toBeTrue();
    expect(Ingredient::where('name', 'Fresh Basil')->where('user_id', $user->id)->exists())->toBeTrue();
});

it('bulk creates ingredients with store and section', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->for($user)->create();
    $section = GroceryStoreSection::factory()->for($store)->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => 'Cilantro', 'grocery_store_id' => $store->id, 'grocery_store_section_id' => $section->id],
        ],
    ]);

    $response->assertCreated();

    $ingredient = Ingredient::where('name', 'Cilantro')->first();
    expect($ingredient->grocery_store_id)->toBe($store->id);
    expect($ingredient->grocery_store_section_id)->toBe($section->id);
});

it('validates ingredient names are required in bulk create', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => '', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

it('validates ingredient name uniqueness per user in bulk create', function () {
    $user = User::factory()->create();
    Ingredient::factory()->for($user)->create(['name' => 'Garlic']);

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => 'Garlic', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

it('validates ingredient names are unique within the same bulk request', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => 'Paprika', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
            ['name' => 'Paprika', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ingredients.1.name']);
});

it('requires at least one ingredient in bulk create', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ingredients']);
});

it('rejects unauthenticated bulk create', function () {
    $response = $this->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => 'Olive Oil', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
        ],
    ]);

    $response->assertUnauthorized();
});
