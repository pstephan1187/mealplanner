<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface GroceryStore {
    id: number;
    name: string;
}

const props = defineProps<{
    groceryStore: GroceryStore;
}>();

const groceryStoresIndex = () => ({ url: '/grocery-stores' });
const show = (store: { id: number }) => ({ url: `/grocery-stores/${store.id}` });
const update = {
    form: () => ({
        action: `/grocery-stores/${props.groceryStore.id}`,
        method: 'patch',
    }),
};

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Grocery Stores',
        href: groceryStoresIndex().url,
    },
    {
        title: props.groceryStore.name,
        href: show(props.groceryStore).url,
    },
    {
        title: 'Edit',
        href: `/grocery-stores/${props.groceryStore.id}/edit`,
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${groceryStore.name}`" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Edit store"
                    description="Update the store details."
                />
                <Button variant="ghost" as-child>
                    <Link :href="show(groceryStore)">Back to store</Link>
                </Button>
            </div>

            <Form
                v-bind="update.form()"
                class="max-w-xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Store name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="groceryStore.name"
                        placeholder="Whole Foods Market"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="show(groceryStore)">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Update store
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
