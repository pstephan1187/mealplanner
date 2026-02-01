<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { MEAL_TYPES } from '@/lib/constants';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import {
    store as storeMealPlanRecipe,
    update as updateMealPlanRecipe,
} from '@/routes/meal-plan-recipes';
import { edit, index as mealPlansIndex, show } from '@/routes/meal-plans';
import { create as createRecipe } from '@/routes/recipes';
import {
    show as showShoppingList,
    store as storeShoppingList,
} from '@/routes/shopping-lists';
import { type BreadcrumbItem } from '@/types';
import {
    resolveCollection,
    type MealPlan,
    type MealPlanRecipe,
    type Recipe,
    type ResourceCollection,
} from '@/types/models';

interface RecipeOption extends Pick<
    Recipe,
    'id' | 'name' | 'servings' | 'meal_types' | 'photo_url'
> {
    servings: number;
}

const props = defineProps<{
    mealPlan: ResourceProp<MealPlan>;
    recipes: ResourceCollection<RecipeOption>;
}>();

const mealPlan = computed(() => resolveResource(props.mealPlan));

const recipeOptions = computed(() => resolveCollection(props.recipes));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meal Plans',
        href: mealPlansIndex().url,
    },
    {
        title: mealPlan.value.name,
        href: show(mealPlan.value.id).url,
    },
];

const assignmentDialogOpen = ref(false);
const activeSlot = ref<{
    dateKey: string;
    label: string;
    mealType: string;
} | null>(null);
const activeMealPlanRecipeId = ref<number | null>(null);
const shouldSyncServings = ref(true);
const isEditing = computed(() => activeMealPlanRecipeId.value !== null);

const form = useForm({
    meal_plan_id: null as number | null,
    recipe_id: null as number | null,
    date: null as string | null,
    meal_type: null as string | null,
    servings: 1,
});

const shoppingListForm = useForm({
    meal_plan_id: null as number | null,
});

const parseDate = (value: string): Date => new Date(`${value}T00:00:00`);

const formatDateKey = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const formatDisplayDate = (date: Date): string =>
    date.toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });

const weekDays = computed(() => {
    if (!mealPlan.value.start_date) {
        return [];
    }

    const start = parseDate(mealPlan.value.start_date);
    const end = mealPlan.value.end_date
        ? parseDate(mealPlan.value.end_date)
        : null;
    const days: { date: Date; key: string; label: string }[] = [];

    for (let i = 0; i < 7; i += 1) {
        const date = new Date(start);
        date.setDate(start.getDate() + i);

        if (end && date > end) {
            break;
        }

        days.push({
            date,
            key: formatDateKey(date),
            label: formatDisplayDate(date),
        });
    }

    return days;
});

const mealPlanRecipes = computed(() => mealPlan.value.meal_plan_recipes ?? []);

const selectedRecipe = computed(
    () =>
        recipeOptions.value.find((recipe) => recipe.id === form.recipe_id) ??
        null,
);

watch(selectedRecipe, (recipe) => {
    if (recipe) {
        if (shouldSyncServings.value) {
            form.servings = recipe.servings ?? 1;
        }
    }
});

const mealsByDateType = computed(() => {
    const map = new Map<string, Record<string, MealPlanRecipe[]>>();

    mealPlanRecipes.value.forEach((recipe) => {
        const dateKey = recipe.date?.split('T')[0] ?? recipe.date;

        if (!map.has(dateKey)) {
            map.set(dateKey, {});
        }

        const dayMap = map.get(dateKey) ?? {};

        if (!dayMap[recipe.meal_type]) {
            dayMap[recipe.meal_type] = [];
        }

        dayMap[recipe.meal_type].push(recipe);
        map.set(dateKey, dayMap);
    });

    return map;
});

const getMealsForDay = (dateKey: string, mealType: string) => {
    const dayMap = mealsByDateType.value.get(dateKey);
    return dayMap?.[mealType] ?? [];
};

