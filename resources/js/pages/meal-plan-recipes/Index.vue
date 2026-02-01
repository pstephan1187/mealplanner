<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ResourceIndex from '@/components/ResourceIndex.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateShort } from '@/lib/utils';
import {
    create,
    index as mealPlanRecipesIndex,
    show,
} from '@/routes/meal-plan-recipes';
import { type BreadcrumbItem } from '@/types';
import type { MealPlanRecipe, Paginated } from '@/types/models';

defineProps<{
    mealPlanRecipes: Paginated<MealPlanRecipe>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meal plan recipes',
        href: mealPlanRecipesIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Meal plan recipes" />

        <ResourceIndex
            title="Meal plan recipes"
            description="Assignments for specific meal plan days."
            :create-route="create().url"
            create-label="Add recipe"
            empty-state-message="No assignments yet. Add recipes to a plan."
            :items="mealPlanRecipes"
            #default="{ item: meal }"
        >
            <Card>
                <CardContent class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold">
                            {{ meal.recipe?.name ?? 'Recipe' }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ formatDateShort(meal.date) }} -
                            {{ meal.meal_type }}
                        </p>
                    </div>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="show(meal.id)">View</Link>
                    </Button>
                </CardContent>
            </Card>
        </ResourceIndex>
    </AppLayout>
</template>
