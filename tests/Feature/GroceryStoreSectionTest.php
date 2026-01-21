<?php

use App\Models\GroceryStore;
use App\Models\GroceryStoreSection;
use App\Models\User;

test('user can add a section to their store', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('grocery-stores.sections.store', $store), [
            'name' => 'Produce',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('grocery-stores.show', $store));

    expect(GroceryStoreSection::where('name', 'Produce')->exists())->toBeTrue();
});

test('section name is required', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('grocery-stores.sections.store', $store), [
            'name' => '',
        ]);

    $response->assertSessionHasErrors('name');
});

test('section name must be unique within store', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id, 'name' => 'Produce']);

    $response = $this
        ->actingAs($user)
        ->post(route('grocery-stores.sections.store', $store), [
            'name' => 'Produce',
        ]);

    $response->assertSessionHasErrors('name');
});

test('same section name can exist in different stores', function () {
    $user = User::factory()->create();
    $store1 = GroceryStore::factory()->create(['user_id' => $user->id]);
    $store2 = GroceryStore::factory()->create(['user_id' => $user->id]);
    GroceryStoreSection::factory()->create(['grocery_store_id' => $store1->id, 'name' => 'Produce']);

    $response = $this
        ->actingAs($user)
        ->post(route('grocery-stores.sections.store', $store2), [
            'name' => 'Produce',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
});

test('user cannot add section to other user store', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $otherUser->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('grocery-stores.sections.store', $store), [
            'name' => 'Produce',
        ]);

    $response->assertNotFound();
});

test('user can update section in their store', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    $section = GroceryStoreSection::factory()->create([
        'grocery_store_id' => $store->id,
        'name' => 'Old Name',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('grocery-stores.sections.update', [$store, $section]), [
            'name' => 'New Name',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    expect($section->refresh()->name)->toBe('New Name');
});

test('user can delete section from their store', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    $section = GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('grocery-stores.sections.destroy', [$store, $section]));

    $response->assertRedirect(route('grocery-stores.show', $store));

    expect(GroceryStoreSection::find($section->id))->toBeNull();
});

test('user cannot delete section from other user store', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $otherUser->id]);
    $section = GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('grocery-stores.sections.destroy', [$store, $section]));

    $response->assertNotFound();
});

test('section sort order is auto-incremented', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('grocery-stores.sections.store', $store), ['name' => 'First']);

    $this->actingAs($user)
        ->post(route('grocery-stores.sections.store', $store), ['name' => 'Second']);

    $sections = $store->sections()->orderBy('sort_order')->get();

    expect($sections[0]->name)->toBe('First');
    expect($sections[0]->sort_order)->toBe(1);
    expect($sections[1]->name)->toBe('Second');
    expect($sections[1]->sort_order)->toBe(2);
});

test('user can quick create section via json api', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('grocery-stores.sections.store-quick', $store), [
            'name' => 'Quick Section',
        ]);

    $response->assertCreated();
    $response->assertJsonStructure([
        'section' => ['id', 'name', 'sort_order'],
    ]);
    $response->assertJsonPath('section.name', 'Quick Section');

    expect(GroceryStoreSection::where('name', 'Quick Section')->exists())->toBeTrue();
});

test('quick create section returns correct sort order', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);
    GroceryStoreSection::factory()->create(['grocery_store_id' => $store->id, 'sort_order' => 5]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('grocery-stores.sections.store-quick', $store), [
            'name' => 'New Section',
        ]);

    $response->assertCreated();
    $response->assertJsonPath('section.sort_order', 6);
});

test('user cannot quick create section in other user store', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $otherUser->id]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('grocery-stores.sections.store-quick', $store), [
            'name' => 'Section',
        ]);

    $response->assertNotFound();
});

test('quick create section validates name is required', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('grocery-stores.sections.store-quick', $store), [
            'name' => '',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
});
