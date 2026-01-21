<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const groceryStoresIndex = () => ({ url: '/grocery-stores' });
const store = { form: () => ({ action: '/grocery-stores', method: 'post' }) };

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grocery Stores',
        href: groceryStoresIndex().url,
    },
    {
        title: 'Create',
        href: '/grocery-stores/create',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Add grocery store" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Add grocery store"
                    description="Add a new store to organize your ingredients."
                />
                <Button variant="ghost" as-child>
                    <Link :href="groceryStoresIndex()">Back to stores</Link>
                </Button>
            </div>

            <Form
                v-bind="store.form()"
                class="max-w-xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Store name</Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="Whole Foods Market"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="groceryStoresIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Create store
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
