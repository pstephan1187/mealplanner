# Meal Planner

A full-stack meal planning application built with Laravel 12, Inertia v2, Vue 3, and Tailwind CSS v4. Manage recipes, plan meals for the week, and generate organized shopping lists grouped by grocery store sections.

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- SQLite
- [Laravel Herd](https://herd.laravel.com) (recommended) or any local PHP server

For AI-powered recipe import, you'll need an OpenAI API key (`OPENAI_API_KEY` in `.env`).

## Getting Started

### Quick Setup

```bash
composer setup
```

This runs in sequence: `composer install`, copies `.env.example` to `.env`, generates an app key, runs migrations, installs npm dependencies, and builds frontend assets.

### Manual Setup

```bash
# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database (SQLite is the default — the file is created automatically)
php artisan migrate

# Build frontend
npm run build
```

### Database Seeding

Seed the database with sample data for development:

```bash
php artisan db:seed
```

The seeder creates a test user (configurable via env vars) and populates recipes, ingredients, meal plans, and shopping lists. Configure the seed user in `.env`:

```
SEEDER_USER_NAME="Test User"
SEEDER_USER_EMAIL=test@example.com
SEEDER_USER_PASSWORD=password
```

### Running the Dev Server

```bash
composer run dev
```

This starts three processes concurrently:
- **Queue worker** — `php artisan queue:listen` (for background jobs like recipe import)
- **Log watcher** — `php artisan pail` (real-time log output)
- **Vite** — `npm run dev` (frontend hot-reload + Wayfinder route generation)

If using Laravel Herd, the app is automatically available at `https://mealplanner.test`. Otherwise, visit the URL shown in Vite's output.

## Development Commands

### Code Formatting

```bash
# Format PHP (run before committing)
vendor/bin/pint --dirty

# Format frontend (Vue, TS, CSS)
npm run format

# Lint frontend
npm run lint
```

### Testing

```bash
# Run all tests
php artisan test --compact

# Run a specific test file
php artisan test --compact tests/Feature/RecipeControllerTest.php

# Run a specific test by name
php artisan test --compact --filter="can create a recipe with sections"
```

Tests use **Pest v4** with `RefreshDatabase`. Browser tests (in `tests/Browser/`) use Pest's built-in browser testing with Playwright.

### Wayfinder Route Generation

[Wayfinder](https://github.com/laravel/wayfinder) generates type-safe TypeScript functions for all Laravel routes and controller actions. The Vite plugin runs this automatically in dev mode. To regenerate manually:

```bash
php artisan wayfinder:generate
```

Generated files live in `resources/js/wayfinder/`, `resources/js/actions/`, and `resources/js/routes/`.

## Architecture

### Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | Vue 3, TypeScript |
| Bridge | Inertia.js v2 |
| Styling | Tailwind CSS v4 |
| Database | SQLite |
| Testing | Pest v4 (Feature + Browser) |
| Auth | Laravel Fortify (login, registration, password reset, email verification, 2FA) |
| Code Style | Laravel Pint (PHP), Prettier + ESLint (frontend) |

### Domain Model

```
User
 ├── Recipes ──────── RecipeSections (optional grouping)
 │                      └── Ingredients (via ingredient_recipe pivot)
 ├── Ingredients ──── linked to GroceryStore / GroceryStoreSection
 ├── MealPlans ────── MealPlanRecipes (date + meal_type + servings)
 │                      └── generates → ShoppingList
 ├── ShoppingLists ── ShoppingListItems (with store/section overrides)
 └── GroceryStores ── GroceryStoreSections
```

All user-owned models use the `BelongsToCurrentUser` trait which provides a `scopeCurrentUser()` query scope for filtering by the authenticated user.

### Recipe Structure

Recipes support two ingredient organization modes:

- **Flat** — Ingredients attached directly to the recipe (pivot `recipe_section_id` is NULL)
- **Sectioned** — Ingredients grouped under `RecipeSection` records (e.g., "For the Sauce", "For the Dough")

A recipe cannot mix both modes. The `ingredient_recipe` pivot table has a surrogate `id` primary key with columns: `recipe_id`, `ingredient_id`, `recipe_section_id` (nullable), `quantity`, `unit`, `note`.

### Shopping List Display Modes

Shopping lists support three display modes:
- **Manual** — User-defined sort order (drag-and-drop)
- **Alphabetical** — Sorted by ingredient name
- **Store** — Grouped by grocery store sections

### AI-Powered Recipe Import

Paste a recipe URL and the app uses Prism (with OpenAI's `gpt-4o-mini`) to extract structured recipe data: name, instructions (as sanitized HTML), ingredients, servings, prep/cook times, and flavor profile. Imported ingredients are fuzzy-matched against the user's existing ingredient library.

## Key Patterns & Conventions

### Backend

**Authorization** uses the `EnsuresOwnership` trait on controllers. It checks that the authenticated user owns the resource (via `user_id`), returning a 404 if not. For nested resources (e.g., shopping list items), it traverses relationships via the `$throughRelationship` parameter.

**Form Requests** use array-based validation rules (not pipe-delimited strings). Rich text inputs are sanitized in `prepareForValidation()` using `Purifier::clean()` from the `mews/purifier` package.

**Fractions** are stored as decimals in the database and displayed as human-readable fractions (e.g., `1 1/2`). The `FractionConverter` utility class handles conversion in both directions, and the custom `Fraction` validation rule validates user input.

**API Resources** use `whenLoaded()` and `whenPivotLoaded()` for conditional relationship data. `IngredientResource` converts decimal quantities back to fraction strings for display.

**Photos** support both file upload and URL import. Uploaded images are cropped to square using `spatie/image` and stored on the `public` disk.

### Frontend

**Forms** use the Inertia `<Form>` component with native HTML `name` attributes — not `useForm`. Array notation is used for nested data: `:name="\`ingredients[${index}][ingredient_id]\`"`.

**Routing** uses Wayfinder-generated type-safe functions. Import from `@/routes/` for named routes or `@/actions/` for controller actions. Use `.form()` for `<Form>` action/method attributes and `.url()` for URL strings.

**UI Components** follow the shadcn/vue pattern — a component library in `resources/js/components/ui/` built on `reka-ui` headless primitives, styled with `class-variance-authority` and `tailwind-merge`.

**Rich Text Editor** is TipTap-based (`resources/js/components/ui/rich-text-editor/`), using `v-model` for data binding and a hidden `<input>` for form submission.

**Theming** is CSS variable-based with two themes (default and blush-pink), each supporting light and dark modes. Theme variables are defined in `resources/css/app.css` and toggled via CSS classes (`.dark`, `.theme-blush-pink`).

**TypeScript types** for all domain models live in `resources/js/types/models.ts`. The `ResourceCollection<T>` type and `resolveCollection()` helper normalize Laravel API response shapes.

### Testing

- Feature tests live in `tests/Feature/`, browser tests in `tests/Browser/`, unit tests in `tests/Unit/`
- All tests use `RefreshDatabase`
- Use `postJson()` (not `post()`) when testing validation errors to get 422 responses instead of 302 redirects
- Factory pattern: `Ingredient::factory()->for($user)->create()`
- Inertia assertions: `->assertInertia(fn ($page) => $page->component('recipes/Show')->has('recipe.data.sections', 1))`

## Project Structure

```
app/
├── Actions/                    # Action classes (Fortify auth + ParseRecipeFromUrl)
├── Http/
│   ├── Controllers/            # Resource controllers
│   │   ├── Concerns/           # EnsuresOwnership trait
│   │   └── Settings/           # Profile, password, appearance, 2FA controllers
│   ├── Middleware/              # HandleAppearance, HandleInertiaRequests
│   ├── Requests/               # Form request validation (organized by resource)
│   └── Resources/              # Eloquent API resources
├── Models/
│   └── Concerns/               # BelongsToCurrentUser trait
├── Providers/                  # AppServiceProvider, FortifyServiceProvider
├── Rules/                      # Fraction validation rule
└── Support/                    # FractionConverter utility

resources/js/
├── pages/                      # Inertia page components (by resource)
├── components/                 # Shared components
│   └── ui/                     # shadcn/vue-style component library (reka-ui based)
├── composables/                # Vue composables (drag-drop, modals, sorting, themes)
├── layouts/                    # App, auth, and settings layouts
├── types/                      # TypeScript interfaces (models.ts)
├── lib/                        # Utility functions
├── actions/                    # Wayfinder-generated controller actions
├── routes/                     # Wayfinder-generated named routes
└── wayfinder/                  # Wayfinder-generated base types

database/
├── migrations/                 # 23 migration files
├── factories/                  # Model factories (all 10 models)
└── seeders/                    # Database seeders

tests/
├── Feature/                    # HTTP/controller tests
├── Browser/                    # Pest v4 browser tests (Playwright)
└── Unit/                       # Unit tests (FractionConverter, etc.)
```

## Configuration

### Environment Variables

Key variables beyond Laravel defaults:

| Variable | Purpose |
|----------|---------|
| `OPENAI_API_KEY` | Required for AI recipe import |
| `SEEDER_USER_NAME` | Seed user's name (default: "Test User") |
| `SEEDER_USER_EMAIL` | Seed user's email (default: test@example.com) |
| `SEEDER_USER_PASSWORD` | Seed user's password (default: password) |

### Fortify Features

Configured in `config/fortify.php`:

- User registration
- Password reset via email
- Email verification
- Two-factor authentication (with password + OTP confirmation)

### Middleware

Configured in `bootstrap/app.php`:

- `HandleAppearance` — Manages theme/appearance preferences
- `HandleInertiaRequests` — Shares auth user data and sidebar state with all Inertia pages
- Cookies `appearance` and `sidebar_state` are excluded from encryption
