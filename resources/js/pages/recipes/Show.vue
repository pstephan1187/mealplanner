<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import { destroy, edit, index as recipesIndex, show } from '@/routes/recipes';
import { type BreadcrumbItem } from '@/types';
import type { Recipe } from '@/types/models';

const props = defineProps<{
    recipe: ResourceProp<Recipe>;
}>();

const recipe = resolveResource(props.recipe);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recipes',
        href: recipesIndex().url,
    },
    {
        title: recipe.name,
        href: show(recipe).url,
    },
];

const totalTime = computed(
    () => (recipe.prep_time_minutes ?? 0) + (recipe.cook_time_minutes ?? 0),
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="recipe.name" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    :title="recipe.name"
                    description="Full recipe details, ingredients, and timing."
                />
                <div class="flex flex-wrap gap-2">
                    <Button variant="secondary" as-child>
                        <Link :href="edit(recipe)">Edit recipe</Link>
                    </Button>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive">Delete</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="destroy.form(recipe)"
                                v-slot="{ processing }"
                                class="space-y-6"
                            >
                                <DialogHeader class="space-y-3">
                                    <DialogTitle
                                        >Delete this recipe?</DialogTitle
                                    >
                                    <DialogDescription>
                                        This will remove the recipe and its
                                        ingredient list from your library.
                                    </DialogDescription>
                                </DialogHeader>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="processing"
                                    >
                                        Delete recipe
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
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
                            Add a square photo to make this recipe pop.
                        </div>
                    </div>
                    <CardContent class="space-y-6">
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="mealType in recipe.meal_types"
                                :key="mealType"
                                variant="secondary"
                            >
                                {{ mealType }}
                            </Badge>
                        </div>

                        <div class="grid gap-3 text-sm text-muted-foreground">
                            <div class="flex items-center justify-between">
                                <span>Flavor profile</span>
                                <span class="font-medium text-foreground">
                                    {{ recipe.flavor_profile }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Total time</span>
                                <span class="font-medium text-foreground">
                                    {{ totalTime }} min
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Servings</span>
                                <span class="font-medium text-foreground">
                                    {{ recipe.servings }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Prep</span>
                                <span class="font-medium text-foreground">
                                    {{ recipe.prep_time_minutes ?? 0 }} min
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Cook</span>
                                <span class="font-medium text-foreground">
                                    {{ recipe.cook_time_minutes ?? 0 }} min
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Ingredients</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-if="!recipe.ingredients?.length"
                                class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
                            >
                                No ingredients yet. Add them in the edit screen.
                            </div>
                            <div
                                v-for="ingredient in recipe.ingredients"
                                :key="ingredient.id"
                                class="flex items-start justify-between gap-4 rounded-lg border border-border/70 p-3 text-sm"
                            >
                                <div>
                                    <p class="font-medium">
                                        {{ ingredient.name }}
                                    </p>
                                    <p
                                        v-if="ingredient.pivot?.note"
                                        class="text-muted-foreground"
                                    >
                                        {{ ingredient.pivot.note }}
                                    </p>
                                </div>
                                <div class="text-right text-muted-foreground">
                                    <p>
                                        {{ ingredient.pivot?.quantity ?? '-' }}
                                        {{ ingredient.pivot?.unit }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Instructions</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p
                                class="text-sm leading-relaxed whitespace-pre-line text-muted-foreground"
                            >
                                {{ recipe.instructions }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
