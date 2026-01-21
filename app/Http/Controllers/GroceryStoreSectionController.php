<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroceryStoreSections\StoreGroceryStoreSectionRequest;
use App\Http\Requests\GroceryStoreSections\UpdateGroceryStoreSectionRequest;
use App\Http\Resources\GroceryStoreSectionResource;
use App\Models\GroceryStore;
use App\Models\GroceryStoreSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroceryStoreSectionController extends Controller
{
    public function store(StoreGroceryStoreSectionRequest $request, GroceryStore $groceryStore): RedirectResponse
    {
        $this->ensureStoreOwner($request, $groceryStore);

        $maxOrder = $groceryStore->sections()->max('sort_order') ?? 0;

        $groceryStore->sections()->create([
            ...$request->validated(),
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('grocery-stores.show', $groceryStore);
    }

    public function storeQuick(StoreGroceryStoreSectionRequest $request, GroceryStore $groceryStore): JsonResponse
    {
        $this->ensureStoreOwner($request, $groceryStore);

        $maxOrder = $groceryStore->sections()->max('sort_order') ?? 0;

        $section = $groceryStore->sections()->create([
            ...$request->validated(),
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'section' => GroceryStoreSectionResource::make($section),
        ], 201);
    }

    public function update(UpdateGroceryStoreSectionRequest $request, GroceryStore $groceryStore, GroceryStoreSection $section): RedirectResponse
    {
        $this->ensureStoreOwner($request, $groceryStore);
        $this->ensureSectionBelongsToStore($groceryStore, $section);

        $section->update($request->validated());

        return redirect()->route('grocery-stores.show', $groceryStore);
    }

    public function destroy(Request $request, GroceryStore $groceryStore, GroceryStoreSection $section): RedirectResponse
    {
        $this->ensureStoreOwner($request, $groceryStore);
        $this->ensureSectionBelongsToStore($groceryStore, $section);

        $section->delete();

        return redirect()->route('grocery-stores.show', $groceryStore);
    }

    protected function ensureStoreOwner(Request $request, GroceryStore $groceryStore): void
    {
        if ($groceryStore->user_id !== $request->user()->id) {
            abort(404);
        }
    }

    protected function ensureSectionBelongsToStore(GroceryStore $groceryStore, GroceryStoreSection $section): void
    {
        if ($section->grocery_store_id !== $groceryStore->id) {
            abort(404);
        }
    }
}
