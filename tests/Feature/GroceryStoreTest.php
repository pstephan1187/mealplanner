<?php

use App\Models\GroceryStore;
use App\Models\User;

test('grocery stores index displays stores', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id, 'name' => 'Whole Foods']);

    $response = $this
        ->actingAs($user)
        ->get(route('grocery-stores.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('grocery-stores/Index')
        ->has('groceryStores.data', 1)
    );
});

test('grocery stores index only shows own stores', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    GroceryStore::factory()->create(['user_id' => $user->id]);
    GroceryStore::factory()->create(['user_id' => $otherUser->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('grocery-stores.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('groceryStores.data', 1)
    );
});

test('user can create a grocery store', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('grocery-stores.store'), [
            'name' => 'Trader Joe\'s',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    expect(GroceryStore::where('name', 'Trader Joe\'s')->exists())->toBeTrue();
});

test('grocery store name is required', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('grocery-stores.store'), [
            'name' => '',
        ]);

    $response->assertSessionHasErrors('name');
});

test('grocery store name must be unique per user', function () {
    $user = User::factory()->create();
    GroceryStore::factory()->create(['user_id' => $user->id, 'name' => 'Whole Foods']);

    $response = $this
        ->actingAs($user)
        ->post(route('grocery-stores.store'), [
            'name' => 'Whole Foods',
        ]);

    $response->assertSessionHasErrors('name');
});

test('different users can have same store name', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    GroceryStore::factory()->create(['user_id' => $user1->id, 'name' => 'Whole Foods']);

    $response = $this
        ->actingAs($user2)
        ->post(route('grocery-stores.store'), [
            'name' => 'Whole Foods',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
});

test('user can view own grocery store', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('grocery-stores.show', $store));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('grocery-stores/Show')
        ->has('groceryStore')
    );
});

test('user cannot view other user grocery store', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $otherUser->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('grocery-stores.show', $store));

    $response->assertNotFound();
});

test('user can update own grocery store', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

    $response = $this
        ->actingAs($user)
        ->patch(route('grocery-stores.update', $store), [
            'name' => 'New Name',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    expect($store->refresh()->name)->toBe('New Name');
});

test('user cannot update other user grocery store', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $otherUser->id]);

    $response = $this
        ->actingAs($user)
        ->patch(route('grocery-stores.update', $store), [
            'name' => 'New Name',
        ]);

    $response->assertNotFound();
});

test('user can delete own grocery store', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('grocery-stores.destroy', $store));

    $response->assertRedirect(route('grocery-stores.index'));

    expect(GroceryStore::find($store->id))->toBeNull();
});

test('user cannot delete other user grocery store', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $store = GroceryStore::factory()->create(['user_id' => $otherUser->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('grocery-stores.destroy', $store));

    $response->assertNotFound();
});

test('unauthenticated users cannot access grocery stores', function () {
    $response = $this->get(route('grocery-stores.index'));
    $response->assertRedirect(route('login'));
});

test('user can quick create grocery store via json api', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('grocery-stores.store-quick'), [
            'name' => 'Quick Store',
        ]);

    $response->assertCreated();
    $response->assertJsonStructure([
        'grocery_store' => ['id', 'name', 'sections'],
    ]);
    $response->assertJsonPath('grocery_store.name', 'Quick Store');

    expect(GroceryStore::where('name', 'Quick Store')->exists())->toBeTrue();
});

test('user can quick create grocery store with sections', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('grocery-stores.store-quick'), [
            'name' => 'Store With Sections',
            'sections' => ['Produce', 'Dairy', 'Bakery'],
        ]);

    $response->assertCreated();
    $response->assertJsonPath('grocery_store.name', 'Store With Sections');
    $response->assertJsonCount(3, 'grocery_store.sections');

    $store = GroceryStore::where('name', 'Store With Sections')->first();
    expect($store->sections)->toHaveCount(3);
    expect($store->sections->pluck('name')->all())->toBe(['Produce', 'Dairy', 'Bakery']);
});

test('quick create ignores empty section names', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('grocery-stores.store-quick'), [
            'name' => 'Store',
            'sections' => ['Produce', '', '  ', 'Dairy'],
        ]);

    $response->assertCreated();
    $response->assertJsonCount(2, 'grocery_store.sections');
});

test('quick create validates store name is required', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('grocery-stores.store-quick'), [
            'name' => '',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
});
