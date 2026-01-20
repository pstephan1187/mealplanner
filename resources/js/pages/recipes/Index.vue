<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import Pagination, { type PaginationLink } from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index as recipesIndex, show } from '@/routes/recipes';
import { type BreadcrumbItem } from '@/types';

interface Ingredient {
    id: number;
    name: string;
}

interface Recipe {
    id: number;
    name: string;
    meal_types?: string[];
    photo_url?: string | null;
    prep_time_minutes?: number | null;
    cook_time_minutes?: number | null;
    servings?: number | null;
    ingredients?: Ingredient[];
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
    recipes: Paginated<Recipe>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recipes',
        href: recipesIndex().url,
    },
];

const recipeItems = computed(() => props.recipes.data ?? []);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Recipes" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Recipes"
                    description="Capture your favorites, prep times, and ingredients."
                />
                <Button as-child>
                    <Link :href="create()">Create recipe</Link>
                </Button>
            </div>

            <div v-if="recipeItems.length === 0">
                <Card>
                    <CardContent
                        class="flex flex-col items-start gap-3 py-10 text-sm text-muted-foreground"
                    >
                        <p>
                            No recipes yet. Start with a go-to dinner you love.
                        </p>
                        <Button as-child size="sm">
                            <Link :href="create()">Add your first recipe</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="recipe in recipeItems"
                    :key="recipe.id"
                    class="overflow-hidden"
                >
                    <div class="relative aspect-[4/3] bg-muted">
                        <img
                            v-if="recipe.photo_url"
                            :src="recipe.photo_url"
                            :alt="recipe.name"
                            class="size-full object-cover"
                        >
                        <div
                            v-else
                            class="flex size-full items-center justify-center text-sm text-muted-foreground"
                        >
                            No photo
                        </div>
                    </div>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-lg font-semibold">
                                    {{ recipe.name }}
                                </h3>
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="show(recipe)">View</Link>
                                </Button>
                            </div>
                            <div
                                v-if="recipe.meal_types?.length"
                                class="flex flex-wrap gap-2"
                            >
                                <Badge
                                    v-for="mealType in recipe.meal_types"
                                    :key="mealType"
                                    variant="secondary"
                                >
                                    {{ mealType }}
                                </Badge>
                            </div>
                        </div>

                        <div class="grid gap-2 text-sm text-muted-foreground">
                            <div class="flex items-center justify-between">
                                <span>Prep + cook</span>
                                <span>
                                    {{
                                        (recipe.prep_time_minutes ?? 0) +
                                        (recipe.cook_time_minutes ?? 0)
                                    }}
                                    min
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Servings</span>
                                <span>{{ recipe.servings ?? '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Ingredients</span>
                                <span>
                                    {{ recipe.ingredients?.length ?? 0 }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Pagination :links="recipes.links" />
        </div>
    </AppLayout>
</template>
