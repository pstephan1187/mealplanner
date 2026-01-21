<?php

use App\Models\GroceryStore;
use App\Models\GroceryStoreSection;
use App\Models\Ingredient;
use App\Models\User;

test('user can create ingredient without store', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('ingredients.store'), [
            'name' => 'Tomato',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    expect(Ingredient::where('name', 'Tomato')->exists())->toBeTrue();
});

test('user can create ingredient with grocery store', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('ingredients.store'), [
            'name' => 'Tomato',
            'grocery_store_id' => $store->id,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $ingredient = Ingredient::where('name', 'Tomato')->first();
    expect($ingredient->grocery_store_id)->toBe($store->id);
});

test('user can create ingredient with store and section', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    $section = GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('ingredients.store'), [
            'name' => 'Tomato',
            'grocery_store_id' => $store->id,
            'grocery_store_section_id' => $section->id,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $ingredient = Ingredient::where('name', 'Tomato')->first();
    expect($ingredient->grocery_store_id)->toBe($store->id);
    expect($ingredient->grocery_store_section_id)->toBe($section->id);
});

test('section cannot be set without store', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    $section = GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('ingredients.store'), [
            'name' => 'Tomato',
            'grocery_store_section_id' => $section->id,
        ]);

    $response->assertSessionHasErrors('grocery_store_section_id');
});

test('section must belong to selected store', function () {
    $user = User::factory()->create();
    $store1 = GroceryStore::factory()->create(['user_id' => $user->id]);
    $store2 = GroceryStore::factory()->create(['user_id' => $user->id]);
    $section = GroceryStoreSection::factory()->create(['grocery_store_id' => $store2->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('ingredients.store'), [
            'name' => 'Tomato',
            'grocery_store_id' => $store1->id,
            'grocery_store_section_id' => $section->id,
        ]);

    $response->assertSessionHasErrors('grocery_store_section_id');
});

test('user cannot use other user store for ingredient', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $otherUser->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('ingredients.store'), [
            'name' => 'Tomato',
            'grocery_store_id' => $store->id,
        ]);

    $response->assertSessionHasErrors('grocery_store_id');
});

test('user can update ingredient to add store', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->create(['user_id' => $user->id]);
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->patch(route('ingredients.update', $ingredient), [
            'name' => $ingredient->name,
            'grocery_store_id' => $store->id,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    expect($ingredient->refresh()->grocery_store_id)->toBe($store->id);
});

test('user can update ingredient to add store and section', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->create(['user_id' => $user->id]);
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    $section = GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id]);

    $response = $this
        ->actingAs($user)
        ->patch(route('ingredients.update', $ingredient), [
            'name' => $ingredient->name,
            'grocery_store_id' => $store->id,
            'grocery_store_section_id' => $section->id,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $ingredient->refresh();
    expect($ingredient->grocery_store_id)->toBe($store->id);
    expect($ingredient->grocery_store_section_id)->toBe($section->id);
});

test('user can update ingredient to remove store and section', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    $section = GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id]);
    $ingredient = Ingredient::factory()->create([
        'user_id' => $user->id,
        'grocery_store_id' => $store->id,
        'grocery_store_section_id' => $section->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('ingredients.update', $ingredient), [
            'name' => $ingredient->name,
            'grocery_store_id' => '',
            'grocery_store_section_id' => '',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $ingredient->refresh();
    expect($ingredient->grocery_store_id)->toBeNull();
    expect($ingredient->grocery_store_section_id)->toBeNull();
});

test('section validation applies on update too', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    $section = GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id]);
    $ingredient = Ingredient::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->patch(route('ingredients.update', $ingredient), [
            'name' => $ingredient->name,
            'grocery_store_section_id' => $section->id,
        ]);

    $response->assertSessionHasErrors('grocery_store_section_id');
});

test('deleting store nullifies ingredient store reference', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    $ingredient = Ingredient::factory()->create([
        'user_id' => $user->id,
        'grocery_store_id' => $store->id,
    ]);

    $store->delete();

    $ingredient->refresh();
    expect($ingredient->grocery_store_id)->toBeNull();
});

test('deleting section nullifies ingredient section reference', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    $section = GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id]);
    $ingredient = Ingredient::factory()->create([
        'user_id' => $user->id,
        'grocery_store_id' => $store->id,
        'grocery_store_section_id' => $section->id,
    ]);

    $section->delete();

    $ingredient->refresh();
    expect($ingredient->grocery_store_id)->toBe($store->id);
    expect($ingredient->grocery_store_section_id)->toBeNull();
});

test('ingredients index shows store and section info', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id, 'name' => 'Whole Foods']);
    $section = GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id, 'name' => 'Produce']);
    Ingredient::factory()->create([
        'user_id' => $user->id,
        'name' => 'Tomato',
        'grocery_store_id' => $store->id,
        'grocery_store_section_id' => $section->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('ingredients.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ingredients/Index')
        ->has('ingredients.data', 1)
    );
});

test('ingredient edit page receives grocery stores', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id]);
    $ingredient = Ingredient::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('ingredients.edit', $ingredient));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ingredients/Edit')
        ->has('ingredient')
        ->has('groceryStores', 1)
    );
});

test('ingredient create page receives grocery stores', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('ingredients.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ingredients/Create')
        ->has('groceryStores', 1)
    );
});
