<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroceryStores\StoreGroceryStoreRequest;
use App\Http\Requests\GroceryStores\UpdateGroceryStoreRequest;
use App\Http\Resources\GroceryStoreResource;
use App\Models\GroceryStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GroceryStoreController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $stores = GroceryStore::query()
            ->where('user_id', $request->user()->id)
            ->withCount('sections')
            ->orderBy('name')
            ->paginate();

        return Inertia::render('grocery-stores/Index', [
            'groceryStores' => GroceryStoreResource::collection($stores),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('grocery-stores/Create');
    }

    public function store(StoreGroceryStoreRequest $request): RedirectResponse
    {
        $store = $request->user()->groceryStores()->create($request->validated());

        return redirect()->route('grocery-stores.show', $store);
    }

    public function storeQuick(StoreGroceryStoreRequest $request): JsonResponse
    {
        $store = $request->user()->groceryStores()->create([
            'name' => $request->validated('name'),
        ]);

        // Create sections if provided
        $sections = $request->input('sections', []);
        $sortOrder = 1;
        foreach ($sections as $sectionName) {
            if (is_string($sectionName) && trim($sectionName) !== '') {
                $store->sections()->create([
                    'name' => trim($sectionName),
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $store->load('sections');

        return response()->json([
            'grocery_store' => GroceryStoreResource::make($store),
        ], 201);
    }

    public function show(Request $request, GroceryStore $groceryStore): InertiaResponse
    {
        $this->ensureStoreOwner($request, $groceryStore);

        $groceryStore->load('sections');

        return Inertia::render('grocery-stores/Show', [
            'groceryStore' => GroceryStoreResource::make($groceryStore),
        ]);
    }

    public function edit(Request $request, GroceryStore $groceryStore): InertiaResponse
    {
        $this->ensureStoreOwner($request, $groceryStore);

        return Inertia::render('grocery-stores/Edit', [
            'groceryStore' => GroceryStoreResource::make($groceryStore),
        ]);
    }

    public function update(UpdateGroceryStoreRequest $request, GroceryStore $groceryStore): RedirectResponse
    {
        $this->ensureStoreOwner($request, $groceryStore);

        $groceryStore->update($request->validated());

        return redirect()->route('grocery-stores.show', $groceryStore);
    }

    public function destroy(Request $request, GroceryStore $groceryStore): RedirectResponse
    {
        $this->ensureStoreOwner($request, $groceryStore);

        $groceryStore->delete();

        return redirect()->route('grocery-stores.index');
    }

    protected function ensureStoreOwner(Request $request, GroceryStore $groceryStore): void
    {
        if ($groceryStore->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
