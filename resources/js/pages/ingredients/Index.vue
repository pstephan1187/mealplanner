<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ResourceIndex from '@/components/ResourceIndex.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index as ingredientsIndex, show } from '@/routes/ingredients';
import { type BreadcrumbItem } from '@/types';
import type { Ingredient, Paginated } from '@/types/models';

defineProps<{
    ingredients: Paginated<Ingredient>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ingredients',
        href: ingredientsIndex().url,
    },
];

const getLocationText = (ingredient: Ingredient): string => {
    if (!ingredient.grocery_store) {
        return 'No store assigned';
    }
    if (ingredient.grocery_store_section) {
        return `${ingredient.grocery_store.name} · ${ingredient.grocery_store_section.name}`;
    }
    return ingredient.grocery_store.name;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Ingredients" />

        <ResourceIndex
            title="Ingredients"
            description="Keep your ingredient list tidy and reusable."
            :create-route="create().url"
            create-label="Add ingredient"
            empty-state-message="No ingredients yet. Start with pantry staples you use often."
            :items="ingredients"
            #default="{ item: ingredient }"
        >
            <Card>
                <CardContent class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">{{ ingredient.name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ getLocationText(ingredient) }}
                        </p>
                    </div>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="show(ingredient)">View</Link>
                    </Button>
                </CardContent>
            </Card>
        </ResourceIndex>
    </AppLayout>
</template>
