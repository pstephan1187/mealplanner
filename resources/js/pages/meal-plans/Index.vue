<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import Pagination, { type PaginationLink } from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateShort } from '@/lib/utils';
import { create, index as mealPlansIndex, show } from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';

interface MealPlan {
    id: number;
    name: string;
    start_date?: string | null;
    end_date?: string | null;
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
    mealPlans: Paginated<MealPlan>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meal Plans',
        href: mealPlansIndex().url,
    },
];

const mealPlanItems = computed(() => props.mealPlans.data ?? []);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Meal plans" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Meal plans"
                    description="Build weekly plans, auto-assign recipes, and refine later."
                />
                <Button as-child>
                    <Link :href="create()">Start meal plan</Link>
                </Button>
            </div>

            <div v-if="mealPlanItems.length === 0">
                <Card>
                    <CardContent
                        class="flex flex-col items-start gap-3 py-10 text-sm text-muted-foreground"
                    >
                        <p>No meal plans yet. Start with a week you want to prep.</p>
                        <Button as-child size="sm">
                            <Link :href="create()">Create your first plan</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="mealPlan in mealPlanItems"
                    :key="mealPlan.id"
                >
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
            </div>

            <Pagination :links="mealPlans.links" />
        </div>
    </AppLayout>
</template>
