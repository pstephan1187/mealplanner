<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import { edit, index as ingredientsIndex, update } from '@/routes/ingredients';
import { type BreadcrumbItem } from '@/types';
import type { GroceryStore, Ingredient } from '@/types/models';

import IngredientForm from './Partials/IngredientForm.vue';

const props = defineProps<{
    ingredient: ResourceProp<Ingredient>;
    groceryStores: ResourceProp<GroceryStore[]>;
}>();

const ingredient = resolveResource(props.ingredient);
const groceryStores = resolveResource(props.groceryStores);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ingredients',
        href: ingredientsIndex().url,
    },
    {
        title: ingredient.name,
        href: edit(ingredient).url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit ingredient" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Edit ingredient"
                    description="Rename or refine this pantry item."
                />
                <Button variant="ghost" as-child>
                    <Link :href="ingredientsIndex()">Back to ingredients</Link>
                </Button>
            </div>

            <Form
                v-bind="update.form.patch(ingredient)"
                class="max-w-xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <IngredientForm
                    :ingredient="ingredient"
                    :grocery-stores="groceryStores"
                    :errors="errors"
                />

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="ingredientsIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
