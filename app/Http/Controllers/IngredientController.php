<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOwnership;
use App\Http\Requests\Ingredients\StoreIngredientRequest;
use App\Http\Requests\Ingredients\UpdateIngredientRequest;
use App\Http\Resources\GroceryStoreResource;
use App\Http\Resources\IngredientResource;
use App\Models\GroceryStore;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class IngredientController extends Controller
{
    use EnsuresOwnership;

    public function index(Request $request): InertiaResponse
    {
        $ingredients = Ingredient::query()
            ->currentUser()
            ->with(['groceryStore', 'groceryStoreSection'])
            ->orderBy('name')
            ->paginate();

        return Inertia::render('ingredients/Index', [
            'ingredients' => IngredientResource::collection($ingredients),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $groceryStores = GroceryStore::query()
            ->currentUser()
            ->with('sections')
            ->orderBy('name')
            ->get();

        return Inertia::render('ingredients/Create', [
            'groceryStores' => GroceryStoreResource::collection($groceryStores),
        ]);
    }

    public function store(StoreIngredientRequest $request): RedirectResponse
    {
        $ingredient = $request->user()->ingredients()->create($request->validated());

        return redirect()->route('ingredients.show', $ingredient);
    }

    public function storeQuick(StoreIngredientRequest $request): JsonResponse
    {
        $ingredient = $request->user()->ingredients()->create($request->validated());

        return response()->json([
            'ingredient' => IngredientResource::make($ingredient),
        ], 201);
    }

    public function show(Request $request, Ingredient $ingredient): InertiaResponse
    {
        $this->ensureOwnership($request, $ingredient);

        $ingredient->load(['groceryStore', 'groceryStoreSection']);

        return Inertia::render('ingredients/Show', [
            'ingredient' => IngredientResource::make($ingredient),
        ]);
    }

    public function edit(Request $request, Ingredient $ingredient): InertiaResponse
    {
        $this->ensureOwnership($request, $ingredient);

        $ingredient->load(['groceryStore', 'groceryStoreSection']);

        $groceryStores = GroceryStore::query()
            ->currentUser()
            ->with('sections')
            ->orderBy('name')
            ->get();

        return Inertia::render('ingredients/Edit', [
            'ingredient' => IngredientResource::make($ingredient),
            'groceryStores' => GroceryStoreResource::collection($groceryStores),
        ]);
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $this->ensureOwnership($request, $ingredient);

        $ingredient->update($request->validated());

        return redirect()->route('ingredients.show', $ingredient);
    }

    public function destroy(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $this->ensureOwnership($request, $ingredient);

        $ingredient->delete();

        return redirect()->route('ingredients.index');
    }
}
