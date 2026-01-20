# Feature 2 - Recipe Library

## Goal
Allow users to create, view, edit, and organize recipes with photos and time estimates.

## Data Model
- Recipe fields:
  - `id`, `user_id`, `name`
  - `instructions` (Markdown)
  - `servings` (default)
  - `flavor_profile` enum (`App\Enums\FlavorProfile`)
  - `meal_types` JSON array: `Breakfast`, `Lunch`, `Dinner`
  - `photo_path` (string)
  - `prep_time_minutes` (integer)
  - `cook_time_minutes` (integer)
- Ingredient fields: `id`, `name`
- RecipeIngredient (pivot): `recipe_id`, `ingredient_id`, `quantity`, `unit`, `note`

## Pages
- Recipe Index
  - Search, filter by `flavor_profile` and `meal_types`.
  - Card or list layout optimized for mobile.
- Recipe Detail
  - Photo, times, servings, meal types, ingredients, instructions.
  - Actions: edit, duplicate, delete.
- Recipe Create/Edit
  - Markdown editor for instructions.
  - Ingredients repeater (ingredient, quantity, unit, note).
  - Photo upload with preview.

## UI/UX Notes
- Mobile-first layout: card stacks, large tap targets, sticky save action on small screens.
- Provide a clear empty state with a primary "Create recipe" action.
- Inline validation errors, and show total time (prep + cook) as a convenience.

## Photo Requirements
- Storage disk is configurable via `.env` and uses a public subdirectory on the default disk.
- Enforce square images with a max resolution of 2048x2048 using `spatie/image`.

## Image Upload Flow
- Validate the uploaded `photo` as an image with square dimensions (2048x2048 max).
- Store the processed image in a `recipes/` subdirectory on the default public disk.
- Use `spatie/image` to crop/fit to a square and limit resolution before saving.
- Persist the relative path in `photo_path` and use `Storage::url()` for display.

## Migration Notes
- Add `photo_path`, `prep_time_minutes`, and `cook_time_minutes` to the `recipes` table.

## Deliverables
- Inertia pages under `resources/js/pages/recipes`.
- Shared form components for ingredient rows if reused elsewhere.