const formatRange = computed(() => {
    if (!mealPlan.value.start_date) {
        return '';
    }

    const start = parseDate(mealPlan.value.start_date);
    const end = mealPlan.value.end_date
        ? parseDate(mealPlan.value.end_date)
        : null;

    const startLabel = formatDisplayDate(start);
    const endLabel = end ? formatDisplayDate(end) : '';

    return endLabel ? `${startLabel} - ${endLabel}` : startLabel;
});

const uniqueRecipeCount = computed(() => {
    const ids = mealPlanRecipes.value
        .map((meal) => meal.recipe?.id)
        .filter((id): id is number => typeof id === 'number');

    return new Set(ids).size;
});

const openAssignment = (dateKey: string, mealType: string, label: string) => {
    form.clearErrors();
    activeMealPlanRecipeId.value = null;
    shouldSyncServings.value = true;
    form.meal_plan_id = mealPlan.value.id;
    form.date = dateKey;
    form.meal_type = mealType;
    form.recipe_id = recipeOptions.value[0]?.id ?? null;
    form.servings = recipeOptions.value[0]?.servings ?? 1;
    activeSlot.value = { dateKey, mealType, label };
    assignmentDialogOpen.value = true;
};

const openEditAssignment = (
    meal: MealPlanRecipe,
    dateKey: string,
    mealType: string,
    label: string,
) => {
    form.clearErrors();
    activeMealPlanRecipeId.value = meal.id;
    shouldSyncServings.value = false;
    form.meal_plan_id = mealPlan.value.id;
    form.date = dateKey;
    form.meal_type = mealType;
    form.recipe_id = meal.recipe?.id ?? meal.recipe_id ?? null;
    form.servings = meal.servings;
    activeSlot.value = { dateKey, mealType, label };
    assignmentDialogOpen.value = true;
};

const submitAssignment = () => {
    const onSuccess = () => {
        assignmentDialogOpen.value = false;
        activeSlot.value = null;
        activeMealPlanRecipeId.value = null;
        shouldSyncServings.value = true;
        form.reset();
    };

    if (activeMealPlanRecipeId.value) {
        form.put(updateMealPlanRecipe(activeMealPlanRecipeId.value).url, {
            preserveScroll: true,
            onSuccess,
        });
        return;
    }

    form.post(storeMealPlanRecipe().url, {
        preserveScroll: true,
        onSuccess,
    });
};

