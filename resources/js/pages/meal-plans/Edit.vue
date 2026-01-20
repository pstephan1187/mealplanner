<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import { edit, index as mealPlansIndex, update } from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';

interface MealPlan {
    id: number;
    name: string;
    start_date?: string | null;
    end_date?: string | null;
}

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

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Edit meal plan"
                    description="Update dates or rename this plan."
                />
                <Button variant="ghost" as-child>
                    <Link :href="mealPlansIndex()">Back to meal plans</Link>
                </Button>
            </div>

            <Form
                v-bind="update.form(mealPlan.id)"
                class="space-y-6"
                v-slot="{ errors, processing }"
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

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="mealPlansIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
