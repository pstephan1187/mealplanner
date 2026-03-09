<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index as recipesIndex, show } from '@/routes/recipes';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, Recipe } from '@/types/models';

const props = defineProps<{
    recipes: Paginated<Recipe>;
    filters?: {
        search?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recipes',
        href: recipesIndex().url,
    },
];

const search = ref(props.filters?.search ?? '');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        router.get(
            recipesIndex().url,
            { search: value || undefined },
            {
                preserveState: true,
                replace: true,
            },
        );
    }, 300);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Recipes" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Recipes"
                    description="Capture your favorites, prep times, and ingredients."
                />
                <Button as-child>
                    <Link :href="create().url">Create recipe</Link>
                </Button>
            </div>

            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search recipes..."
                    class="pl-9"
                />
            </div>

            <div v-if="recipes.data.length === 0">
                <Card>
                    <CardContent
                        class="flex flex-col items-start gap-3 py-10 text-sm text-muted-foreground"
                    >
                        <p v-if="search">
                            No recipes match "{{ search }}". Try a different
                            search.
                        </p>
                        <p v-else>
                            No recipes yet. Start with a go-to dinner you love.
                        </p>
                        <Button v-if="!search" as-child size="sm">
                            <Link :href="create().url">Create recipe</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="recipe in recipes.data"
                    :key="recipe.id"
                    class="overflow-hidden"
                >
                    <div class="relative aspect-[4/3] bg-muted">
                        <img
                            v-if="recipe.photo_url"
                            :src="recipe.photo_url"
                            :alt="recipe.name"
                            class="size-full object-cover"
                        />
                        <div
                            v-else
                            class="flex size-full items-center justify-center text-sm text-muted-foreground"
                        >
                            No photo
                        </div>
                    </div>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <h3 class="text-lg font-semibold">
                                    {{ recipe.name }}
                                </h3>
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="show(recipe)">View</Link>
                                </Button>
                            </div>
                        </div>

                        <div class="grid gap-2 text-sm text-muted-foreground">
                            <div class="flex items-center justify-between">
                                <span>Prep + cook</span>
                                <span>
                                    {{
                                        (recipe.prep_time_minutes ?? 0) +
                                        (recipe.cook_time_minutes ?? 0)
                                    }}
                                    min
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Servings</span>
                                <span>{{ recipe.servings ?? '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Ingredients</span>
                                <span>
                                    {{ recipe.ingredients?.length ?? 0 }}
                                </span>
                            </div>
                            <div
                                v-if="recipe.sections_count"
                                class="flex items-center justify-between"
                            >
                                <span>Sections</span>
                                <span>{{ recipe.sections_count }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Pagination :links="recipes.links" />
        </div>
    </AppLayout>
</template>
