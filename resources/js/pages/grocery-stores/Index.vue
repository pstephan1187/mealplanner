<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ResourceIndex from '@/components/ResourceIndex.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create,
    index as groceryStoresIndex,
    show,
} from '@/routes/grocery-stores';
import { type BreadcrumbItem } from '@/types';
import type { GroceryStore, Paginated } from '@/types/models';

defineProps<{
    groceryStores: Paginated<GroceryStore>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grocery Stores',
        href: groceryStoresIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Grocery Stores" />

        <ResourceIndex
            title="Grocery Stores"
            description="Manage your stores and their sections for organized shopping."
            :create-route="create().url"
            create-label="Add store"
            empty-state-message="No grocery stores yet. Add your favorite stores to organize ingredients by aisle."
            :items="groceryStores"
            #default="{ item: store }"
        >
            <Card>
                <CardContent class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">{{ store.name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ store.sections_count ?? 0 }}
                            {{
                                store.sections_count === 1
                                    ? 'section'
                                    : 'sections'
                            }}
                        </p>
                    </div>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="show(store.id)">View</Link>
                    </Button>
                </CardContent>
            </Card>
        </ResourceIndex>
    </AppLayout>
</template>
