<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import {
    edit,
    index as shoppingListItemsIndex,
    update,
} from '@/routes/shopping-list-items';
import { type BreadcrumbItem } from '@/types';
import {
    resolveCollection,
    type Ingredient,
    type ResourceCollection,
    type ShoppingList,
    type ShoppingListItem,
} from '@/types/models';

const props = defineProps<{
    item: ResourceProp<ShoppingListItem>;
    shoppingLists: ResourceCollection<ShoppingList>;
    ingredients: ResourceCollection<Ingredient>;
}>();

const item = resolveResource(props.item);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shopping list items',
        href: shoppingListItemsIndex().url,
    },
    {
        title: 'Edit',
        href: edit(item.id).url,
    },
];

const shoppingListOptions = computed(() =>
    resolveCollection(props.shoppingLists),
);

const ingredientOptions = computed(() => resolveCollection(props.ingredients));

const selectedShoppingListId = ref<number | ''>(item.shopping_list_id ?? '');
const selectedIngredientId = ref<number | ''>(item.ingredient_id ?? '');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit shopping list item" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Edit shopping list item"
                    description="Adjust quantity, unit, and sorting."
                />
                <Button variant="ghost" as-child>
                    <Link :href="shoppingListItemsIndex()">
                        Back to items
                    </Link>
                </Button>
            </div>

            <Form
                v-bind="update.form(item.id)"
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
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
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
                                v-model="selectedIngredientId"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
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
                                :default-value="item.quantity"
                            />
                            <InputError :message="errors.quantity" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="unit">Unit</Label>
                            <Input
                                id="unit"
                                name="unit"
                                :default-value="item.unit"
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
                                :default-value="item.sort_order ?? ''"
                            />
                            <InputError :message="errors.sort_order" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="is_purchased"
                                name="is_purchased"
                                :default-value="item.is_purchased"
                            />
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
                        Save changes
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
