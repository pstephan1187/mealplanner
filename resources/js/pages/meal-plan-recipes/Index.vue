<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import Pagination, { type PaginationLink } from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateShort } from '@/lib/utils';
import { create, index as mealPlanRecipesIndex, show } from '@/routes/meal-plan-recipes';
import { type BreadcrumbItem } from '@/types';

interface Recipe {
    id: number;
    name: string;
}

interface MealPlanRecipe {
    id: number;
    meal_plan_id: number;
    recipe_id: number;
    date: string;
    meal_type: string;
    servings: number;
    recipe?: Recipe | null;
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
    mealPlanRecipes: Paginated<MealPlanRecipe>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meal plan recipes',
        href: mealPlanRecipesIndex().url,
    },
];

const planRecipes = computed(() => props.mealPlanRecipes.data ?? []);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Meal plan recipes" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Meal plan recipes"
                    description="Assignments for specific meal plan days."
                />
                <Button as-child>
                    <Link :href="create()">Add recipe</Link>
                </Button>
            </div>

            <div v-if="planRecipes.length === 0">
                <Card>
                    <CardContent
                        class="flex flex-col items-start gap-3 py-10 text-sm text-muted-foreground"
                    >
                        <p>No assignments yet. Add recipes to a plan.</p>
                        <Button as-child size="sm">
                            <Link :href="create()">Add your first recipe</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card v-for="meal in planRecipes" :key="meal.id">
                    <CardContent class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-lg font-semibold">
                                {{ meal.recipe?.name ?? 'Recipe' }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDateShort(meal.date) }} - {{ meal.meal_type }}
                            </p>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="show(meal.id)">View</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Pagination :links="mealPlanRecipes.links" />
        </div>
    </AppLayout>
</template>
