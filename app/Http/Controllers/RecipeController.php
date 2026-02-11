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
use Illuminate\Support\Facades\DB;
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

        $recipe = $request->user()->recipes()->create(
            Arr::except($data, ['ingredients', 'sections', 'photo', 'photo_url'])
        );

        if ($request->hasFile('photo')) {
            $recipe->photo_path = $this->storePhoto($request->file('photo'));
            $recipe->save();
        } elseif ($request->filled('photo_url')) {
            try {
                $recipe->photo_path = $this->storePhotoFromUrl($request->input('photo_url'));
                $recipe->save();
            } catch (\Illuminate\Http\Client\RequestException $e) {
                report($e);
            }
        }

        if (! empty($data['sections'])) {
            $this->syncSections($recipe, $data['sections']);
        } elseif (! empty($data['ingredients'])) {
            $this->syncFlatIngredients($recipe, $data['ingredients']);
        }

        return redirect()->route('recipes.show', $recipe);
    }

    public function show(Request $request, Recipe $recipe): InertiaResponse
    {
        $this->ensureOwnership($request, $recipe);

        return Inertia::render('recipes/Show', [
            'recipe' => RecipeResource::make($recipe->load(['ingredients', 'sections.ingredients'])),
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
            'recipe' => RecipeResource::make($recipe->load(['ingredients', 'sections.ingredients'])),
            'ingredients' => IngredientResource::collection($ingredients),
            'groceryStores' => GroceryStoreResource::collection($groceryStores),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $this->ensureOwnership($request, $recipe);

        $data = $request->validated();
        $hasSections = array_key_exists('sections', $data);
        $hasIngredients = array_key_exists('ingredients', $data);

        $recipe->fill(Arr::except($data, ['ingredients', 'sections', 'photo', 'photo_url']));

        if ($request->hasFile('photo')) {
            $this->deletePhoto($recipe);
            $recipe->photo_path = $this->storePhoto($request->file('photo'));
        } elseif ($request->filled('photo_url')) {
            try {
                $this->deletePhoto($recipe);
                $recipe->photo_path = $this->storePhotoFromUrl($request->input('photo_url'));
            } catch (\Illuminate\Http\Client\RequestException $e) {
                report($e);
            }
        }

        $recipe->save();

        if ($hasSections) {
            // Switching to sections: clear any flat ingredients first
            DB::table('ingredient_recipe')
                ->where('recipe_id', $recipe->id)
                ->whereNull('recipe_section_id')
                ->delete();

            $this->syncSections($recipe, $data['sections']);
        } elseif ($hasIngredients) {
            // Switching to flat: delete sections (cascade clears section ingredients via nullOnDelete,
            // then we clear those orphaned pivot rows too)
            $recipe->sections()->delete();
            DB::table('ingredient_recipe')
                ->where('recipe_id', $recipe->id)
                ->delete();

            $this->syncFlatIngredients($recipe, $data['ingredients']);
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
     */
    protected function syncFlatIngredients(Recipe $recipe, array $ingredients): void
    {
        DB::table('ingredient_recipe')
            ->where('recipe_id', $recipe->id)
            ->delete();

        if ($ingredients === []) {
            return;
        }

        $now = now();

        $rows = array_map(fn (array $ingredient) => [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient['ingredient_id'],
            'recipe_section_id' => null,
            'quantity' => FractionConverter::toDecimal((string) $ingredient['quantity']),
            'unit' => $ingredient['unit'],
            'note' => $ingredient['note'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $ingredients);

        DB::table('ingredient_recipe')->insert($rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    protected function syncSections(Recipe $recipe, array $sections): void
    {
        // Delete existing sections (cascades via FK nullOnDelete on pivot rows)
        $recipe->sections()->delete();

        // Clean up any orphaned pivot rows with null recipe_section_id
        DB::table('ingredient_recipe')
            ->where('recipe_id', $recipe->id)
            ->delete();

        $now = now();

        foreach ($sections as $sectionData) {
            $section = $recipe->sections()->create([
                'name' => $sectionData['name'],
                'sort_order' => $sectionData['sort_order'],
                'instructions' => $sectionData['instructions'] ?? null,
            ]);

            $sectionIngredients = $sectionData['ingredients'] ?? [];

            if ($sectionIngredients !== []) {
                $rows = array_map(fn (array $ingredient) => [
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ingredient['ingredient_id'],
                    'recipe_section_id' => $section->id,
                    'quantity' => FractionConverter::toDecimal((string) $ingredient['quantity']),
                    'unit' => $ingredient['unit'],
                    'note' => $ingredient['note'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $sectionIngredients);

                DB::table('ingredient_recipe')->insert($rows);
            }
        }
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
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; MealPlannerBot/1.0)',
        ])->timeout(15)->get($url);
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
