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
    index as ingredientsIndex,
    show,
} from '@/routes/ingredients';
import { type BreadcrumbItem } from '@/types';
import type { Ingredient } from '@/types/models';

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
        href: show(ingredient).url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="ingredient.name" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    :title="ingredient.name"
                    description="Ingredient details for your recipe library."
                />
                <div class="flex flex-wrap gap-2">
                    <Button variant="secondary" as-child>
                        <Link :href="edit(ingredient)">Edit ingredient</Link>
                    </Button>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive">Delete</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="destroy.form(ingredient)"
                                v-slot="{ processing }"
                                class="space-y-6"
                            >
                                <DialogHeader class="space-y-3">
                                    <DialogTitle
                                        >Delete this ingredient?</DialogTitle
                                    >
                                    <DialogDescription>
                                        Recipes using this ingredient will lose
                                        its link. You can add it again later.
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
                                        Delete ingredient
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <Card class="max-w-xl">
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-1">
                        <dt class="text-sm font-medium">Grocery Store</dt>
                        <dd class="text-sm text-muted-foreground">
                            {{
                                ingredient.grocery_store?.name ??
                                'No store assigned'
                            }}
                        </dd>
                    </div>
                    <div class="grid gap-1">
                        <dt class="text-sm font-medium">Store Section</dt>
                        <dd class="text-sm text-muted-foreground">
                            {{
                                ingredient.grocery_store_section?.name ??
                                'No section assigned'
                            }}
                        </dd>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
