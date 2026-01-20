<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';

// Routes disabled - using hardcoded paths temporarily
const ingredientsIndex = () => ({ url: '/ingredients' });
const edit = (ingredient: { id: number }) => ({ url: `/ingredients/${ingredient.id}/edit` });
const update = {
    form: (ingredient: { id: number }) => ({
        action: `/ingredients/${ingredient.id}`,
        method: 'patch',
    }),
};

interface Ingredient {
    id: number;
    name: string;
}

const props = defineProps<{
    ingredient: ResourceProp<Ingredient>;
}>();

const ingredient = resolveResource(props.ingredient);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ingredients',
        href: ingredientsIndex().url,
    },
    {
        title: ingredient.name,
        href: edit(ingredient).url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit ingredient" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Edit ingredient"
                    description="Rename or refine this pantry item."
                />
                <Button variant="ghost" as-child>
                    <Link :href="ingredientsIndex()">Back to ingredients</Link>
                </Button>
            </div>

            <Form
                v-bind="update.form(ingredient)"
                class="max-w-xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Ingredient name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="ingredient.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="ingredientsIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
