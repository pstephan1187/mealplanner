<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import {
    destroy,
    edit,
    index as shoppingListItemsIndex,
    show,
} from '@/routes/shopping-list-items';
import { type BreadcrumbItem } from '@/types';
import type { ShoppingListItem } from '@/types/models';

const props = defineProps<{
    item: ResourceProp<ShoppingListItem>;
}>();

const item = resolveResource(props.item);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shopping list items',
        href: shoppingListItemsIndex().url,
    },
    {
        title: item.ingredient?.name ?? 'Item',
        href: show(item.id).url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Shopping list item" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    :title="item.ingredient?.name ?? 'Shopping list item'"
                    description="Review quantities, unit, and sorting."
                />
                <div class="flex flex-wrap gap-2">
                    <Button variant="secondary" as-child>
                        <Link :href="edit(item.id)">Edit item</Link>
                    </Button>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive">Delete</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="destroy.form(item.id)"
                                v-slot="{ processing }"
                                class="space-y-6"
                            >
                                <DialogHeader class="space-y-3">
                                    <DialogTitle>Delete this item?</DialogTitle>
                                    <DialogDescription>
                                        This will remove the ingredient from the
                                        shopping list.
                                    </DialogDescription>
                                </DialogHeader>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="processing"
                                    >
                                        Delete item
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <Card class="max-w-xl">
                <CardHeader>
                    <CardTitle>Item details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm text-muted-foreground">
                    <p>Quantity: {{ item.quantity }} {{ item.unit }}</p>
                    <p>Sort order: {{ item.sort_order ?? 'Not set' }}</p>
                    <p>
                        Purchased:
                        {{ item.is_purchased ? 'Yes' : 'No' }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
