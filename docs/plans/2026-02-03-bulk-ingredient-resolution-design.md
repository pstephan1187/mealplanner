# Bulk Ingredient Resolution Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Improve recipe import UX by adding fuzzy ingredient matching and a bulk resolution modal so users can resolve all unmatched ingredients in one pass instead of one-by-one.

**Architecture:** Backend gets fuzzy matching (substring containment) in RecipeImportController and a new bulk-create endpoint on IngredientController. Frontend gets a new IngredientResolutionModal component mounted from RecipeForm, triggered by a "Resolve unmatched" button that appears after import.

**Tech Stack:** Laravel 12, Pest 4, Vue 3, Inertia v2, Tailwind v4, shadcn-vue components (Dialog, Combobox, Button, etc.)

---

### Task 1: Fuzzy Matching — Tests

**Files:**
- Modify: `tests/Feature/RecipeImportTest.php`

**Step 1: Write failing tests for fuzzy matching and suggestions field**

Add these tests to `tests/Feature/RecipeImportTest.php`:

```php
it('returns suggestions array in import response', function () {
    $user = User::factory()->create();

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Test Recipe',
            'instructions' => '<p>Test</p>',
            'servings' => 1,
            'flavor_profile' => null,
            'meal_types' => [],
            'prep_time_minutes' => null,
            'cook_time_minutes' => null,
            'ingredients' => [
                ['name' => 'Garlic', 'quantity' => '1', 'unit' => 'clove', 'note' => null],
            ],
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/recipe',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'ingredients' => [
            ['ingredient_id', 'name', 'quantity', 'unit', 'note', 'suggestions'],
        ],
    ]);
});

it('suggests fuzzy matches when imported name is contained in existing ingredient name', function () {
    $user = User::factory()->create();
    $evoo = Ingredient::factory()->for($user)->create(['name' => 'Extra Virgin Olive Oil']);

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Pasta',
            'instructions' => '<p>Cook</p>',
            'servings' => 2,
            'flavor_profile' => null,
            'meal_types' => [],
            'prep_time_minutes' => null,
            'cook_time_minutes' => null,
            'ingredients' => [
                ['name' => 'olive oil', 'quantity' => '2', 'unit' => 'tbsp', 'note' => null],
            ],
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/pasta',
    ]);

    $response->assertSuccessful();
    expect($response->json('ingredients.0.ingredient_id'))->toBeNull();
    expect($response->json('ingredients.0.suggestions'))->toHaveCount(1);
    expect($response->json('ingredients.0.suggestions.0.id'))->toBe($evoo->id);
    expect($response->json('ingredients.0.suggestions.0.name'))->toBe('Extra Virgin Olive Oil');
});

it('suggests fuzzy matches when existing ingredient name is contained in imported name', function () {
    $user = User::factory()->create();
    $chicken = Ingredient::factory()->for($user)->create(['name' => 'Chicken Breast']);

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Chicken Dinner',
            'instructions' => '<p>Cook</p>',
            'servings' => 2,
            'flavor_profile' => null,
            'meal_types' => [],
            'prep_time_minutes' => null,
            'cook_time_minutes' => null,
            'ingredients' => [
                ['name' => 'boneless skinless chicken breast', 'quantity' => '2', 'unit' => 'lb', 'note' => null],
            ],
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/chicken',
    ]);

    $response->assertSuccessful();
    expect($response->json('ingredients.0.ingredient_id'))->toBeNull();
    expect($response->json('ingredients.0.suggestions'))->toHaveCount(1);
    expect($response->json('ingredients.0.suggestions.0.id'))->toBe($chicken->id);
});

it('returns empty suggestions for exact matches', function () {
    $user = User::factory()->create();
    $garlic = Ingredient::factory()->for($user)->create(['name' => 'Garlic']);

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Garlic Bread',
            'instructions' => '<p>Cook</p>',
            'servings' => 2,
            'flavor_profile' => null,
            'meal_types' => [],
            'prep_time_minutes' => null,
            'cook_time_minutes' => null,
            'ingredients' => [
                ['name' => 'garlic', 'quantity' => '3', 'unit' => 'cloves', 'note' => null],
            ],
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/garlic-bread',
    ]);

    $response->assertSuccessful();
    expect($response->json('ingredients.0.ingredient_id'))->toBe($garlic->id);
    expect($response->json('ingredients.0.suggestions'))->toBeEmpty();
});

it('does not suggest fuzzy matches from other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Ingredient::factory()->for($otherUser)->create(['name' => 'Extra Virgin Olive Oil']);

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Pasta',
            'instructions' => '<p>Cook</p>',
            'servings' => 2,
            'flavor_profile' => null,
            'meal_types' => [],
            'prep_time_minutes' => null,
            'cook_time_minutes' => null,
            'ingredients' => [
                ['name' => 'olive oil', 'quantity' => '2', 'unit' => 'tbsp', 'note' => null],
            ],
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/pasta',
    ]);

    $response->assertSuccessful();
    expect($response->json('ingredients.0.suggestions'))->toBeEmpty();
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/RecipeImportTest.php --filter="suggestions|fuzzy"`
Expected: FAIL — `suggestions` key does not exist in response

