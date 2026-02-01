<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { MEAL_TYPES } from '@/lib/constants';
import {
    formatDateShort,
    resolveResource,
    type ResourceProp,
} from '@/lib/utils';
import {
    edit,
    index as mealPlanRecipesIndex,
    update,
} from '@/routes/meal-plan-recipes';
import { type BreadcrumbItem } from '@/types';
import {
    resolveCollection,
    type MealPlan,
    type MealPlanRecipe,
    type Recipe,
    type ResourceCollection,
} from '@/types/models';

const props = defineProps<{
    mealPlanRecipe: ResourceProp<MealPlanRecipe>;
    mealPlans: ResourceCollection<MealPlan>;
    recipes: ResourceCollection<Recipe>;
}>();

const mealPlanRecipe = resolveResource(props.mealPlanRecipe);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meal plan recipes',
        href: mealPlanRecipesIndex().url,
    },
    {
        title: 'Edit',
        href: edit(mealPlanRecipe.id).url,
    },
];

const mealPlanOptions = computed(() => resolveCollection(props.mealPlans));

const recipeOptions = computed(() => resolveCollection(props.recipes));

const selectedMealPlanId = ref<number | ''>(mealPlanRecipe.meal_plan_id);
const selectedRecipeId = ref<number | ''>(mealPlanRecipe.recipe_id);
const selectedMealType = ref<string | ''>(mealPlanRecipe.meal_type);

const selectedMealPlan = computed(() => {
    if (selectedMealPlanId.value === '') {
        return null;
    }

    return (
        mealPlanOptions.value.find(
            (plan) => plan.id === selectedMealPlanId.value,
        ) ?? null
    );
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit meal plan recipe" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Edit meal plan recipe"
                    description="Adjust the day, meal type, or servings."
                />
                <Button variant="ghost" as-child>
                    <Link :href="mealPlanRecipesIndex()">
                        Back to assignments
                    </Link>
                </Button>
            </div>

            <Form
                v-bind="update.form(mealPlanRecipe.id)"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Assignment details</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-6 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="meal_plan_id">Meal plan</Label>
                            <select
                                id="meal_plan_id"
                                name="meal_plan_id"
                                v-model="selectedMealPlanId"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >
                                <option value="">Choose a plan</option>
                                <option
                                    v-for="plan in mealPlanOptions"
                                    :key="plan.id"
                                    :value="plan.id"
                                >
                                    {{ plan.name }} (
                                    {{ formatDateShort(plan.start_date) }}
                                    <span v-if="plan.end_date">-</span>
                                    {{ formatDateShort(plan.end_date) }})
                                </option>
                            </select>
                            <InputError :message="errors.meal_plan_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="recipe_id">Recipe</Label>
                            <select
                                id="recipe_id"
                                name="recipe_id"
                                v-model="selectedRecipeId"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >
                                <option value="">Choose a recipe</option>
                                <option
                                    v-for="recipe in recipeOptions"
                                    :key="recipe.id"
                                    :value="recipe.id"
                                >
                                    {{ recipe.name }}
                                </option>
                            </select>
                            <InputError :message="errors.recipe_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="date">Date</Label>
                            <Input
                                id="date"
                                name="date"
                                type="date"
                                :default-value="mealPlanRecipe.date"
                                :min="selectedMealPlan?.start_date ?? undefined"
                                :max="selectedMealPlan?.end_date ?? undefined"
                            />
                            <InputError :message="errors.date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="meal_type">Meal type</Label>
                            <select
                                id="meal_type"
                                name="meal_type"
                                v-model="selectedMealType"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >
                                <option value="">Choose meal type</option>
                                <option
                                    v-for="mealType in MEAL_TYPES"
                                    :key="mealType"
                                    :value="mealType"
                                >
                                    {{ mealType }}
                                </option>
                            </select>
                            <InputError :message="errors.meal_type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="servings">Servings</Label>
                            <Input
                                id="servings"
                                name="servings"
                                type="number"
                                min="1"
                                :default-value="mealPlanRecipe.servings"
                            />
                            <InputError :message="errors.servings" />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="mealPlanRecipesIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
