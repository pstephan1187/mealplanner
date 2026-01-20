<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import Pagination, { type PaginationLink } from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

// Routes disabled - using hardcoded paths temporarily
const ingredientsIndex = () => ({ url: '/ingredients' });
const create = () => ({ url: '/ingredients/create' });
const show = (ingredient: { id: number }) => ({ url: `/ingredients/${ingredient.id}` });

interface Ingredient {
    id: number;
    name: string;
}

interface Paginated<T> {
    data: T[];
    links?: PaginationLink[];
    meta?: {
        total?: number;
        from?: number | null;
        to?: number | null;
    };
}

const props = defineProps<{
    ingredients: Paginated<Ingredient>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ingredients',
        href: ingredientsIndex().url,
    },
];

const ingredientItems = computed(() => props.ingredients.data ?? []);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Ingredients" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Ingredients"
                    description="Keep your ingredient list tidy and reusable."
                />
                <Button as-child>
                    <Link :href="create()">Add ingredient</Link>
                </Button>
            </div>

            <div v-if="ingredientItems.length === 0">
                <Card>
                    <CardContent
                        class="flex flex-col items-start gap-3 py-10 text-sm text-muted-foreground"
                    >
                        <p>
                            No ingredients yet. Start with pantry staples you
                            use often.
                        </p>
                        <Button as-child size="sm">
                            <Link :href="create()">Add your first ingredient</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="ingredient in ingredientItems"
                    :key="ingredient.id"
                >
                    <CardContent class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ ingredient.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                Ingredient
                            </p>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="show(ingredient)">View</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Pagination :links="ingredients.links" />
        </div>
    </AppLayout>
</template>
