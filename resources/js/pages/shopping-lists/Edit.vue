<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import { edit, index as shoppingListsIndex, update } from '@/routes/shopping-lists';
import { type BreadcrumbItem } from '@/types';

interface MealPlan {
    id: number;
    name: string;
    start_date?: string | null;
    end_date?: string | null;
}

interface ShoppingList {
    id: number;
    meal_plan_id: number;
}

type ResourceCollection<T> = { data: T[] } | T[];

const props = defineProps<{
    shoppingList: ResourceProp<ShoppingList>;
    mealPlans: ResourceCollection<MealPlan>;
}>();

const shoppingList = resolveResource(props.shoppingList);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shopping Lists',
        href: shoppingListsIndex().url,
    },
    {
        title: 'Edit',
        href: edit(shoppingList.id).url,
    },
];

const selectedMealPlanId = ref<number | ''>(shoppingList.meal_plan_id);

const mealPlanOptions = computed(() =>
    Array.isArray(props.mealPlans) ? props.mealPlans : props.mealPlans.data ?? [],
);

const formatDate = (value?: string | null): string => {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit shopping list" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Edit shopping list"
                    description="Switch to another meal plan if needed."
                />
                <Button variant="ghost" as-child>
                    <Link :href="shoppingListsIndex()">
                        Back to shopping lists
                    </Link>
                </Button>
            </div>

            <Form
                v-bind="update.form(shoppingList.id)"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Plan selection</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-2 md:max-w-xl">
                        <Label for="meal_plan_id">Meal plan</Label>
                        <select
                            id="meal_plan_id"
                            name="meal_plan_id"
                            v-model="selectedMealPlanId"
                            class="border-input dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option value="">Choose a plan</option>
                            <option
                                v-for="mealPlan in mealPlanOptions"
                                :key="mealPlan.id"
                                :value="mealPlan.id"
                            >
                                {{ mealPlan.name }} (
                                {{ formatDate(mealPlan.start_date) }}
                                <span v-if="mealPlan.end_date">-</span>
                                {{ formatDate(mealPlan.end_date) }})
                            </option>
                        </select>
                        <InputError :message="errors.meal_plan_id" />
                    </CardContent>
                </Card>

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="shoppingListsIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
