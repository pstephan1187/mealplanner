<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, ClipboardList, CookingPot, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateShort } from '@/lib/utils';
import { dashboard } from '@/routes';
import {
    create as createMealPlan,
    index as mealPlansIndex,
    show as showMealPlan,
} from '@/routes/meal-plans';
import {
    create as createRecipe,
    index as recipesIndex,
    show as showRecipe,
} from '@/routes/recipes';
import { create as createShoppingList } from '@/routes/shopping-lists';
import { type BreadcrumbItem } from '@/types';
import {
    resolveCollection,
    type MealPlan,
    type Recipe,
    type ResourceCollection,
} from '@/types/models';

interface Props {
    stats: {
        recipes: number;
        meal_plans: number;
        shopping_lists: number;
    };
    recentRecipes: ResourceCollection<Recipe>;
    recentMealPlans: ResourceCollection<MealPlan>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const recentRecipes = computed(() => resolveCollection(props.recentRecipes));

const recentMealPlans = computed(() =>
    resolveCollection(props.recentMealPlans),
);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Dashboard"
                    description="Plan the week, cook with confidence, and keep shopping tidy."
                />
                <div class="flex flex-wrap gap-2">
                    <Button variant="secondary" as-child>
                        <Link :href="recipesIndex()"> Browse recipes </Link>
                    </Button>
                    <Button as-child>
                        <Link :href="createMealPlan()">
                            <Plus class="size-4" />
                            Start a meal plan
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary"
                        >
                            <CookingPot class="size-5" />
                        </div>
                        <div>
                            <CardTitle class="text-lg">Recipes</CardTitle>
                            <p class="text-sm text-muted-foreground">
                                {{ stats.recipes }} total
                            </p>
                        </div>
                    </CardHeader>
                    <CardContent class="flex items-center justify-between">
                        <Badge variant="secondary">Library</Badge>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="createRecipe()">Add new</Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary"
                        >
                            <CalendarDays class="size-5" />
                        </div>
                        <div>
                            <CardTitle class="text-lg">Meal plans</CardTitle>
                            <p class="text-sm text-muted-foreground">
                                {{ stats.meal_plans }} active
                            </p>
                        </div>
                    </CardHeader>
                    <CardContent class="flex items-center justify-between">
                        <Badge variant="secondary">Weekly flow</Badge>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="createMealPlan()">Start</Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary"
                        >
                            <ClipboardList class="size-5" />
                        </div>
                        <div>
                            <CardTitle class="text-lg">Shopping</CardTitle>
                            <p class="text-sm text-muted-foreground">
                                {{ stats.shopping_lists }} lists
                            </p>
                        </div>
                    </CardHeader>
                    <CardContent class="flex items-center justify-between">
                        <Badge variant="secondary">Prep ready</Badge>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="createShoppingList()"> New list </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between"
                    >
                        <CardTitle>Recent recipes</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="recipesIndex()">View all</Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-if="recentRecipes.length === 0"
                            class="rounded-lg border border-dashed border-border p-6 text-sm text-muted-foreground"
                        >
                            No recipes yet. Start your library with a favorite.
                        </div>
                        <div
                            v-for="recipe in recentRecipes"
                            :key="recipe.id"
                            class="flex items-center justify-between gap-4 rounded-lg border border-border/80 p-4"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex size-12 items-center justify-center overflow-hidden rounded-lg bg-muted text-xs font-semibold text-muted-foreground uppercase"
                                >
                                    <img
                                        v-if="recipe.photo_url"
                                        :src="recipe.photo_url"
                                        :alt="recipe.name"
                                        class="size-full object-cover"
                                    />
                                    <span v-else>
                                        {{ recipe.name.slice(0, 2) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium">
                                        {{ recipe.name }}
                                    </p>
                                </div>
                            </div>
                            <Button variant="ghost" size="sm" as-child>
                                <Link :href="showRecipe(recipe)">View</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between"
                    >
                        <CardTitle>Recent meal plans</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="mealPlansIndex()">View all</Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-if="recentMealPlans.length === 0"
                            class="rounded-lg border border-dashed border-border p-6 text-sm text-muted-foreground"
                        >
                            No meal plans yet. Start a week plan to see it here.
                        </div>
                        <div
                            v-for="mealPlan in recentMealPlans"
                            :key="mealPlan.id"
                            class="flex items-center justify-between gap-4 rounded-lg border border-border/80 p-4"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ mealPlan.name }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ formatDateShort(mealPlan.start_date) }}
                                    <span v-if="mealPlan.end_date">-</span>
                                    {{ formatDateShort(mealPlan.end_date) }}
                                </p>
                            </div>
                            <Button variant="ghost" size="sm" as-child>
                                <Link :href="showMealPlan(mealPlan.id)"
                                    >View</Link
                                >
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
