# Grocery Store/Section Selection Gaps — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Ensure every surface where ingredients can be edited also supports creating and selecting grocery stores and grocery store sections.

**Architecture:** Three independent fixes — (1) add `allow-create` with creation modals to `IngredientResolutionModal`, (2–3) add store/section fields to Shopping List Item Create and Edit pages. All follow the established `useStoreSelection` + `useStoreAndSectionModals` + `StoreCreationModal`/`SectionCreationModal` pattern from `IngredientForm.vue`.

**Tech Stack:** Laravel 12, Inertia v2, Vue 3, Pest v4, existing composables and UI components.

---

## Task 1: IngredientResolutionModal — Add store/section creation support

**Files:**
- Modify: `resources/js/components/IngredientResolutionModal.vue`
- Test: `tests/Feature/RecipeImportTest.php` (or manual — this modal is already tested via recipe import flow)

### Step 1: Add creation modal imports and state

In `IngredientResolutionModal.vue`, add imports and wire up shared creation modal state with an `activeRowIndex` ref to track which row triggered the creation.

Add to `<script setup>`:

```typescript
import StoreCreationModal from '@/components/StoreCreationModal.vue';
import SectionCreationModal from '@/components/SectionCreationModal.vue';
import { useStoreAndSectionModals } from '@/composables/useStoreAndSectionModals';
import type { GroceryStoreSection } from '@/types/models';

const activeRowIndex = ref<number | null>(null);

const {
    showStoreModal,
    showSectionModal,
    prefillStoreName,
    prefillSectionName,
    openStoreModal: baseOpenStoreModal,
    openSectionModal: baseOpenSectionModal,
    handleStoreCreated,
    handleSectionCreated,
} = useStoreAndSectionModals({
    onStoreCreated: (store: GroceryStore) => {
        localStores.value = [...localStores.value, store].sort((a, b) =>
            a.name.localeCompare(b.name),
        );
        if (activeRowIndex.value !== null) {
            rows.value[activeRowIndex.value].storeId = store.id;
        }
    },
    onSectionCreated: (section: GroceryStoreSection) => {
        localStores.value = localStores.value.map((store) => {
            if (activeRowIndex.value !== null && store.id === Number(rows.value[activeRowIndex.value].storeId)) {
                return {
                    ...store,
                    sections: [...(store.sections || []), section].sort(
                        (a, b) => a.name.localeCompare(b.name),
                    ),
                };
            }
            return store;
        });
        if (activeRowIndex.value !== null) {
            rows.value[activeRowIndex.value].sectionId = section.id;
        }
    },
});

function openStoreModalForRow(index: number, prefillName?: string) {
    activeRowIndex.value = index;
    baseOpenStoreModal(prefillName);
}

function openSectionModalForRow(index: number, prefillName?: string) {
    activeRowIndex.value = index;
    baseOpenSectionModal(prefillName);
}
```

### Step 2: Update Comboboxes with `allow-create`

Replace the existing store Combobox (around line 265-269):

```vue
<Combobox
    v-model="row.storeId"
    :options="storeOptionsFor(row)"
    placeholder="Select or create a store..."
    allow-create
    create-label="Create store"
    @create="(name: string) => openStoreModalForRow(index, name)"
/>
```

Replace the existing section Combobox (around line 272-278):

```vue
<Combobox
    v-model="row.sectionId"
    :options="sectionOptionsFor(row)"
    placeholder="Select or create a section..."
    :disabled="!row.storeId"
    allow-create
    create-label="Create section"
    @create="(name: string) => openSectionModalForRow(index, name)"
/>
```

### Step 3: Add creation modals to the template

Add just before the closing `</Dialog>` tag:

```vue
<StoreCreationModal
    v-model:open="showStoreModal"
    :prefill-name="prefillStoreName"
    @store-created="handleStoreCreated"
/>

<SectionCreationModal
    v-model:open="showSectionModal"
    :store-id="activeRowIndex !== null ? rows[activeRowIndex].storeId : ''"
    :prefill-name="prefillSectionName"
    @section-created="handleSectionCreated"
/>
```

### Step 4: Compute the selected store ID for SectionCreationModal

The `SectionCreationModal` needs the `storeId` of the active row. The expression `activeRowIndex !== null ? rows[activeRowIndex].storeId : ''` handles this inline.

### Step 5: Run existing tests

Run: `php artisan test --compact --filter="recipe import\|IngredientResolution\|import"`
Expected: All existing tests pass (no regressions).

### Step 6: Run formatters

Run: `npm run format && npm run lint`

