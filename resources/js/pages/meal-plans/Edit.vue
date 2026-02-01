<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import ResourceForm from '@/components/ResourceForm.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import { edit, index as mealPlansIndex, update } from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';
import type { MealPlan } from '@/types/models';

const props = defineProps<{
    mealPlan: ResourceProp<MealPlan>;
}>();

const mealPlan = resolveResource(props.mealPlan);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meal Plans',
        href: mealPlansIndex().url,
    },
    {
        title: mealPlan.name,
        href: edit(mealPlan.id).url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit meal plan" />

        <ResourceForm
            title="Edit meal plan"
            description="Update dates or rename this plan."
            :back-route="mealPlansIndex().url"
            back-label="Back to meal plans"
            submit-label="Save changes"
            :form-action="update.form(mealPlan.id)"
            #default="{ errors }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Plan details</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-6 md:grid-cols-2">
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="name">Plan name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="mealPlan.name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="start_date">Start date</Label>
                        <Input
                            id="start_date"
                            name="start_date"
                            type="date"
                            :default-value="mealPlan.start_date ?? ''"
                        />
                        <InputError :message="errors.start_date" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="end_date">End date</Label>
                        <Input
                            id="end_date"
                            name="end_date"
                            type="date"
                            :default-value="mealPlan.end_date ?? ''"
                        />
                        <InputError :message="errors.end_date" />
                    </div>
                </CardContent>
            </Card>
        </ResourceForm>
    </AppLayout>
</template>
