<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShoppingListResource;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SharedShoppingListController extends Controller
{
    public function show(string $shareToken): InertiaResponse
    {
        $shoppingList = ShoppingList::query()
            ->where('share_token', $shareToken)
            ->firstOrFail();

        return Inertia::render('shopping-lists/Shared', [
            'shoppingList' => ShoppingListResource::make($shoppingList->load([
                'items.ingredient.groceryStore',
                'items.ingredient.groceryStoreSection',
                'items.groceryStore',
                'items.groceryStoreSection',
                'mealPlan',
            ])),
            'shareToken' => $shareToken,
        ]);
    }

    public function toggleItem(Request $request, string $shareToken, ShoppingListItem $shoppingListItem): RedirectResponse
    {
        $shoppingList = ShoppingList::query()
            ->where('share_token', $shareToken)
            ->firstOrFail();

        if ($shoppingListItem->shopping_list_id !== $shoppingList->id) {
            abort(404);
        }

        $shoppingListItem->update([
            'is_purchased' => ! $shoppingListItem->is_purchased,
        ]);

        return back();
    }
}