**Step 3: Commit**

```
test: add failing tests for fuzzy ingredient matching and suggestions
```

---

### Task 2: Fuzzy Matching — Implementation

**Files:**
- Modify: `app/Http/Controllers/RecipeImportController.php`

**Step 1: Update `matchIngredients()` to add fuzzy matching and suggestions**

Replace the `matchIngredients` method in `RecipeImportController.php`:

```php
/**
 * Match extracted ingredient names against the current user's existing ingredients.
 *
 * Pass 1: Exact case-insensitive match sets ingredient_id.
 * Pass 2: Substring containment in both directions populates suggestions.
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
            $importedName = strtolower($ingredient['name'] ?? '');
            $match = $userIngredients->get($importedName);

            $suggestions = [];

            if (! $match && $importedName !== '') {
                $suggestions = $userIngredients
                    ->filter(function (Ingredient $existing) use ($importedName): bool {
                        $existingName = strtolower($existing->name);

                        return str_contains($existingName, $importedName)
                            || str_contains($importedName, $existingName);
                    })
                    ->map(fn (Ingredient $existing): array => [
                        'id' => $existing->id,
                        'name' => $existing->name,
                    ])
                    ->values()
                    ->all();
            }

            return [
                'ingredient_id' => $match?->id,
                'name' => $ingredient['name'] ?? null,
                'quantity' => $ingredient['quantity'] ?? null,
                'unit' => $ingredient['unit'] ?? null,
                'note' => $ingredient['note'] ?? null,
                'suggestions' => $suggestions,
            ];
        })
        ->all();
}
```

**Step 2: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/RecipeImportTest.php`
Expected: ALL PASS (including existing tests — they now receive `suggestions` key too)

**Step 3: Run pint**

Run: `vendor/bin/pint --dirty`

**Step 4: Commit**

```
feat: add fuzzy ingredient matching with suggestions to recipe import
```

---

### Task 3: Bulk Create Endpoint — Tests

**Files:**
- Create: `tests/Feature/BulkIngredientTest.php`

**Step 1: Write failing tests for the bulk create endpoint**

```php
<?php

use App\Models\GroceryStore;
use App\Models\GroceryStoreSection;
use App\Models\Ingredient;
use App\Models\User;

it('bulk creates ingredients', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => 'Olive Oil', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
            ['name' => 'Fresh Basil', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
        ],
    ]);

    $response->assertCreated();
    $response->assertJsonCount(2, 'ingredients');
    $response->assertJsonStructure([
        'ingredients' => [
            ['id', 'name'],
        ],
    ]);

    expect(Ingredient::where('user_id', $user->id)->count())->toBe(2);
    expect(Ingredient::where('name', 'Olive Oil')->where('user_id', $user->id)->exists())->toBeTrue();
    expect(Ingredient::where('name', 'Fresh Basil')->where('user_id', $user->id)->exists())->toBeTrue();
});

