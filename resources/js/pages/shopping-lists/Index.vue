<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import Pagination, { type PaginationLink } from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateShort } from '@/lib/utils';
import { create, index as shoppingListsIndex, show } from '@/routes/shopping-lists';
import { type BreadcrumbItem } from '@/types';

interface MealPlan {
    id: number;
    name: string;
    start_date?: string | null;
    end_date?: string | null;
}

interface ShoppingList {
    id: number;
    meal_plan_id: number;
    meal_plan?: MealPlan | null;
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
    shoppingLists: Paginated<ShoppingList>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shopping Lists',
        href: shoppingListsIndex().url,
    },
];

const shoppingListItems = computed(() => props.shoppingLists.data ?? []);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Shopping lists" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Shopping lists"
                    description="Lists generated from meal plans and manually curated items."
                />
                <Button as-child>
                    <Link :href="create()">Create list</Link>
                </Button>
            </div>

            <div v-if="shoppingListItems.length === 0">
                <Card>
                    <CardContent
                        class="flex flex-col items-start gap-3 py-10 text-sm text-muted-foreground"
                    >
                        <p>No shopping lists yet. Start from a meal plan.</p>
                        <Button as-child size="sm">
                            <Link :href="create()">Create your first list</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="shoppingList in shoppingListItems"
                    :key="shoppingList.id"
                >
                    <CardContent class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-lg font-semibold">
                                {{
                                    shoppingList.meal_plan?.name ??
                                    `Meal plan #${shoppingList.meal_plan_id}`
                                }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    formatDateShort(
                                        shoppingList.meal_plan?.start_date,
                                    )
                                }}
                                <span
                                    v-if="
                                        shoppingList.meal_plan?.end_date
                                    "
                                >
                                    -
                                </span>
                                {{
                                    formatDateShort(
                                        shoppingList.meal_plan?.end_date,
                                    )
                                }}
                            </p>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="show(shoppingList.id)">View</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Pagination :links="shoppingLists.links" />
        </div>
    </AppLayout>
</template>
