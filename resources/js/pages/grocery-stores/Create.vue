<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import ResourceForm from '@/components/ResourceForm.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create,
    index as groceryStoresIndex,
    store,
} from '@/routes/grocery-stores';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grocery Stores',
        href: groceryStoresIndex().url,
    },
    {
        title: 'Create',
        href: create().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Add grocery store" />

        <ResourceForm
            title="Add grocery store"
            description="Add a new store to organize your ingredients."
            :back-route="groceryStoresIndex().url"
            back-label="Back to stores"
            submit-label="Create store"
            :form-action="store.form()"
            narrow
            #default="{ errors }"
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
        </ResourceForm>
    </AppLayout>
</template>
