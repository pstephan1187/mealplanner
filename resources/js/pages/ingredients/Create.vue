<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import {
    create,
    index as ingredientsIndex,
    store as storeIngredient,
} from '@/routes/ingredients';
import { type BreadcrumbItem } from '@/types';
import type { GroceryStore } from '@/types/models';

import IngredientForm from './Partials/IngredientForm.vue';

const props = defineProps<{
    groceryStores: ResourceProp<GroceryStore[]>;
}>();

const groceryStores = resolveResource(props.groceryStores);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ingredients',
        href: ingredientsIndex().url,
    },
    {
        title: 'Create',
        href: create().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create ingredient" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Create ingredient"
                    description="Add pantry staples and fresh produce you'll reuse."
                />
                <Button variant="ghost" as-child>
                    <Link :href="ingredientsIndex()">Back to ingredients</Link>
                </Button>
            </div>

            <Form
                v-bind="storeIngredient.form()"
                class="max-w-xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <IngredientForm
                    :grocery-stores="groceryStores"
                    :errors="errors"
                />

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="ingredientsIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Create ingredient
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