const createShoppingListFromPlan = () => {
    shoppingListForm.meal_plan_id = mealPlan.value.id;
    shoppingListForm.post(storeShoppingList().url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="mealPlan.name" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    :title="mealPlan.name"
                    description="Swipe through the week and adjust meals anytime."
                />
                <div class="flex flex-wrap gap-2">
                    <Button variant="secondary" as-child>
                        <Link :href="edit(mealPlan.id)">Edit plan</Link>
                    </Button>
                    <Button v-if="mealPlan.shopping_list" as-child>
                        <Link
                            :href="showShoppingList(mealPlan.shopping_list.id)"
                        >
                            View list
                        </Link>
                    </Button>
                    <Button
                        v-else
                        type="button"
                        :disabled="shoppingListForm.processing"
                        @click="createShoppingListFromPlan"
                    >
                        {{
                            shoppingListForm.processing
                                ? 'Creating...'
                                : 'Create list'
                        }}
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Week view</CardTitle>
                    <p class="text-sm text-muted-foreground">
                        {{ formatRange }}
                    </p>
                </CardHeader>
                <CardContent>
                    <div
                        class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-4 [scrollbar-width:none] md:grid md:grid-cols-7 md:overflow-visible"
                    >
                        <div
                            v-for="day in weekDays"
                            :key="day.key"
                            class="min-w-[240px] snap-start rounded-xl border border-border/70 bg-card p-4 md:min-w-0"
                        >
                            <div class="mb-3">
                                <p class="text-sm font-semibold">
                                    {{ day.label }}
                                </p>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="mealType in MEAL_TYPES"
                                    :key="mealType"
                                    class="space-y-2"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs font-semibold text-muted-foreground uppercase"
                                    >
                                        <span>{{ mealType }}</span>
                                    </div>

                                    <button
                                        v-if="
                                            getMealsForDay(day.key, mealType)
                                                .length === 0
                                        "
                                        type="button"
                                        :data-test="`meal-slot-${day.key}-${mealType.toLowerCase()}`"
                                        class="w-full rounded-md border border-dashed border-border px-2 py-3 text-left text-xs text-muted-foreground transition hover:border-primary/40 hover:text-foreground"
                                        @click="
                                            openAssignment(
                                                day.key,
                                                mealType,
                                                day.label,
                                            )
                                        "
                                    >
                                        No meal yet
                                    </button>

                                    <div
                                        v-for="meal in getMealsForDay(
                                            day.key,
                                            mealType,
                                        )"
                                        :key="meal.id"
                                        class="rounded-md border border-border/70 text-left text-sm transition hover:border-primary/40"
                                    >
                                        <button
                                            type="button"
                                            class="w-full px-3 py-2 text-left"
                                            :data-test="`meal-item-${meal.id}`"
                                            @click="
                                                openEditAssignment(
                                                    meal,
                                                    day.key,
                                                    mealType,
                                                    day.label,
                                                )
                                            "
                                        >
                                            <p class="font-medium">
                                                {{
                                                    meal.recipe?.name ??
                                                    'Recipe'
                                                }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Servings: {{ meal.servings }}
                                            </p>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Plan summary</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-border/70 p-4">
                        <p class="text-sm text-muted-foreground">
                            Meals planned
                        </p>
                        <p class="text-2xl font-semibold">
                            {{ mealPlanRecipes.length }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border/70 p-4">
                        <p class="text-sm text-muted-foreground">
                            Recipes unique
                        </p>
                        <p class="text-2xl font-semibold">
                            {{ uniqueRecipeCount }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border/70 p-4">
                        <p class="text-sm text-muted-foreground">
                            Shopping list
                        </p>
                        <p class="text-2xl font-semibold">
                            {{ mealPlan.shopping_list ? 'Ready' : 'Draft' }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="assignmentDialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ isEditing ? 'Edit meal' : 'Assign a meal' }}
                    </DialogTitle>
                    <DialogDescription>
                        <span v-if="activeSlot">
                            {{ activeSlot.mealType }} • {{ activeSlot.label }}
                        </span>
                        <span v-else>
                            Choose a recipe and servings for this slot.
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="recipeOptions.length === 0"
                    class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
                >
                    <p class="mb-3">
                        You do not have any recipes yet. Add one to start
                        planning meals.
                    </p>
                    <Button size="sm" as-child>
                        <Link :href="createRecipe()">Create recipe</Link>
                    </Button>
                </div>

                <form
                    v-else
                    class="space-y-4"
                    @submit.prevent="submitAssignment"
                >
                    <div class="grid gap-2">
                        <Label for="recipe_id">Recipe</Label>
                        <select
                            id="recipe_id"
                            name="recipe_id"
                            v-model="form.recipe_id"
                            @change="shouldSyncServings = true"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        >
                            <option value="">Select a recipe</option>
                            <option
                                v-for="recipeOption in recipeOptions"
                                :key="recipeOption.id"
                                :value="recipeOption.id"
                            >
                                {{ recipeOption.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.recipe_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="servings">Servings</Label>
                        <Input
                            id="servings"
                            name="servings"
                            type="number"
                            min="1"
                            v-model="form.servings"
                        />
                        <InputError :message="form.errors.servings" />
                    </div>

                    <InputError :message="form.errors.date" />

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary" type="button">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            :disabled="form.processing || !form.recipe_id"
                        >
                            {{ isEditing ? 'Save changes' : 'Add meal' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
