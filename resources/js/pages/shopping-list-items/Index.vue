<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import Pagination, { type PaginationLink } from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index as shoppingListItemsIndex, show } from '@/routes/shopping-list-items';
import { type BreadcrumbItem } from '@/types';

interface Ingredient {
    id: number;
    name: string;
}

interface ShoppingListItem {
    id: number;
    shopping_list_id: number;
    quantity: string | number;
    unit: string;
    is_purchased: boolean;
    sort_order?: number | null;
    ingredient?: Ingredient | null;
}

interface Paginated<T> {
    data: T[];
    links?: PaginationLink[];
    meta?: {
        total?: number;
        from?: number | null;
        to?: number | null;
    };
}

const props = defineProps<{
    items: Paginated<ShoppingListItem>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shopping list items',
        href: shoppingListItemsIndex().url,
    },
];

const itemRows = computed(() => props.items.data ?? []);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Shopping list items" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Shopping list items"
                    description="Line items across all shopping lists."
                />
                <Button as-child>
                    <Link :href="create()">Add item</Link>
                </Button>
            </div>

            <div v-if="itemRows.length === 0">
                <Card>
                    <CardContent
                        class="flex flex-col items-start gap-3 py-10 text-sm text-muted-foreground"
                    >
                        <p>No items yet. Add an ingredient to get started.</p>
                        <Button as-child size="sm">
                            <Link :href="create()">Add your first item</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card v-for="item in itemRows" :key="item.id">
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
            </div>

            <Pagination :links="items.links" />
        </div>
    </AppLayout>
</template>
