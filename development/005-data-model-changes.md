# Data Model Changes (Initial Build)

## Goal
Capture schema changes needed for the initial feature set, aligned with the current Laravel/Inertia stack.

## Recipes
Add the following columns to `recipes`:
- `photo_path` (string, nullable)
- `prep_time_minutes` (unsigned integer, nullable)
- `cook_time_minutes` (unsigned integer, nullable)

Notes:
- Recipe photos are stored on the default disk in a public subdirectory configured via `.env`.
- Enforce square images with max resolution 2048x2048 using `spatie/image` during upload processing.

## Shopping List Items
Add the following column to `shopping_list_items`:
- `sort_order` (unsigned integer, nullable)

Notes:
- Use `sort_order` for user-defined ordering and manual sorting.
- Default ordering can fall back to `id` or name when `sort_order` is null.
