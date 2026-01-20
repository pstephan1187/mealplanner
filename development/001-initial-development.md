# Meal Planning App - Technical Specification (Laravel + Inertia/Vue)

## Overview
A meal planning application built with Laravel 12, Inertia v2, Vue 3, and Tailwind CSS v4. It enables users to manage recipes, build meal plans through a wizard, and generate consolidated shopping lists.

## Current Stack Snapshot
- Backend: Laravel 12, Fortify, Wayfinder
- Frontend: Inertia v2, Vue 3, Tailwind CSS v4
- UI toolkit in use:
  - Reka UI based components under `resources/js/components/ui`
  - `lucide-vue-next` for icons
  - `tw-animate-css` for motion utilities
  - `class-variance-authority`, `clsx`, `tailwind-merge` for class composition
  - `@vueuse/core` for composables

## Stack Delta (from original Filament plan)
- No Filament panel or resources. All UI is custom Inertia pages with Vue components.
- Filament tables/forms/wizards are replaced by pages and shared UI components.
- Wayfinder routes and Inertia links replace panel navigation.

## UI/UX Direction
- Keep the current application look and navigation structure.
- Mobile and tablet are primary: design for touch, compact layout, and clear tap targets.
- Reuse existing layouts (`AppLayout`, `AppHeader`, `AppSidebar`) for consistent spacing.
- Use existing UI components and patterns before adding new ones.
- Provide empty states, skeleton loading states, and inline validation errors for all CRUD views.
- Ensure accessible forms: labels, help text, focus states, and keyboard navigation.

## Feature Documents (Build Order)
1. `development/005-data-model-changes.md`
2. `development/010-app-shell-and-navigation.md`
3. `development/020-recipes.md`
4. `development/030-meal-plan-wizard.md`
5. `development/040-meal-plan-calendar.md`
6. `development/050-shopping-list-core.md`
7. `development/060-print-and-export.md`
8. `development/070-seeds-and-demo-data.md`
9. `development/080-testing.md`

Deferred for later:
- `development/090-future-shopping-list-sublists.md`
- Recipe tags

## Domain Model Summary
- Users: standard Laravel auth with per-user scoping.
- Recipes: recipes, ingredients, and recipe ingredients (pivot).
- Meal Plans: meal plans and meal plan recipes (per date, meal type).
- Shopping Lists: main list and items only (sublists deferred).
  - Shopping list items include `sort_order` for manual ordering.

## Technical Guidelines
- Use Inertia `Link` and Wayfinder routes instead of hardcoded URLs.
- Use eager loading on list/detail views to avoid N+1 queries.
- Prefer Form Requests for validation and policies for authorization.
- Follow Laravel 12 structure: configure middleware in `bootstrap/app.php`.
- Run Pint formatting and minimal Pest tests for each change.
