# Feature 4 - Meal Plan Calendar (View/Edit)

## Goal
View and adjust meal plans in a mobile-friendly calendar layout.

## UI Layout
- Mobile: week view with day cards that can be swiped left and right.
- Tablet/Desktop: weekly grid showing days as columns and meal types as rows.

## Functionality
- View assigned recipes per day and meal type.
- Quick edit actions per slot: change recipe, adjust servings, clear slot.
- Visual indicators for missing meals in the plan.

## UI/UX Notes
- Avoid dense grids on mobile; use swipeable day cards with clear meal sections.
- Make quick actions available via long-press or overflow menus on touch devices.
- Keep meal slots accessible with sufficient spacing for taps.

## Deliverables
- Inertia pages under `resources/js/pages/meal-plans/show`.
- Reusable meal-slot components for list and grid views.
