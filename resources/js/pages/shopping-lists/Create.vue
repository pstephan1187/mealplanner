<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import InputError from '@/components/InputError.vue';
import ResourceForm from '@/components/ResourceForm.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateShort } from '@/lib/utils';
import {
    create,
    index as shoppingListsIndex,
    store,
} from '@/routes/shopping-lists';
import { type BreadcrumbItem } from '@/types';
import {
    resolveCollection,
    type MealPlan,
    type ResourceCollection,
} from '@/types/models';

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

const mealPlanOptions = computed(() => resolveCollection(props.mealPlans));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create shopping list" />

        <ResourceForm
            title="Create shopping list"
            description="Pick a meal plan to generate a list you can customize."
            :back-route="shoppingListsIndex().url"
            back-label="Back to shopping lists"
            submit-label="Create list"
            :form-action="store.form()"
            #default="{ errors }"
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
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        required
                    >
                        <option value="">Choose a plan</option>
                        <option
                            v-for="mealPlan in mealPlanOptions"
                            :key="mealPlan.id"
                            :value="mealPlan.id"
                        >
                            {{ mealPlan.name }} (
                            {{ formatDateShort(mealPlan.start_date) }}
                            <span v-if="mealPlan.end_date">-</span>
                            {{ formatDateShort(mealPlan.end_date) }})
                        </option>
                    </select>
                    <InputError :message="errors.meal_plan_id" />
                </CardContent>
            </Card>
        </ResourceForm>
    </AppLayout>
</template>
