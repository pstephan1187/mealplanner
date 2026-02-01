<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    destroy as destroyStore,
    edit,
    index as groceryStoresIndex,
    show,
} from '@/routes/grocery-stores';
import {
    destroy as destroySection,
    store as storeSection,
} from '@/routes/grocery-stores/sections';
import { type BreadcrumbItem } from '@/types';
import type { GroceryStore } from '@/types/models';

const props = defineProps<{
    groceryStore: GroceryStore;
}>();

const deleteStore = () => {
    if (
        confirm(
            'Are you sure you want to delete this store? This will also remove it from any ingredients.',
        )
    ) {
        router.delete(destroyStore(props.groceryStore.id).url);
    }
};

const deleteSection = (sectionId: number) => {
    if (confirm('Are you sure you want to delete this section?')) {
        router.delete(
            destroySection({
                grocery_store: props.groceryStore.id,
                section: sectionId,
            }).url,
        );
    }
};

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Grocery Stores',
        href: groceryStoresIndex().url,
    },
    {
        title: props.groceryStore.name,
        href: show(props.groceryStore.id).url,
    },
]);

const sections = computed(() => props.groceryStore.sections ?? []);
const newSectionName = ref('');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="groceryStore.name" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    :title="groceryStore.name"
                    description="Manage store sections to organize ingredients by aisle."
                />
                <div class="flex gap-2">
                    <Button variant="ghost" as-child>
                        <Link :href="groceryStoresIndex()">Back to stores</Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="edit(groceryStore.id)">Edit</Link>
                    </Button>
                    <Button variant="destructive" @click="deleteStore">
                        Delete
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Add Section</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            v-bind="storeSection.form(groceryStore.id)"
                            class="space-y-4"
                            v-slot="{ errors, processing }"
                            @success="newSectionName = ''"
                        >
                            <div class="grid gap-2">
                                <Label for="section-name">Section name</Label>
                                <Input
                                    id="section-name"
                                    name="name"
                                    v-model="newSectionName"
                                    placeholder="Produce"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </div>

                            <Button type="submit" :disabled="processing">
                                Add section
                            </Button>
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Sections</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="sections.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            No sections yet. Add sections like "Produce",
                            "Dairy", or "Frozen" to organize your shopping.
                        </div>
                        <ul v-else class="space-y-2">
                            <li
                                v-for="section in sections"
                                :key="section.id"
                                class="flex items-center justify-between rounded-md border px-3 py-2"
                            >
                                <span>{{ section.name }}</span>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="deleteSection(section.id)"
                                >
                                    Remove
                                </Button>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
