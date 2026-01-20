<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ingredients\StoreIngredientRequest;
use App\Http\Requests\Ingredients\UpdateIngredientRequest;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class IngredientController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $ingredients = Ingredient::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->paginate();

        return Inertia::render('ingredients/Index', [
            'ingredients' => IngredientResource::collection($ingredients),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('ingredients/Create');
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
        $this->ensureIngredientOwner($request, $ingredient);

        return Inertia::render('ingredients/Show', [
            'ingredient' => IngredientResource::make($ingredient),
        ]);
    }

    public function edit(Request $request, Ingredient $ingredient): InertiaResponse
    {
        $this->ensureIngredientOwner($request, $ingredient);

        return Inertia::render('ingredients/Edit', [
            'ingredient' => IngredientResource::make($ingredient),
        ]);
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $this->ensureIngredientOwner($request, $ingredient);

        $ingredient->update($request->validated());

        return redirect()->route('ingredients.show', $ingredient);
    }

    public function destroy(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $this->ensureIngredientOwner($request, $ingredient);

        $ingredient->delete();

        return redirect()->route('ingredients.index');
    }

    protected function ensureIngredientOwner(Request $request, Ingredient $ingredient): void
    {
        if ($ingredient->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
