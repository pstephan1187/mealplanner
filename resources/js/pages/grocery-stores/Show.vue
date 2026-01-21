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
import { type BreadcrumbItem } from '@/types';

interface GroceryStoreSection {
    id: number;
    name: string;
    sort_order: number;
}

interface GroceryStore {
    id: number;
    name: string;
    sections?: GroceryStoreSection[];
}

const props = defineProps<{
    groceryStore: GroceryStore;
}>();

const groceryStoresIndex = () => ({ url: '/grocery-stores' });
const edit = (store: { id: number }) => ({ url: `/grocery-stores/${store.id}/edit` });
const storeSection = { form: () => ({ action: `/grocery-stores/${props.groceryStore.id}/sections`, method: 'post' }) };
const deleteStore = () => {
    if (confirm('Are you sure you want to delete this store? This will also remove it from any ingredients.')) {
        router.delete(`/grocery-stores/${props.groceryStore.id}`);
    }
};

const deleteSection = (sectionId: number) => {
    if (confirm('Are you sure you want to delete this section?')) {
        router.delete(`/grocery-stores/${props.groceryStore.id}/sections/${sectionId}`);
    }
};

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Grocery Stores',
        href: groceryStoresIndex().url,
    },
    {
        title: props.groceryStore.name,
        href: `/grocery-stores/${props.groceryStore.id}`,
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
                        <Link :href="edit(groceryStore)">Edit</Link>
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
                            v-bind="storeSection.form()"
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
