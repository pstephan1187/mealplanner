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
import { create, index as mealPlansIndex, store } from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    defaultStartDate: string;
    defaultEndDate: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meal Plans',
        href: mealPlansIndex().url,
    },
    {
        title: 'Create',
        href: create().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create meal plan" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div class="space-y-3">
                <Heading
                    title="Create meal plan"
                    description="A full-page wizard to outline the week before assigning recipes."
                />
                <p class="text-sm text-muted-foreground">
                    Recipes are auto-assigned after you save the plan. You can
                    adjust every meal afterward.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Plan progress</CardTitle>
                </CardHeader>
                <CardContent>
                    <ol class="grid gap-4 md:grid-cols-3">
                        <li class="rounded-lg border border-border/70 p-4">
                            <p class="text-sm font-semibold text-foreground">
                                Step 1
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Name the plan and set the week.
                            </p>
                        </li>
                        <li
                            class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
                        >
                            Step 2: Auto-assign recipes and tweak manually.
                        </li>
                        <li
                            class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
                        >
                            Step 3: Review meals and generate shopping.
                        </li>
                    </ol>
                </CardContent>
            </Card>

            <Form
                v-bind="store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Step 1: Plan basics</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-6 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="name">Plan name (optional)</Label>
                            <Input
                                id="name"
                                name="name"
                                placeholder="Leave blank to use date range"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="start_date">Start date</Label>
                            <Input
                                id="start_date"
                                name="start_date"
                                type="date"
                                :default-value="props.defaultStartDate"
                                required
                            />
                            <InputError :message="errors.start_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_date">End date</Label>
                            <Input
                                id="end_date"
                                name="end_date"
                                type="date"
                                :default-value="props.defaultEndDate"
                                required
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
                        Save and auto-assign recipes
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
