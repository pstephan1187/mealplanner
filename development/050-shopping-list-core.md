# Feature 5 - Shopping List (Core)

## Goal
Generate and manage a single consolidated shopping list for a meal plan.

## Data Model
- ShoppingListItem includes `sort_order` (integer) for user-defined ordering.

## Consolidation Logic
- Trigger on meal plan approval/confirmation.
- Scale quantities: `(Ingredient Quantity / Recipe Servings) * Plan Servings`.
- Sum totals for identical ingredients and consistent units.

## UI/UX Notes
- Checklist UI with a clear "purchased" toggle per item.
- Allow user-defined ordering and manual sorting of items.
- Provide a simple way to set or reset the order on mobile.
- Allow manual quantity edits with clear indication that the value is user-adjusted.

## Migration Notes
- Add `sort_order` to `shopping_list_items` for user-defined ordering.

## Deliverables
- Inertia pages under `resources/js/pages/shopping-lists`.
- Consolidation service or action for computing list items.
