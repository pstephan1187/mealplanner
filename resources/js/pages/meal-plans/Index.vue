<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ResourceIndex from '@/components/ResourceIndex.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateShort } from '@/lib/utils';
import { create, index as mealPlansIndex, show } from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';
import type { MealPlan, Paginated } from '@/types/models';

defineProps<{
    mealPlans: Paginated<MealPlan>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meal Plans',
        href: mealPlansIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Meal plans" />

        <ResourceIndex
            title="Meal plans"
            description="Build weekly plans, auto-assign recipes, and refine later."
            :create-route="create().url"
            create-label="Start meal plan"
            empty-state-message="No meal plans yet. Start with a week you want to prep."
            :items="mealPlans"
            #default="{ item: mealPlan }"
        >
            <Card>
                <CardContent class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold">
                            {{ mealPlan.name }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ formatDateShort(mealPlan.start_date) }}
                            <span v-if="mealPlan.end_date">-</span>
                            {{ formatDateShort(mealPlan.end_date) }}
                        </p>
                    </div>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="show(mealPlan.id)">View</Link>
                    </Button>
                </CardContent>
            </Card>
        </ResourceIndex>
    </AppLayout>
</template>
