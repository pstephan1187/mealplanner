<script setup lang="ts" generic="T extends { id: number }">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Paginated } from '@/types/models';

const props = defineProps<{
    title: string;
    description?: string;
    createRoute: string;
    createLabel: string;
    emptyStateMessage: string;
    items: Paginated<T>;
}>();

const itemList = computed(() => props.items.data ?? []);
</script>

<template>
    <div class="flex flex-col gap-8 px-6 py-8">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading :title="title" :description="description" />
            <Button as-child>
                <Link :href="createRoute">{{ createLabel }}</Link>
            </Button>
        </div>

        <div v-if="itemList.length === 0">
            <Card>
                <CardContent
                    class="flex flex-col items-start gap-3 py-10 text-sm text-muted-foreground"
                >
                    <p>{{ emptyStateMessage }}</p>
                    <Button as-child size="sm">
                        <Link :href="createRoute">
                            <slot name="empty-action-label">
                                {{ createLabel }}
                            </slot>
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <slot v-for="item in itemList" :key="item.id" :item="item" />
        </div>

        <Pagination :links="items.links" />
    </div>
</template>
