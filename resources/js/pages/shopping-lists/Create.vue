<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
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
import { create, index as shoppingListsIndex, store } from '@/routes/shopping-lists';
import { type BreadcrumbItem } from '@/types';

interface MealPlan {
    id: number;
    name: string;
    start_date?: string | null;
    end_date?: string | null;
}

type ResourceCollection<T> = { data: T[] } | T[];

const props = defineProps<{
    mealPlans: ResourceCollection<MealPlan>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shopping Lists',
        href: shoppingListsIndex().url,
    },
    {
        title: 'Create',
        href: create().url,
    },
];

const page = usePage();
const preselectedMealPlanId = computed(() => {
    const query = page.url.split('?')[1] ?? '';
    const params = new URLSearchParams(query);
    const value = params.get('meal_plan_id');

    return value ? Number(value) : null;
});

const selectedMealPlanId = ref<number | ''>(preselectedMealPlanId.value ?? '');

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
        <Head title="Create shopping list" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Create shopping list"
                    description="Pick a meal plan to generate a list you can customize."
                />
                <Button variant="ghost" as-child>
                    <Link :href="shoppingListsIndex()"
                        >Back to shopping lists</Link
                    >
                </Button>
            </div>

            <Form
                v-bind="store.form()"
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
                            required
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
                        Create list
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
