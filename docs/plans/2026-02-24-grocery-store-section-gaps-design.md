# Grocery Store/Section Selection Gaps

## Problem

Three surfaces where ingredients can be edited lack full grocery store/section support:

1. **IngredientResolutionModal** - Can select stores/sections but cannot create new ones
2. **Shopping List Item Create page** - No store/section fields at all
3. **Shopping List Item Edit page** - No store/section fields at all

## Design

### Gap 1: IngredientResolutionModal

Add `StoreCreationModal` and `SectionCreationModal` with `allow-create` on Comboboxes.

Since the modal manages multiple rows, share a single set of creation modals with an `activeRowIndex` tracker. When a store/section is created, assign it to the row that triggered the creation and update `localStores` so it appears in all rows.

**Files changed:**
- `resources/js/components/IngredientResolutionModal.vue` — add modals, allow-create, row tracking

### Gap 2 & 3: Shopping List Item Create/Edit Pages

Add item-level store/section override fields using the same pattern as `IngredientForm.vue`.

**Backend:**
- `ShoppingListItemController::create()` and `edit()` — pass `groceryStores` with eager-loaded sections
- `StoreShoppingListItemRequest` — add `grocery_store_id` and `grocery_store_section_id` validation rules (Update request already has them)

**Frontend (both Create.vue and Edit.vue):**
- Accept `groceryStores` prop
- Add `useStoreSelection` + `useStoreAndSectionModals` composables
- Add Combobox fields with `allow-create` for store and section
- Mount `StoreCreationModal` and `SectionCreationModal`

### Patterns to reuse

- `useStoreSelection` composable for reactive store/section dropdowns
- `useStoreAndSectionModals` composable for creation modal state
- `StoreCreationModal` and `SectionCreationModal` components
- Combobox `allow-create` + `@create` pattern from `IngredientForm.vue`

### Scope decision

Shopping List Item pages set item-level overrides (`ShoppingListItem.grocery_store_id`), not the ingredient's default.