### Step 7: Commit

```
feat: add store/section creation to IngredientResolutionModal
```

---

## Task 2: Backend — Pass groceryStores and update validation for Shopping List Items

**Files:**
- Modify: `app/Http/Controllers/ShoppingListItemController.php:39-54,76-94`
- Modify: `app/Http/Requests/ShoppingListItems/StoreShoppingListItemRequest.php`
- Test: `tests/Feature/ShoppingListItemControllerTest.php`

### Step 1: Write failing tests

In the existing test file (or create if needed), add tests for the new store/section fields.

```php
it('can create a shopping list item with grocery store override', function () {
    $user = \App\Models\User::factory()->create();
    $shoppingList = \App\Models\ShoppingList::factory()->for($user)->create();
    $ingredient = \App\Models\Ingredient::factory()->for($user)->create();
    $store = \App\Models\GroceryStore::factory()->for($user)->create();
    $section = \App\Models\GroceryStoreSection::factory()->for($store)->create();

    $this->actingAs($user)
        ->postJson(route('shopping-list-items.store'), [
            'shopping_list_id' => $shoppingList->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 2,
            'unit' => 'cups',
            'grocery_store_id' => $store->id,
            'grocery_store_section_id' => $section->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('shopping_list_items', [
        'ingredient_id' => $ingredient->id,
        'grocery_store_id' => $store->id,
        'grocery_store_section_id' => $section->id,
    ]);
});

it('passes grocery stores to the create page', function () {
    $user = \App\Models\User::factory()->create();
    $store = \App\Models\GroceryStore::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('shopping-list-items.create'))
        ->assertInertia(fn ($page) => $page
            ->component('shopping-list-items/Create')
            ->has('groceryStores'));
});

it('passes grocery stores to the edit page', function () {
    $user = \App\Models\User::factory()->create();
    $shoppingList = \App\Models\ShoppingList::factory()->for($user)->create();
    $ingredient = \App\Models\Ingredient::factory()->for($user)->create();
    $item = $shoppingList->items()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => 1,
        'unit' => 'cup',
    ]);
    $store = \App\Models\GroceryStore::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('shopping-list-items.edit', $item))
        ->assertInertia(fn ($page) => $page
            ->component('shopping-list-items/Edit')
            ->has('groceryStores'));
});
```

### Step 2: Run tests to verify they fail

Run: `php artisan test --compact --filter="grocery store"`
Expected: FAIL — `groceryStores` prop not present, validation doesn't accept store fields on create.

### Step 3: Update StoreShoppingListItemRequest

Add `grocery_store_id` and `grocery_store_section_id` rules to `app/Http/Requests/ShoppingListItems/StoreShoppingListItemRequest.php`:

```php
'grocery_store_id' => [
    'sometimes',
    'nullable',
    'integer',
    Rule::exists('grocery_stores', 'id')->where('user_id', $this->user()->id),
],
'grocery_store_section_id' => [
    'sometimes',
    'nullable',
    'integer',
    'exists:grocery_store_sections,id',
],
```

### Step 4: Update ShoppingListItemController create() and edit()

In `create()`, add grocery store query and pass to frontend:

```php
$groceryStores = GroceryStore::query()
    ->currentUser()
    ->with('sections')
    ->orderBy('name')
    ->get();
```

Pass to Inertia::render: `'groceryStores' => GroceryStoreResource::collection($groceryStores)`

Same pattern in `edit()`.

Add imports at top:
```php
use App\Http\Resources\GroceryStoreResource;
use App\Models\GroceryStore;
```

### Step 5: Run tests to verify they pass

Run: `php artisan test --compact --filter="grocery store"`
Expected: PASS.

### Step 6: Run formatters

Run: `vendor/bin/pint --dirty`

### Step 7: Commit

```
feat: pass groceryStores to shopping list item create/edit and add validation
```

---

## Task 3: Frontend — Shopping List Item Create page store/section fields

**Files:**
- Modify: `resources/js/pages/shopping-list-items/Create.vue`

### Step 1: Add imports and composable setup

Add to `<script setup>`:

```typescript
import { Combobox } from '@/components/ui/combobox';
import SectionCreationModal from '@/components/SectionCreationModal.vue';
import StoreCreationModal from '@/components/StoreCreationModal.vue';
import { useStoreAndSectionModals } from '@/composables/useStoreAndSectionModals';
import { useStoreSelection } from '@/composables/useStoreSelection';
import type {
    GroceryStore,
    GroceryStoreSection,
    ResourceCollection,
} from '@/types/models';
```

