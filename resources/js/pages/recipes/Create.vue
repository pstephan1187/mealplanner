<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import { create, index as recipesIndex, store } from '@/routes/recipes';
import { type BreadcrumbItem } from '@/types';

import RecipeForm from './Partials/RecipeForm.vue';

interface IngredientOption {
    id: number;
    name: string;
}

const props = defineProps<{
    ingredients: ResourceProp<IngredientOption[]>;
}>();

const ingredients = resolveResource(props.ingredients);

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
                <Button variant="ghost" as-child>
                    <Link :href="recipesIndex()">Back to recipes</Link>
                </Button>
            </div>

            <Form
                v-bind="store.form()"
                enctype="multipart/form-data"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <RecipeForm :ingredients="ingredients" :errors="errors" />

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
    </AppLayout>
</template>
