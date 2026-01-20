<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShoppingListItems\StoreShoppingListItemRequest;
use App\Http\Requests\ShoppingListItems\UpdateShoppingListItemRequest;
use App\Http\Resources\IngredientResource;
use App\Http\Resources\ShoppingListItemResource;
use App\Http\Resources\ShoppingListResource;
use App\Models\Ingredient;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ShoppingListItemController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $items = ShoppingListItem::query()
            ->whereHas('shoppingList', function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id);
            })
            ->with('ingredient')
            ->orderByRaw('sort_order is null, sort_order')
            ->paginate();

        return Inertia::render('shopping-list-items/Index', [
            'items' => ShoppingListItemResource::collection($items),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $shoppingLists = ShoppingList::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        $ingredients = Ingredient::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('shopping-list-items/Create', [
            'shoppingLists' => ShoppingListResource::collection($shoppingLists),
            'ingredients' => IngredientResource::collection($ingredients),
        ]);
    }

    public function store(StoreShoppingListItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $shoppingList = $this->resolveShoppingList($request, $data['shopping_list_id']);

        $item = $shoppingList->items()->create(Arr::except($data, ['shopping_list_id']));

        return redirect()->route('shopping-list-items.show', $item);
    }

    public function show(Request $request, ShoppingListItem $shoppingListItem): InertiaResponse
    {
        $this->ensureShoppingListItemOwner($request, $shoppingListItem);

        return Inertia::render('shopping-list-items/Show', [
            'item' => ShoppingListItemResource::make($shoppingListItem->load('ingredient')),
        ]);
    }

    public function edit(Request $request, ShoppingListItem $shoppingListItem): InertiaResponse
    {
        $this->ensureShoppingListItemOwner($request, $shoppingListItem);

        $shoppingLists = ShoppingList::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        $ingredients = Ingredient::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('shopping-list-items/Edit', [
            'item' => ShoppingListItemResource::make($shoppingListItem->load('ingredient')),
            'shoppingLists' => ShoppingListResource::collection($shoppingLists),
            'ingredients' => IngredientResource::collection($ingredients),
        ]);
    }

    public function update(
        UpdateShoppingListItemRequest $request,
        ShoppingListItem $shoppingListItem
    ): RedirectResponse {
        $this->ensureShoppingListItemOwner($request, $shoppingListItem);

        $data = $request->validated();

        if (array_key_exists('shopping_list_id', $data)) {
            $shoppingList = $this->resolveShoppingList($request, $data['shopping_list_id']);
            $shoppingListItem->shopping_list_id = $shoppingList->id;
        }

        $shoppingListItem->fill(Arr::except($data, ['shopping_list_id']));
        $shoppingListItem->save();

        return redirect()->route('shopping-list-items.show', $shoppingListItem);
    }

    public function destroy(Request $request, ShoppingListItem $shoppingListItem): RedirectResponse
    {
        $this->ensureShoppingListItemOwner($request, $shoppingListItem);

        $shoppingListItem->delete();

        return redirect()->route('shopping-list-items.index');
    }

    protected function resolveShoppingList(Request $request, int $shoppingListId): ShoppingList
    {
        return ShoppingList::query()
            ->whereKey($shoppingListId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    protected function ensureShoppingListItemOwner(Request $request, ShoppingListItem $shoppingListItem): void
    {
        $shoppingListItem->loadMissing('shoppingList');

        if ($shoppingListItem->shoppingList->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