Update props to accept groceryStores:

```typescript
const props = defineProps<{
    shoppingLists: ResourceCollection<ShoppingList>;
    ingredients: ResourceCollection<Ingredient>;
    groceryStores: ResourceCollection<GroceryStore>;
}>();
```

Add composable wiring (same pattern as `IngredientForm.vue`):

```typescript
const localStores = ref<GroceryStore[]>([...resolveCollection(props.groceryStores)]);

const { selectedStoreId, selectedSectionId, storeOptions, sectionOptions } =
    useStoreSelection(localStores);

const {
    showStoreModal,
    showSectionModal,
    prefillStoreName,
    prefillSectionName,
    openStoreModal,
    openSectionModal,
    handleStoreCreated,
    handleSectionCreated,
} = useStoreAndSectionModals({
    onStoreCreated: (store: GroceryStore) => {
        localStores.value = [...localStores.value, store].sort((a, b) =>
            a.name.localeCompare(b.name),
        );
        selectedStoreId.value = store.id;
    },
    onSectionCreated: (section: GroceryStoreSection) => {
        localStores.value = localStores.value.map((s) => {
            if (s.id === Number(selectedStoreId.value)) {
                return {
                    ...s,
                    sections: [...(s.sections || []), section].sort(
                        (a, b) => a.name.localeCompare(b.name),
                    ),
                };
            }
            return s;
        });
        selectedSectionId.value = section.id;
    },
});
```

### Step 2: Add store/section fields to template

Add inside the `<CardContent>` grid, after the unit field:

```vue
<div class="grid gap-2">
    <Label>Grocery store (optional)</Label>
    <Combobox
        v-model="selectedStoreId"
        :options="storeOptions"
        placeholder="Select or create a store..."
        name="grocery_store_id"
        allow-create
        create-label="Create store"
        @create="openStoreModal"
    />
</div>

<div class="grid gap-2">
    <Label>Store section (optional)</Label>
    <Combobox
        v-model="selectedSectionId"
        :options="sectionOptions"
        placeholder="Select or create a section..."
        name="grocery_store_section_id"
        :disabled="!selectedStoreId"
        allow-create
        create-label="Create section"
        @create="openSectionModal"
    />
</div>
```

### Step 3: Add creation modals

Add just before `</AppLayout>`:

```vue
<StoreCreationModal
    v-model:open="showStoreModal"
    :prefill-name="prefillStoreName"
    @store-created="handleStoreCreated"
/>

<SectionCreationModal
    v-model:open="showSectionModal"
    :store-id="selectedStoreId"
    :prefill-name="prefillSectionName"
    @section-created="handleSectionCreated"
/>
```

### Step 4: Run formatters

Run: `npm run format && npm run lint`

### Step 5: Commit

```
feat: add store/section selection to shopping list item create page
```

---

## Task 4: Frontend — Shopping List Item Edit page store/section fields

**Files:**
- Modify: `resources/js/pages/shopping-list-items/Edit.vue`

### Step 1: Add imports and composable setup

Same pattern as Task 3. Add imports, update props to include `groceryStores`, set up composables.

For the Edit page, initialize `useStoreSelection` with the existing item's values:

```typescript
const { selectedStoreId, selectedSectionId, storeOptions, sectionOptions } =
    useStoreSelection(localStores, {
        initialStoreId: item.grocery_store_id ?? '',
        initialSectionId: item.grocery_store_section_id ?? '',
    });
```

### Step 2: Add store/section fields to template

Same Combobox fields as Task 3, added inside `<CardContent>`.

### Step 3: Add creation modals

Same as Task 3.

### Step 4: Run formatters

Run: `npm run format && npm run lint`

### Step 5: Run all related tests

Run: `php artisan test --compact --filter="ShoppingListItem"`
Expected: All tests pass.

### Step 6: Commit

```
feat: add store/section selection to shopping list item edit page
```

---

## Task 5: Final verification

### Step 1: Run full test suite

Run: `php artisan test --compact`
Expected: All tests pass (except known pre-existing failures in `ResourceSerializationTest`, `ShoppingListItemControllerTest`, `ShoppingListPreferencesTest`).

### Step 2: Run all formatters

Run: `vendor/bin/pint --dirty && npm run format && npm run lint`

### Step 3: Manual smoke test

Build frontend: `npm run build`

Verify each surface:
1. Import a recipe → unmatched ingredients → create mode → can create new store/section
2. Shopping list items → Create → store/section fields present with create capability
3. Shopping list items → Edit → store/section fields pre-populated with create capability
