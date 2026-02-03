<?php

use App\Models\Recipe;
use App\Models\ShoppingListItem;
use App\Models\User;

it('links recipes to a user', function () {
    $recipe = Recipe::factory()->create();

    expect($recipe->user)->toBeInstanceOf(User::class);
});

it('stores shopping list item ordering', function () {
    $item = ShoppingListItem::factory()->create([
        'sort_order' => 3,
    ]);

    expect($item->sort_order)->toBe(3);
});
