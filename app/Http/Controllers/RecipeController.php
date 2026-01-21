<?php

namespace App\Http\Controllers;

use App\Http\Requests\Recipes\StoreRecipeRequest;
use App\Http\Requests\Recipes\UpdateRecipeRequest;
use App\Http\Resources\GroceryStoreResource;
use App\Http\Resources\IngredientResource;
use App\Http\Resources\RecipeResource;
use App\Models\GroceryStore;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class RecipeController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $recipes = Recipe::query()
            ->where('user_id', $request->user()->id)
            ->with('ingredients')
            ->latest()
            ->paginate();

        return Inertia::render('recipes/Index', [
            'recipes' => RecipeResource::collection($recipes),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $ingredients = Ingredient::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        $groceryStores = GroceryStore::query()
            ->where('user_id', $request->user()->id)
            ->with('sections')
            ->orderBy('name')
            ->get();

        return Inertia::render('recipes/Create', [
            'ingredients' => IngredientResource::collection($ingredients),
            'groceryStores' => GroceryStoreResource::collection($groceryStores),
        ]);
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $ingredients = $data['ingredients'] ?? [];

        $recipe = $request->user()->recipes()->create(
            Arr::except($data, ['ingredients', 'photo'])
        );

        if ($request->hasFile('photo')) {
            $recipe->photo_path = $this->storePhoto($request->file('photo'));
            $recipe->save();
        }

        if ($ingredients !== []) {
            $recipe->ingredients()->sync($this->formatIngredients($ingredients));
        }

        return redirect()->route('recipes.show', $recipe);
    }

    public function show(Request $request, Recipe $recipe): InertiaResponse
    {
        $this->ensureRecipeOwner($request, $recipe);

        return Inertia::render('recipes/Show', [
            'recipe' => RecipeResource::make($recipe->load('ingredients')),
        ]);
    }

    public function edit(Request $request, Recipe $recipe): InertiaResponse
    {
        $this->ensureRecipeOwner($request, $recipe);

        $ingredients = Ingredient::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        $groceryStores = GroceryStore::query()
            ->where('user_id', $request->user()->id)
            ->with('sections')
            ->orderBy('name')
            ->get();

        return Inertia::render('recipes/Edit', [
            'recipe' => RecipeResource::make($recipe->load('ingredients')),
            'ingredients' => IngredientResource::collection($ingredients),
            'groceryStores' => GroceryStoreResource::collection($groceryStores),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $this->ensureRecipeOwner($request, $recipe);

        $data = $request->validated();
        $shouldSyncIngredients = array_key_exists('ingredients', $data);

        $recipe->fill(Arr::except($data, ['ingredients', 'photo']));

        if ($request->hasFile('photo')) {
            $this->deletePhoto($recipe);
            $recipe->photo_path = $this->storePhoto($request->file('photo'));
        }

        $recipe->save();

        if ($shouldSyncIngredients) {
            $recipe->ingredients()->sync($this->formatIngredients($data['ingredients'] ?? []));
        }

        return redirect()->route('recipes.show', $recipe);
    }

    public function destroy(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->ensureRecipeOwner($request, $recipe);
        $this->deletePhoto($recipe);

        $recipe->delete();

        return redirect()->route('recipes.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $ingredients
     * @return array<int, array<string, mixed>>
     */
    protected function formatIngredients(array $ingredients): array
    {
        return collect($ingredients)
            ->mapWithKeys(function (array $ingredient): array {
                return [
                    $ingredient['ingredient_id'] => [
                        'quantity' => $ingredient['quantity'],
                        'unit' => $ingredient['unit'],
                        'note' => $ingredient['note'] ?? null,
                    ],
                ];
            })
            ->all();
    }

    protected function storePhoto(UploadedFile $photo): string
    {
        $image = Image::load($photo->getPathname());
        $size = min(2048, $image->getWidth(), $image->getHeight());
        $format = strtolower($photo->getClientOriginalExtension()) ?: 'jpg';

        $image->fit(Fit::Crop, $size, $size)->format($format)->save();

        return Storage::disk($this->photoDisk())->putFile(
            $this->photoDirectory(),
            $photo,
            ['visibility' => 'public']
        );
    }

    protected function deletePhoto(Recipe $recipe): void
    {
        if (! $recipe->photo_path) {
            return;
        }

        Storage::disk($this->photoDisk())->delete($recipe->photo_path);
    }

    protected function photoDisk(): string
    {
        return 'public';
    }

    protected function photoDirectory(): string
    {
        return 'recipes';
    }

    protected function ensureRecipeOwner(Request $request, Recipe $recipe): void
    {
        if ($recipe->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
