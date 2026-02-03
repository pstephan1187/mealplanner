import { type PaginationLink } from '@/components/Pagination.vue';

// ---------------------------------------------------------------------------
// Pagination & Resource helpers
// ---------------------------------------------------------------------------

export interface Paginated<T> {
    data: T[];
    links?: PaginationLink[];
    meta?: {
        total?: number;
        from?: number | null;
        to?: number | null;
    };
}

export type ResourceCollection<T> = { data: T[] } | T[];

/**
 * Resolve a ResourceCollection into a plain array.
 * Handles both `T[]` and `{ data: T[] }` shapes returned by Laravel.
 */
export function resolveCollection<T>(collection: ResourceCollection<T>): T[] {
    return Array.isArray(collection) ? collection : (collection.data ?? []);
}

// ---------------------------------------------------------------------------
// Grocery stores & sections
// ---------------------------------------------------------------------------

export interface GroceryStoreSection {
    id: number;
    grocery_store_id?: number;
    name: string;
    sort_order?: number;
}

export interface GroceryStore {
    id: number;
    name: string;
    sections_count?: number;
    sections?: GroceryStoreSection[];
}

// ---------------------------------------------------------------------------
// Ingredients
// ---------------------------------------------------------------------------

export interface Ingredient {
    id: number;
    name: string;
    grocery_store_id?: number | null;
    grocery_store_section_id?: number | null;
    grocery_store?: GroceryStore | null;
    grocery_store_section?: GroceryStoreSection | null;
}

// ---------------------------------------------------------------------------
// Recipes
// ---------------------------------------------------------------------------

export interface RecipeIngredientPivot {
    quantity: string | number;
    unit: string;
    note?: string | null;
}

export interface RecipeIngredient {
    id: number;
    name: string;
    pivot?: RecipeIngredientPivot | null;
    suggestions?: Array<{ id: number; name: string }>;
}

export interface Recipe {
    id: number;
    name: string;
    instructions?: string;
    servings?: number | null;
    flavor_profile?: string;
    meal_types?: string[];
    photo_url?: string | null;
    prep_time_minutes?: number | null;
    cook_time_minutes?: number | null;
    ingredients?: RecipeIngredient[];
}

// ---------------------------------------------------------------------------
// Meal plans & meal-plan recipes
// ---------------------------------------------------------------------------

export interface MealPlan {
    id: number;
    name: string;
    start_date?: string | null;
    end_date?: string | null;
    meal_plan_recipes?: MealPlanRecipe[];
    shopping_list?: ShoppingList | null;
}

export interface MealPlanRecipe {
    id: number;
    meal_plan_id: number;
    recipe_id: number;
    date: string;
    meal_type: string;
    servings: number;
    recipe?: Recipe | null;
}

// ---------------------------------------------------------------------------
// Shopping lists & items
// ---------------------------------------------------------------------------

export interface ShoppingList {
    id: number;
    meal_plan_id: number;
    display_mode?: 'manual' | 'alphabetical' | 'store';
    meal_plan?: MealPlan | null;
    items?: ShoppingListItem[];
}

export interface ShoppingListItem {
    id: number;
    shopping_list_id?: number;
    ingredient_id?: number;
    quantity: string | number;
    unit: string;
    is_purchased: boolean;
    sort_order?: number | null;
    grocery_store_id?: number | null;
    grocery_store_section_id?: number | null;
    ingredient?: Ingredient | null;
    effective_grocery_store?: GroceryStore | null;
    effective_grocery_store_section?: GroceryStoreSection | null;
}
