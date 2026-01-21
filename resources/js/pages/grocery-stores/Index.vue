<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import Pagination, { type PaginationLink } from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface GroceryStore {
    id: number;
    name: string;
    sections_count?: number;
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
    groceryStores: Paginated<GroceryStore>;
}>();

const groceryStoresIndex = () => ({ url: '/grocery-stores' });
const create = () => ({ url: '/grocery-stores/create' });
const show = (store: { id: number }) => ({ url: `/grocery-stores/${store.id}` });

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grocery Stores',
        href: groceryStoresIndex().url,
    },
];

const storeItems = computed(() => props.groceryStores.data ?? []);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Grocery Stores" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Grocery Stores"
                    description="Manage your stores and their sections for organized shopping."
                />
                <Button as-child>
                    <Link :href="create()">Add store</Link>
                </Button>
            </div>

            <div v-if="storeItems.length === 0">
                <Card>
                    <CardContent
                        class="flex flex-col items-start gap-3 py-10 text-sm text-muted-foreground"
                    >
                        <p>
                            No grocery stores yet. Add your favorite stores to
                            organize ingredients by aisle.
                        </p>
                        <Button as-child size="sm">
                            <Link :href="create()">Add your first store</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <Card v-for="store in storeItems" :key="store.id">
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
                            <Link :href="show(store)">View</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Pagination :links="groceryStores.links" />
        </div>
    </AppLayout>
</template>
