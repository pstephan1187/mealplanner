<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ResourceIndex from '@/components/ResourceIndex.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create,
    index as shoppingListItemsIndex,
    show,
} from '@/routes/shopping-list-items';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, ShoppingListItem } from '@/types/models';

defineProps<{
    items: Paginated<ShoppingListItem>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shopping list items',
        href: shoppingListItemsIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Shopping list items" />

        <ResourceIndex
            title="Shopping list items"
            description="Line items across all shopping lists."
            :create-route="create().url"
            create-label="Add item"
            empty-state-message="No items yet. Add an ingredient to get started."
            :items="items"
            #default="{ item }"
        >
            <Card>
                <CardContent class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold">
                            {{ item.ingredient?.name ?? 'Ingredient' }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ item.quantity }} {{ item.unit }}
                        </p>
                    </div>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="show(item.id)">View</Link>
                    </Button>
                </CardContent>
            </Card>
        </ResourceIndex>
    </AppLayout>
</template>
