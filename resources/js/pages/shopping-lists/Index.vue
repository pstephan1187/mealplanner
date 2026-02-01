<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ResourceIndex from '@/components/ResourceIndex.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateShort } from '@/lib/utils';
import {
    create,
    index as shoppingListsIndex,
    show,
} from '@/routes/shopping-lists';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, ShoppingList } from '@/types/models';

defineProps<{
    shoppingLists: Paginated<ShoppingList>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shopping Lists',
        href: shoppingListsIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Shopping lists" />

        <ResourceIndex
            title="Shopping lists"
            description="Lists generated from meal plans and manually curated items."
            :create-route="create().url"
            create-label="Create list"
            empty-state-message="No shopping lists yet. Start from a meal plan."
            :items="shoppingLists"
            #default="{ item: shoppingList }"
        >
            <Card>
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
                            <span v-if="shoppingList.meal_plan?.end_date">
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
        </ResourceIndex>
    </AppLayout>
</template>
