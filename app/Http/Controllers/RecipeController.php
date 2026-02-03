<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOwnership;
use App\Http\Requests\Recipes\StoreRecipeRequest;
use App\Http\Requests\Recipes\UpdateRecipeRequest;
use App\Http\Resources\GroceryStoreResource;
use App\Http\Resources\IngredientResource;
use App\Http\Resources\RecipeResource;
use App\Models\GroceryStore;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Support\FractionConverter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class RecipeController extends Controller
{
    use EnsuresOwnership;

    public function index(Request $request): InertiaResponse
    {
        $recipes = Recipe::query()
            ->currentUser()
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
            ->currentUser()
            ->orderBy('name')
            ->get();

        $groceryStores = GroceryStore::query()
            ->currentUser()
            ->with('sections')
            ->orderBy('name')
            ->get();

        return Inertia::render('recipes/Create', [
            'ingredients' => IngredientResource::collection($ingredients),
            'groceryStores' => GroceryStoreResource::collection($groceryStores),
            'canImportRecipe' => Gate::allows('can-import-recipes'),
        ]);
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $ingredients = $data['ingredients'] ?? [];

        $recipe = $request->user()->recipes()->create(
            Arr::except($data, ['ingredients', 'photo', 'photo_url'])
        );

        if ($request->hasFile('photo')) {
            $recipe->photo_path = $this->storePhoto($request->file('photo'));
            $recipe->save();
        } elseif ($request->filled('photo_url')) {
            $recipe->photo_path = $this->storePhotoFromUrl($request->input('photo_url'));
            $recipe->save();
        }

        if ($ingredients !== []) {
            $recipe->ingredients()->sync($this->formatIngredients($ingredients));
        }

        return redirect()->route('recipes.show', $recipe);
    }

    public function show(Request $request, Recipe $recipe): InertiaResponse
    {
        $this->ensureOwnership($request, $recipe);

        return Inertia::render('recipes/Show', [
            'recipe' => RecipeResource::make($recipe->load('ingredients')),
        ]);
    }

    public function edit(Request $request, Recipe $recipe): InertiaResponse
    {
        $this->ensureOwnership($request, $recipe);

        $ingredients = Ingredient::query()
            ->currentUser()
            ->orderBy('name')
            ->get();

        $groceryStores = GroceryStore::query()
            ->currentUser()
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
        $this->ensureOwnership($request, $recipe);

        $data = $request->validated();
        $shouldSyncIngredients = array_key_exists('ingredients', $data);

        $recipe->fill(Arr::except($data, ['ingredients', 'photo', 'photo_url']));

        if ($request->hasFile('photo')) {
            $this->deletePhoto($recipe);
            $recipe->photo_path = $this->storePhoto($request->file('photo'));
        } elseif ($request->filled('photo_url')) {
            $this->deletePhoto($recipe);
            $recipe->photo_path = $this->storePhotoFromUrl($request->input('photo_url'));
        }

        $recipe->save();

        if ($shouldSyncIngredients) {
            $recipe->ingredients()->sync($this->formatIngredients($data['ingredients'] ?? []));
        }

        return redirect()->route('recipes.show', $recipe);
    }

    public function destroy(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->ensureOwnership($request, $recipe);
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
                        'quantity' => FractionConverter::toDecimal((string) $ingredient['quantity']),
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

    protected function storePhotoFromUrl(string $url): string
    {
        $response = Http::get($url);
        $response->throw();

        $extension = Str::afterLast(parse_url($url, PHP_URL_PATH) ?? '', '.') ?: 'jpg';
        $extension = strtolower($extension);

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $extension = 'jpg';
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'recipe_').'.'.$extension;
        file_put_contents($tmpPath, $response->body());

        try {
            $image = Image::load($tmpPath);
            $size = min(2048, $image->getWidth(), $image->getHeight());
            $image->fit(Fit::Crop, $size, $size)->format($extension)->save();

            $filename = Str::random(40).'.'.$extension;

            Storage::disk($this->photoDisk())->putFileAs(
                $this->photoDirectory(),
                $tmpPath,
                $filename,
                ['visibility' => 'public']
            );

            return $this->photoDirectory().'/'.$filename;
        } finally {
            @unlink($tmpPath);
        }
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
}
