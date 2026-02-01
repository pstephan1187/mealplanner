<?php

namespace App\Http\Controllers;

use App\Actions\ParseRecipeFromUrl;
use App\Http\Requests\Recipes\ImportRecipeRequest;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class RecipeImportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ImportRecipeRequest $request, ParseRecipeFromUrl $action): JsonResponse
    {
        Gate::authorize('can-import-recipes');

        try {
            $recipeData = $action($request->validated('url'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $recipeData['ingredients'] = $this->matchIngredients($recipeData['ingredients'] ?? []);

        return response()->json($recipeData);
    }

    /**
     * Match extracted ingredient names against the current user's existing ingredients.
     *
     * @param  array<int, array<string, mixed>>  $extractedIngredients
     * @return array<int, array<string, mixed>>
     */
    protected function matchIngredients(array $extractedIngredients): array
    {
        $userIngredients = Ingredient::query()
            ->currentUser()
            ->get()
            ->keyBy(fn (Ingredient $ingredient): string => strtolower($ingredient->name));

        return collect($extractedIngredients)
            ->map(function (array $ingredient) use ($userIngredients): array {
                $match = $userIngredients->get(strtolower($ingredient['name'] ?? ''));

                return [
                    'ingredient_id' => $match?->id,
                    'name' => $ingredient['name'] ?? null,
                    'quantity' => $ingredient['quantity'] ?? null,
                    'unit' => $ingredient['unit'] ?? null,
                    'note' => $ingredient['note'] ?? null,
                ];
            })
            ->all();
    }
}
