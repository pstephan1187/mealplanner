<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import InputError from '@/components/InputError.vue';
import ResourceForm from '@/components/ResourceForm.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    edit,
    index as groceryStoresIndex,
    show,
    update,
} from '@/routes/grocery-stores';
import { type BreadcrumbItem } from '@/types';
import type { GroceryStore } from '@/types/models';

const props = defineProps<{
    groceryStore: GroceryStore;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Grocery Stores',
        href: groceryStoresIndex().url,
    },
    {
        title: props.groceryStore.name,
        href: show(props.groceryStore.id).url,
    },
    {
        title: 'Edit',
        href: edit(props.groceryStore.id).url,
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${groceryStore.name}`" />

        <ResourceForm
            title="Edit store"
            description="Update the store details."
            :back-route="show(groceryStore.id).url"
            back-label="Back to store"
            submit-label="Update store"
            :form-action="update.form.patch(groceryStore.id)"
            narrow
            #default="{ errors }"
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
        </ResourceForm>
    </AppLayout>
</template>
