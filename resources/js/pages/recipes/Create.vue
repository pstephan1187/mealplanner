<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import { create, index as recipesIndex, store } from '@/routes/recipes';
import { type BreadcrumbItem } from '@/types';
import type { GroceryStore, Ingredient, Recipe } from '@/types/models';

import RecipeImportController from '@/actions/App/Http/Controllers/RecipeImportController';

import RecipeForm from './Partials/RecipeForm.vue';

type IngredientOption = Pick<Ingredient, 'id' | 'name'>;

const props = defineProps<{
    ingredients: ResourceProp<IngredientOption[]>;
    groceryStores: ResourceProp<GroceryStore[]>;
    canImportRecipe: boolean;
}>();

const ingredients = resolveResource(props.ingredients);
const groceryStores = resolveResource(props.groceryStores);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recipes',
        href: recipesIndex().url,
    },
    {
        title: 'Create',
        href: create().url,
    },
];

interface ImportedIngredient {
    ingredient_id: number | null;
    name: string | null;
    quantity: string | number | null;
    unit: string | null;
    note: string | null;
    suggestions: Array<{ id: number; name: string }>;
}

interface ImportResponse {
    name: string | null;
    instructions: string | null;
    servings: number | null;
    flavor_profile: string | null;
    meal_types: string[] | null;
    prep_time_minutes: number | null;
    cook_time_minutes: number | null;
    photo_url: string | null;
    ingredients: ImportedIngredient[];
}

const showImportModal = ref(false);
const importUrl = ref('');
const importError = ref('');
const importing = ref(false);
const importedRecipe = ref<ImportResponse | null>(null);
const formKey = ref(0);

const recipeFromImport = computed<Recipe | null>(() => {
    const data = importedRecipe.value;
    if (!data) {
        return null;
    }

    return {
        id: 0,
        name: data.name ?? '',
        instructions: data.instructions ?? undefined,
        servings: data.servings,
        flavor_profile: data.flavor_profile ?? undefined,
        meal_types: data.meal_types ?? [],
        photo_url: data.photo_url,
        prep_time_minutes: data.prep_time_minutes,
        cook_time_minutes: data.cook_time_minutes,
        ingredients: (data.ingredients ?? []).map((ing) => ({
            id: ing.ingredient_id ?? 0,
            name: ing.name ?? '',
            suggestions: ing.suggestions ?? [],
            pivot: {
                quantity: ing.quantity ?? '',
                unit: ing.unit ?? '',
                note: ing.note,
            },
        })),
    } as Recipe;
});

const openImportModal = () => {
    importUrl.value = '';
    importError.value = '';
    showImportModal.value = true;
};

const importRecipe = async () => {
    importError.value = '';
    importing.value = true;

    try {
        const response = await fetch(RecipeImportController.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find((c) => c.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] ?? '',
                ),
            },
            body: JSON.stringify({ url: importUrl.value }),
        });

        const data = await response.json();

        if (!response.ok) {
            importError.value =
                data.message ||
                data.errors?.url?.[0] ||
                'Failed to import recipe.';
            return;
        }

        importedRecipe.value = data as ImportResponse;
        formKey.value++;
        showImportModal.value = false;
    } catch {
        importError.value =
            'A network error occurred. Please check the URL and try again.';
    } finally {
        importing.value = false;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create recipe" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Create recipe"
                    description="Save instructions, prep times, and a square hero photo."
                />
                <div class="flex items-center gap-2">
                    <Button
                        v-if="canImportRecipe"
                        variant="outline"
                        type="button"
                        @click="openImportModal"
                    >
                        Import from URL
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="recipesIndex()">Back to recipes</Link>
                    </Button>
                </div>
            </div>

            <Form
                v-bind="store.form()"
                enctype="multipart/form-data"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <RecipeForm
                    :key="formKey"
                    :recipe="recipeFromImport"
                    :ingredients="ingredients"
                    :grocery-stores="groceryStores"
                    :errors="errors"
                />

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="recipesIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Create recipe
                    </Button>
                </div>
            </Form>
        </div>

        <Dialog v-model:open="showImportModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Import from URL</DialogTitle>
                    <DialogDescription>
                        Paste a recipe URL and we'll extract the details for you.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="grid gap-2">
                        <Label for="import-url">Recipe URL</Label>
                        <Input
                            id="import-url"
                            v-model="importUrl"
                            type="url"
                            placeholder="https://example.com/recipe"
                            @keydown.enter.prevent="importRecipe"
                        />
                        <p
                            v-if="importError"
                            class="text-sm text-destructive"
                        >
                            {{ importError }}
                        </p>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        @click="importRecipe"
                        :disabled="importing || !importUrl.trim()"
                    >
                        {{ importing ? 'Importing...' : 'Import' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
