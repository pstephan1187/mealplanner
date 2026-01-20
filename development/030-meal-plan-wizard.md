# Feature 3 - Meal Plan Wizard (Create)

## Goal
Create meal plans using a full-page, mobile-friendly wizard flow.

## Flow
1. Setup
   - Plan name, date range, and meal types (Breakfast, Lunch, Dinner).
2. Selection
   - Auto-assign recipes to each slot initially.
   - Allow manual updates per slot.
   - Prioritize recipes that have not been used recently.
   - Optional "Replace" action to swap to another valid recipe.
3. Servings
   - Adjust servings for each planned recipe (default from recipe).
4. Review
   - Summary of dates, meals, and servings before confirm.

## UI/UX Notes
- Full-page flow with a step indicator that collapses to a compact progress bar on mobile.
- Use sticky bottom actions on mobile (Next, Back, Save).
- Validate per-step and preserve user input between steps.
- Show loading placeholders if deferred props are used.

## Deliverables
- Inertia pages under `resources/js/pages/meal-plans/create`.
- Reusable step components or a wizard layout wrapper.
- Clear success state with navigation to the new meal plan.
