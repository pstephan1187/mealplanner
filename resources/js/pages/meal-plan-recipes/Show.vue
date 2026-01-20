<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { destroy, edit, index as mealPlanRecipesIndex, show } from '@/routes/meal-plan-recipes';
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

const props = defineProps<{
    mealPlanRecipe: ResourceProp<MealPlanRecipe>;
}>();

const mealPlanRecipe = resolveResource(props.mealPlanRecipe);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meal plan recipes',
        href: mealPlanRecipesIndex().url,
    },
    {
        title: mealPlanRecipe.recipe?.name ?? 'Assignment',
        href: show(mealPlanRecipe.id).url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Meal plan recipe" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    :title="mealPlanRecipe.recipe?.name ?? 'Meal plan recipe'"
                    description="Assignment details for this meal."
                />
                <div class="flex flex-wrap gap-2">
                    <Button variant="secondary" as-child>
                        <Link :href="edit(mealPlanRecipe.id)">Edit assignment</Link>
                    </Button>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive">Delete</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="destroy.form(mealPlanRecipe.id)"
                                v-slot="{ processing }"
                                class="space-y-6"
                            >
                                <DialogHeader class="space-y-3">
                                    <DialogTitle
                                        >Delete this assignment?</DialogTitle
                                    >
                                    <DialogDescription>
                                        This removes the recipe from the
                                        selected day.
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
                                        Delete assignment
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <Card class="max-w-xl">
                <CardHeader>
                    <CardTitle>Assignment details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm text-muted-foreground">
                    <p>
                        Meal type: {{ mealPlanRecipe.meal_type }}
                    </p>
                    <p>Date: {{ mealPlanRecipe.date }}</p>
                    <p>Servings: {{ mealPlanRecipe.servings }}</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
