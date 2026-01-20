<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

// Routes disabled - using hardcoded paths temporarily
const ingredientsIndex = () => ({ url: '/ingredients' });
const store = { form: () => ({ action: '/ingredients', method: 'post' }) };

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ingredients',
        href: ingredientsIndex().url,
    },
    {
        title: 'Create',
        href: '/ingredients/create',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create ingredient" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Create ingredient"
                    description="Add pantry staples and fresh produce you'll reuse."
                />
                <Button variant="ghost" as-child>
                    <Link :href="ingredientsIndex()">Back to ingredients</Link>
                </Button>
            </div>

            <Form
                v-bind="store.form()"
                class="max-w-xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Ingredient name</Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="Extra virgin olive oil"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="ingredientsIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Create ingredient
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
