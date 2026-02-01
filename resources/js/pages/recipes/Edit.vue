<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import { edit, index as recipesIndex, update } from '@/routes/recipes';
import { type BreadcrumbItem } from '@/types';
import type { GroceryStore, Ingredient, Recipe } from '@/types/models';

import RecipeForm from './Partials/RecipeForm.vue';

type IngredientOption = Pick<Ingredient, 'id' | 'name'>;

const props = defineProps<{
    recipe: ResourceProp<Recipe>;
    ingredients: ResourceProp<IngredientOption[]>;
    groceryStores: ResourceProp<GroceryStore[]>;
}>();

const recipe = resolveResource(props.recipe);
const ingredients = resolveResource(props.ingredients);
const groceryStores = resolveResource(props.groceryStores);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recipes',
        href: recipesIndex().url,
    },
    {
        title: recipe.name,
        href: edit(recipe).url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit recipe" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Edit recipe"
                    description="Adjust ingredients, timing, or swap out the hero photo."
                />
                <Button variant="ghost" as-child>
                    <Link :href="recipesIndex()">Back to recipes</Link>
                </Button>
            </div>

            <Form
                v-bind="update.form(recipe)"
                enctype="multipart/form-data"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <RecipeForm
                    :recipe="recipe"
                    :ingredients="ingredients"
                    :grocery-stores="groceryStores"
                    :errors="errors"
                />

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="recipesIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
