<?php

use App\Mail\ShareShoppingListMail;
use App\Models\MealPlan;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

// ── Public shared view ──────────────────────────────────────────────────

it('renders the shared shopping list page for a valid token', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->shared()->create();

    ShoppingListItem::factory()->for($shoppingList)->count(3)->create();

    $response = $this->get(route('shared.shopping-list.show', $shoppingList->share_token));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('shopping-lists/Shared')
        ->has('shoppingList.data')
        ->has('shoppingList.data.items', 3)
        ->where('shareToken', $shoppingList->share_token)
    );
});

it('returns 404 for an invalid share token', function () {
    $response = $this->get(route('shared.shopping-list.show', 'nonexistent-token'));

    $response->assertNotFound();
});

it('returns 404 for a list without a share token', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    ShoppingList::factory()->for($user)->for($mealPlan)->create(['share_token' => null]);

    $response = $this->get('/shared/shopping-list/some-random-uuid');

    $response->assertNotFound();
});

// ── Public toggle item ──────────────────────────────────────────────────

it('toggles an item on a shared list', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->shared()->create();
    $item = ShoppingListItem::factory()->for($shoppingList)->create(['is_purchased' => false]);

    $response = $this->patch(route('shared.shopping-list.toggle-item', [
        'shareToken' => $shoppingList->share_token,
        'shoppingListItem' => $item->id,
    ]));

    $response->assertRedirect();
    expect($item->refresh()->is_purchased)->toBeTrue();

    // Toggle back
    $this->patch(route('shared.shopping-list.toggle-item', [
        'shareToken' => $shoppingList->share_token,
        'shoppingListItem' => $item->id,
    ]));

    expect($item->refresh()->is_purchased)->toBeFalse();
});

it('rejects toggling an item from a different list', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->shared()->create();
    $otherList = ShoppingList::factory()->for($user)->create();
    $item = ShoppingListItem::factory()->for($otherList)->create();

    $response = $this->patch(route('shared.shopping-list.toggle-item', [
        'shareToken' => $shoppingList->share_token,
        'shoppingListItem' => $item->id,
    ]));

    $response->assertNotFound();
});

it('rejects toggling with an invalid token', function () {
    $item = ShoppingListItem::factory()->create();

    $response = $this->patch(route('shared.shopping-list.toggle-item', [
        'shareToken' => 'bad-token',
        'shoppingListItem' => $item->id,
    ]));

    $response->assertNotFound();
});

// ── Enable sharing ──────────────────────────────────────────────────────

it('enables sharing for the list owner', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create();

    expect($shoppingList->share_token)->toBeNull();

    $response = $this->actingAs($user)->post(route('shopping-lists.share.enable', $shoppingList));

    $response->assertRedirect();
    expect($shoppingList->refresh()->share_token)->not->toBeNull();
});

it('does not regenerate token if already shared', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->shared()->create();

    $originalToken = $shoppingList->share_token;

    $this->actingAs($user)->post(route('shopping-lists.share.enable', $shoppingList));

    expect($shoppingList->refresh()->share_token)->toBe($originalToken);
});

it('prevents enabling sharing for non-owners', function () {
    $otherUser = User::factory()->create();
    $shoppingList = ShoppingList::factory()->create();

    $response = $this->actingAs($otherUser)->post(route('shopping-lists.share.enable', $shoppingList));

    $response->assertNotFound();
});

// ── Disable sharing ─────────────────────────────────────────────────────

it('disables sharing for the list owner', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->shared()->create();

    expect($shoppingList->share_token)->not->toBeNull();

    $response = $this->actingAs($user)->delete(route('shopping-lists.share.disable', $shoppingList));

    $response->assertRedirect();
    expect($shoppingList->refresh()->share_token)->toBeNull();
});

it('prevents disabling sharing for non-owners', function () {
    $otherUser = User::factory()->create();
    $shoppingList = ShoppingList::factory()->shared()->create();

    $response = $this->actingAs($otherUser)->delete(route('shopping-lists.share.disable', $shoppingList));

    $response->assertNotFound();
});

// ── Share via email ─────────────────────────────────────────────────────

it('sends a share email and generates a token if needed', function () {
    Mail::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create(['share_token' => null]);

    $response = $this->actingAs($user)->postJson(route('shopping-lists.share.email', $shoppingList), [
        'email' => 'partner@example.com',
    ]);

    $response->assertRedirect();
    expect($shoppingList->refresh()->share_token)->not->toBeNull();

    Mail::assertSent(ShareShoppingListMail::class, function ($mail) {
        return $mail->hasTo('partner@example.com');
    });
});

it('validates the email field', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create();

    $response = $this->actingAs($user)->postJson(route('shopping-lists.share.email', $shoppingList), [
        'email' => 'not-an-email',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('email');
});

it('requires an email field', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $shoppingList = ShoppingList::factory()->for($user)->for($mealPlan)->create();

    $response = $this->actingAs($user)->postJson(route('shopping-lists.share.email', $shoppingList), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('email');
});

it('prevents sharing via email for non-owners', function () {
    $otherUser = User::factory()->create();
    $shoppingList = ShoppingList::factory()->create();

    $response = $this->actingAs($otherUser)->postJson(route('shopping-lists.share.email', $shoppingList), [
        'email' => 'partner@example.com',
    ]);

    $response->assertNotFound();
});
