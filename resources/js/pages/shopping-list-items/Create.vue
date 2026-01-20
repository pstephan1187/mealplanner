<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index as shoppingListItemsIndex, store } from '@/routes/shopping-list-items';
import { type BreadcrumbItem } from '@/types';

interface ShoppingList {
    id: number;
}

interface Ingredient {
    id: number;
    name: string;
}

type ResourceCollection<T> = { data: T[] } | T[];

const props = defineProps<{
    shoppingLists: ResourceCollection<ShoppingList>;
    ingredients: ResourceCollection<Ingredient>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shopping list items',
        href: shoppingListItemsIndex().url,
    },
    {
        title: 'Create',
        href: create().url,
    },
];

const shoppingListOptions = computed(() =>
    Array.isArray(props.shoppingLists)
        ? props.shoppingLists
        : props.shoppingLists.data ?? [],
);

const ingredientOptions = computed(() =>
    Array.isArray(props.ingredients)
        ? props.ingredients
        : props.ingredients.data ?? [],
);

const page = usePage();
const preselectedShoppingListId = computed(() => {
    const query = page.url.split('?')[1] ?? '';
    const params = new URLSearchParams(query);
    const value = params.get('shopping_list_id');

    return value ? Number(value) : null;
});

const selectedShoppingListId = ref<number | ''>(
    preselectedShoppingListId.value ?? '',
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create shopping list item" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Create shopping list item"
                    description="Add a custom line item for a specific list."
                />
                <Button variant="ghost" as-child>
                    <Link :href="shoppingListItemsIndex()">
                        Back to items
                    </Link>
                </Button>
            </div>

            <Form
                v-bind="store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Item details</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-6 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="shopping_list_id">Shopping list</Label>
                            <select
                                id="shopping_list_id"
                                name="shopping_list_id"
                                v-model="selectedShoppingListId"
                                class="border-input dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                required
                            >
                                <option value="">Choose a list</option>
                                <option
                                    v-for="list in shoppingListOptions"
                                    :key="list.id"
                                    :value="list.id"
                                >
                                    List #{{ list.id }}
                                </option>
                            </select>
                            <InputError :message="errors.shopping_list_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="ingredient_id">Ingredient</Label>
                            <select
                                id="ingredient_id"
                                name="ingredient_id"
                                class="border-input dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                required
                            >
                                <option value="">Choose ingredient</option>
                                <option
                                    v-for="ingredient in ingredientOptions"
                                    :key="ingredient.id"
                                    :value="ingredient.id"
                                >
                                    {{ ingredient.name }}
                                </option>
                            </select>
                            <InputError :message="errors.ingredient_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="quantity">Quantity</Label>
                            <Input
                                id="quantity"
                                name="quantity"
                                type="number"
                                min="0.01"
                                step="0.01"
                                required
                            />
                            <InputError :message="errors.quantity" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="unit">Unit</Label>
                            <Input
                                id="unit"
                                name="unit"
                                placeholder="cups"
                                required
                            />
                            <InputError :message="errors.unit" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="sort_order">Sort order</Label>
                            <Input
                                id="sort_order"
                                name="sort_order"
                                type="number"
                                min="1"
                                placeholder="1"
                            />
                            <InputError :message="errors.sort_order" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox id="is_purchased" name="is_purchased" />
                            <Label for="is_purchased">Purchased</Label>
                            <InputError :message="errors.is_purchased" />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="shoppingListItemsIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Create item
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
