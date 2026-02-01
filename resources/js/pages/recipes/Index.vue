<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ResourceIndex from '@/components/ResourceIndex.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index as recipesIndex, show } from '@/routes/recipes';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, Recipe } from '@/types/models';

defineProps<{
    recipes: Paginated<Recipe>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recipes',
        href: recipesIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Recipes" />

        <ResourceIndex
            title="Recipes"
            description="Capture your favorites, prep times, and ingredients."
            :create-route="create().url"
            create-label="Create recipe"
            empty-state-message="No recipes yet. Start with a go-to dinner you love."
            :items="recipes"
            #default="{ item: recipe }"
        >
            <Card class="overflow-hidden">
                <div class="relative aspect-[4/3] bg-muted">
                    <img
                        v-if="recipe.photo_url"
                        :src="recipe.photo_url"
                        :alt="recipe.name"
                        class="size-full object-cover"
                    />
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
        </ResourceIndex>
    </AppLayout>
</template>