it('bulk creates ingredients with store and section', function () {
    $user = User::factory()->create();
    $store = GroceryStore::factory()->for($user)->create();
    $section = GroceryStoreSection::factory()->for($store)->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => 'Cilantro', 'grocery_store_id' => $store->id, 'grocery_store_section_id' => $section->id],
        ],
    ]);

    $response->assertCreated();

    $ingredient = Ingredient::where('name', 'Cilantro')->first();
    expect($ingredient->grocery_store_id)->toBe($store->id);
    expect($ingredient->grocery_store_section_id)->toBe($section->id);
});

it('validates ingredient names are required in bulk create', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => '', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

it('validates ingredient name uniqueness per user in bulk create', function () {
    $user = User::factory()->create();
    Ingredient::factory()->for($user)->create(['name' => 'Garlic']);

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => 'Garlic', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

it('validates ingredient names are unique within the same bulk request', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => 'Paprika', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
            ['name' => 'Paprika', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ingredients.1.name']);
});

it('requires at least one ingredient in bulk create', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ingredients']);
});

it('rejects unauthenticated bulk create', function () {
    $response = $this->postJson(route('ingredients.bulk-store'), [
        'ingredients' => [
            ['name' => 'Olive Oil', 'grocery_store_id' => null, 'grocery_store_section_id' => null],
        ],
    ]);

    $response->assertUnauthorized();
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/BulkIngredientTest.php`
Expected: FAIL — route `ingredients.bulk-store` not defined

**Step 3: Commit**

```
test: add failing tests for bulk ingredient creation endpoint
```

---

### Task 4: Bulk Create Endpoint — Implementation

**Files:**
- Create: `app/Http/Requests/Ingredients/BulkStoreIngredientRequest.php`
- Modify: `app/Http/Controllers/IngredientController.php`
- Modify: `routes/web.php`

**Step 1: Create the form request**

Run: `php artisan make:request Ingredients/BulkStoreIngredientRequest --no-interaction`

Then replace its contents:

```php
<?php

namespace App\Http\Requests\Ingredients;

use App\Models\GroceryStoreSection;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreIngredientRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ingredients', 'name')->where('user_id', $this->user()->id),
                function (string $attribute, mixed $value, Closure $fail): void {
                    // Check uniqueness within the batch itself
                    preg_match('/ingredients\.(\d+)\.name/', $attribute, $matches);
                    $currentIndex = (int) $matches[1];

                    $names = collect($this->input('ingredients', []))
                        ->pluck('name')
                        ->map(fn ($n) => strtolower(trim($n ?? '')));

                    $duplicates = $names->filter(fn ($n, $i) => $i !== $currentIndex && $n === strtolower(trim($value ?? '')));

                    if ($duplicates->isNotEmpty()) {
                        $fail('Duplicate ingredient name within this request.');
                    }
                },
            ],
            'ingredients.*.grocery_store_id' => [
                'nullable',
                Rule::exists('grocery_stores', 'id')->where('user_id', $this->user()->id),
            ],
            'ingredients.*.grocery_store_section_id' => [
                'nullable',
                'exists:grocery_store_sections,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    preg_match('/ingredients\.(\d+)\./', $attribute, $matches);
                    $index = $matches[1];
                    $storeId = $this->input("ingredients.{$index}.grocery_store_id");

                    if (! $storeId) {
                        $fail('A store must be selected to assign a section.');
                        return;
                    }

                    $section = GroceryStoreSection::find($value);
                    if ($section && $section->grocery_store_id !== (int) $storeId) {
                        $fail('The section must belong to the selected store.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $ingredients = collect($this->input('ingredients', []))->map(function (array $item): array {
            if (($item['grocery_store_id'] ?? '') === '') {
                $item['grocery_store_id'] = null;
            }
            if (($item['grocery_store_section_id'] ?? '') === '') {
                $item['grocery_store_section_id'] = null;
            }

            return $item;
        })->all();

        $this->merge(['ingredients' => $ingredients]);
    }
}
```

**Step 2: Add `bulkStore` method to `IngredientController`**

Add this method and the use statement for the new request to `app/Http/Controllers/IngredientController.php`:

```php
// Add to use statements:
use App\Http\Requests\Ingredients\BulkStoreIngredientRequest;

// Add method after storeQuick():
public function bulkStore(BulkStoreIngredientRequest $request): JsonResponse
{
    $ingredients = collect($request->validated('ingredients'))
        ->map(fn (array $data) => $request->user()->ingredients()->create($data));

    return response()->json([
        'ingredients' => $ingredients->map(fn (Ingredient $ingredient): array => [
            'id' => $ingredient->id,
            'name' => $ingredient->name,
        ])->values()->all(),
    ], 201);
}
```

**Step 3: Add the route**

Add to `routes/web.php` after the existing `ingredients/quick` route (line 31):

```php
Route::post('ingredients/bulk', [IngredientController::class, 'bulkStore'])
    ->name('ingredients.bulk-store');
```

**Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/BulkIngredientTest.php`
Expected: ALL PASS

**Step 5: Run pint**

Run: `vendor/bin/pint --dirty`

**Step 6: Commit**

```
feat: add bulk ingredient creation endpoint
```

---

### Task 5: Generate Wayfinder Types

**Step 1: Regenerate wayfinder after route changes**

Run: `php artisan wayfinder:generate`

This creates the TypeScript action for `bulkStore` at `resources/js/actions/App/Http/Controllers/IngredientController.ts`.

**Step 2: Commit**

```
chore: regenerate wayfinder types for bulk ingredient route
```

---

### Task 6: Update Frontend Types for Import Response

**Files:**
- Modify: `resources/js/pages/recipes/Create.vue`

**Step 1: Update the `ImportedIngredient` and `ImportResponse` interfaces**

In `Create.vue`, update the `ImportedIngredient` interface to include `suggestions`:

```typescript
interface ImportedIngredient {
    ingredient_id: number | null;
    name: string | null;
    quantity: string | number | null;
    unit: string | null;
    note: string | null;
    suggestions: Array<{ id: number; name: string }>;
}
```

**Step 2: Commit**

```
feat: update import response types to include suggestions
```

---

### Task 7: IngredientResolutionModal Component

**Files:**
- Create: `resources/js/components/IngredientResolutionModal.vue`

**Step 1: Create the modal component**

This is the largest piece. The component receives unmatched ingredients, lets the user resolve each one (match to existing or create new with optional store/section), then emits a `resolved` event.

```vue
<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Combobox, type ComboboxOption } from '@/components/ui/combobox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useStoreSelection } from '@/composables/useStoreSelection';
import { apiFetch } from '@/lib/utils';
import type { GroceryStore } from '@/types/models';

import { bulkStore } from '@/actions/App/Http/Controllers/IngredientController';

export interface UnmatchedIngredient {
    rowIndex: number;
    name: string;
    quantity: string | number | null;
    unit: string | null;
    suggestions: Array<{ id: number; name: string }>;
}

interface ResolvedIngredient {
    id: number;
    name: string;
}

type ResolutionMode = 'match' | 'create';

interface RowState {
    mode: ResolutionMode;
    matchedId: number | string | '';
    newName: string;
    storeId: number | string | '';
    sectionId: number | string | '';
}

const props = defineProps<{
    open: boolean;
    unmatchedIngredients: UnmatchedIngredient[];
    existingIngredients: ComboboxOption[];
    groceryStores: GroceryStore[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'resolved', map: Record<number, ResolvedIngredient>): void;
}>();

const saving = ref(false);
const saveError = ref('');

const localStores = ref<GroceryStore[]>([...props.groceryStores]);

const rows = ref<RowState[]>([]);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            rows.value = props.unmatchedIngredients.map((item) => ({
                mode: item.suggestions.length > 0 ? 'match' : 'create',
                matchedId: item.suggestions[0]?.id ?? '',
                newName: item.name,
                storeId: '',
                sectionId: '',
            }));
            saveError.value = '';
        }
    },
);

function storeOptionsFor(row: RowState) {
    return localStores.value.map((s) => ({ id: s.id, name: s.name }));
}

function sectionOptionsFor(row: RowState) {
    if (!row.storeId) return [];
    const store = localStores.value.find((s) => s.id === Number(row.storeId));
    return (store?.sections ?? []).map((s) => ({ id: s.id, name: s.name }));
}

const newCount = computed(
    () => rows.value.filter((r) => r.mode === 'create').length,
);
const matchedCount = computed(
    () => rows.value.filter((r) => r.mode === 'match' && r.matchedId !== '').length,
);

const canSave = computed(() => {
    return rows.value.every((row) => {
        if (row.mode === 'match') return row.matchedId !== '';
        return row.newName.trim() !== '';
    });
});

const saveLabel = computed(() => {
    const parts: string[] = [];
    if (newCount.value > 0) parts.push(`${newCount.value} new`);
    if (matchedCount.value > 0) parts.push(`${matchedCount.value} matched`);
    return parts.length > 0 ? `Save all (${parts.join(', ')})` : 'Save all';
});

async function handleSave() {
    saving.value = true;
    saveError.value = '';

    try {
        const resolutionMap: Record<number, ResolvedIngredient> = {};

        // Collect rows that need new ingredients created
        const toCreate: Array<{ index: number; data: RowState }> = [];
        rows.value.forEach((row, i) => {
            const original = props.unmatchedIngredients[i];
            if (row.mode === 'match' && row.matchedId !== '') {
                const matched = props.existingIngredients.find(
                    (ing) => ing.id === row.matchedId,
                );
                resolutionMap[original.rowIndex] = {
                    id: Number(row.matchedId),
                    name: matched?.name ?? '',
                };
            } else if (row.mode === 'create') {
                toCreate.push({ index: i, data: row });
            }
        });

        // Bulk create new ingredients if any
        if (toCreate.length > 0) {
            const response = await apiFetch(bulkStore.url(), {
                method: 'POST',
                body: JSON.stringify({
                    ingredients: toCreate.map(({ data }) => ({
                        name: data.newName.trim(),
                        grocery_store_id: data.storeId || null,
                        grocery_store_section_id: data.sectionId || null,
                    })),
                }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                saveError.value =
                    errorData.message || 'Failed to create ingredients.';
                return;
            }

            const created = await response.json();
            toCreate.forEach(({ index }, i) => {
                const original = props.unmatchedIngredients[index];
                resolutionMap[original.rowIndex] = {
                    id: created.ingredients[i].id,
                    name: created.ingredients[i].name,
                };
            });
        }

        emit('resolved', resolutionMap);
        emit('update:open', false);
    } catch {
        saveError.value = 'A network error occurred. Please try again.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Dialog
        :open="props.open"
        @update:open="(val) => emit('update:open', val)"
    >
        <DialogContent class="sm:max-w-2xl max-h-[85vh] flex flex-col">
            <DialogHeader>
                <DialogTitle>Resolve unmatched ingredients</DialogTitle>
                <DialogDescription>
                    Match imported ingredients to existing ones or create new
                    ones.
                </DialogDescription>
            </DialogHeader>

            <div class="flex-1 overflow-y-auto space-y-4 py-4">
                <div
                    v-for="(row, index) in rows"
                    :key="index"
                    class="rounded-lg border border-border p-4 space-y-3"
                >
                    <div class="flex items-baseline justify-between gap-4">
                        <div>
                            <p class="font-medium">
                                {{ unmatchedIngredients[index].name }}
                            </p>
                            <p
                                v-if="
                                    unmatchedIngredients[index].quantity ||
                                    unmatchedIngredients[index].unit
                                "
                                class="text-xs text-muted-foreground"
                            >
                                {{
                                    [
                                        unmatchedIngredients[index].quantity,
                                        unmatchedIngredients[index].unit,
                                    ]
                                        .filter(Boolean)
                                        .join(' ')
                                }}
                            </p>
                        </div>
                        <div class="flex gap-1 rounded-md bg-muted p-0.5">
                            <Button
                                type="button"
                                :variant="
                                    row.mode === 'match'
                                        ? 'secondary'
                                        : 'ghost'
                                "
                                size="sm"
                                class="h-7 text-xs px-2"
                                @click="row.mode = 'match'"
                            >
                                Match existing
                            </Button>
                            <Button
                                type="button"
                                :variant="
                                    row.mode === 'create'
                                        ? 'secondary'
                                        : 'ghost'
                                "
                                size="sm"
                                class="h-7 text-xs px-2"
                                @click="row.mode = 'create'"
                            >
                                Create new
                            </Button>
                        </div>
                    </div>

                    <div v-if="row.mode === 'match'">
                        <Combobox
                            v-model="row.matchedId"
                            :options="existingIngredients"
                            placeholder="Search ingredients..."
                        />
                    </div>

                    <div v-else class="space-y-3">
                        <div class="grid gap-2">
                            <Label>Name</Label>
                            <Input v-model="row.newName" />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label>Grocery store (optional)</Label>
                                <Combobox
                                    v-model="row.storeId"
                                    :options="storeOptionsFor(row)"
                                    placeholder="Select store..."
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label>Store section (optional)</Label>
                                <Combobox
                                    v-model="row.sectionId"
                                    :options="sectionOptionsFor(row)"
                                    placeholder="Select section..."
                                    :disabled="!row.storeId"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="saveError" class="text-sm text-destructive">
                {{ saveError }}
            </p>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    @click="handleSave"
                    :disabled="saving || !canSave"
                >
                    {{ saving ? 'Saving...' : saveLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
```

**Step 2: Commit**

```
feat: add IngredientResolutionModal component
```

---

### Task 8: Integrate Modal into RecipeForm

**Files:**
- Modify: `resources/js/pages/recipes/Partials/RecipeForm.vue`

**Step 1: Add the resolve button and modal to RecipeForm**

Changes to `RecipeForm.vue`:

1. Add to imports:
```typescript
import IngredientResolutionModal, {
    type UnmatchedIngredient,
} from '@/components/IngredientResolutionModal.vue';
```

2. Add to the `IngredientRow` interface — a `suggestions` field:
```typescript
interface IngredientRow {
    ingredient_id: number | '';
    quantity: string;
    unit: string;
    note: string;
    importedName?: string;
    suggestions?: Array<{ id: number; name: string }>;
}
```

3. Update the `ingredientRows` initializer to preserve suggestions from the import:
```typescript
const ingredientRows = ref<IngredientRow[]>(
    props.recipe?.ingredients?.length
        ? props.recipe.ingredients.map((ingredient) => ({
              ingredient_id: ingredient.id || '',
              quantity: ingredient.pivot?.quantity?.toString() ?? '',
              unit: ingredient.pivot?.unit ?? '',
              note: ingredient.pivot?.note ?? '',
              importedName:
                  !ingredient.id && ingredient.name ? ingredient.name : undefined,
              suggestions: ingredient.suggestions ?? [],
          }))
        : [],
);
```

4. Add modal state and computed properties:
```typescript
const showResolutionModal = ref(false);

const unmatchedIngredients = computed<UnmatchedIngredient[]>(() =>
    ingredientRows.value
        .map((row, index) => ({ row, index }))
        .filter(({ row }) => row.importedName && row.ingredient_id === '')
        .map(({ row, index }) => ({
            rowIndex: index,
            name: row.importedName!,
            quantity: row.quantity,
            unit: row.unit,
            suggestions: row.suggestions ?? [],
        })),
);

function handleIngredientsResolved(
    map: Record<number, { id: number; name: string }>,
) {
    for (const [rowIndex, resolved] of Object.entries(map)) {
        const idx = Number(rowIndex);
        ingredientRows.value[idx].ingredient_id = resolved.id;
        ingredientRows.value[idx].importedName = undefined;
        ingredientRows.value[idx].suggestions = [];

        // Add to local ingredients list if not already present
        if (!localIngredients.value.some((ing) => ing.id === resolved.id)) {
            localIngredients.value.push({
                id: resolved.id,
                name: resolved.name,
            });
            localIngredients.value.sort((a, b) =>
                a.name.localeCompare(b.name),
            );
        }
    }
}
```

5. Add the resolve button to the Ingredients card header (after the existing "Add ingredient" button):
```vue
<Button
    v-if="unmatchedIngredients.length > 0"
    type="button"
    variant="default"
    size="sm"
    @click="showResolutionModal = true"
>
    Resolve {{ unmatchedIngredients.length }} unmatched
</Button>
```

6. Add the modal at the bottom of the template (alongside the existing modals):
```vue
<IngredientResolutionModal
    v-model:open="showResolutionModal"
    :unmatched-ingredients="unmatchedIngredients"
    :existing-ingredients="localIngredients"
    :grocery-stores="localStores"
    @resolved="handleIngredientsResolved"
/>
```

**Step 2: Update Recipe type to include suggestions**

Check `resources/js/types/models.ts` (or wherever the `Recipe` type ingredient pivot is defined) and ensure the ingredient type allows a `suggestions` array. If the type comes from the backend resource, the field flows through automatically since it's on the JSON response. The `recipeFromImport` computed in `Create.vue` needs to pass suggestions through:

In `Create.vue`, update the ingredients mapping inside `recipeFromImport`:
```typescript
ingredients: (data.ingredients ?? []).map((ing) => ({
    id: ing.ingredient_id ?? 0,
    name: ing.name ?? '',
    suggestions: ing.suggestions ?? [],
    pivot: {
        quantity: ing.quantity ?? '',
        unit: ing.unit ?? '',
        note: ing.note,
    },
})),
```

**Step 3: Commit**

```
feat: integrate ingredient resolution modal into recipe form
```

---

### Task 9: End-to-End Verification and Tests

**Files:**
- Modify: `tests/Feature/RecipeImportTest.php` (update existing test assertions)

**Step 1: Update the existing test that checks import JSON structure**

The first test `it('imports a recipe from a url')` asserts `assertJsonStructure`. Update it to include the `suggestions` key:

```php
$response->assertJsonStructure([
    'name',
    'instructions',
    'servings',
    'flavor_profile',
    'meal_types',
    'prep_time_minutes',
    'cook_time_minutes',
    'photo_url',
    'ingredients' => [
        ['ingredient_id', 'name', 'quantity', 'unit', 'note', 'suggestions'],
    ],
]);
```

**Step 2: Run the full test suite for affected files**

Run: `php artisan test --compact tests/Feature/RecipeImportTest.php tests/Feature/BulkIngredientTest.php tests/Feature/IngredientControllerTest.php`
Expected: ALL PASS

**Step 3: Run pint on all changed files**

Run: `vendor/bin/pint --dirty`

**Step 4: Commit**

```
test: update existing import tests for new suggestions field
```

---

### Task 10: Run Full Test Suite

**Step 1: Run all tests to ensure nothing is broken**

Run: `php artisan test --compact`
Expected: ALL PASS

**Step 2: Build frontend to verify no compilation errors**

Run: `npm run build`
Expected: Build succeeds with no errors

**Step 3: Final commit if any remaining changes**

```
chore: final cleanup for bulk ingredient resolution feature
```
