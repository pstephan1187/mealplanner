<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, GripVertical } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import {
    create as createShoppingListItem,
    edit as editShoppingListItem,
    update as updateShoppingListItem,
} from '@/routes/shopping-list-items';
import {
    edit,
    index as shoppingListsIndex,
    show,
    update,
} from '@/routes/shopping-lists';
import { order as orderShoppingListItems } from '@/routes/shopping-lists/items';
import { type BreadcrumbItem } from '@/types';

interface Ingredient {
    id: number;
    name: string;
}

interface ShoppingListItem {
    id: number;
    quantity: string | number;
    unit: string;
    is_purchased: boolean;
    sort_order?: number | null;
    ingredient?: Ingredient | null;
}

interface MealPlan {
    id: number;
    name: string;
    start_date?: string | null;
    end_date?: string | null;
}

interface ShoppingList {
    id: number;
    meal_plan_id: number;
    display_mode?: 'manual' | 'alphabetical';
    meal_plan?: MealPlan | null;
    items?: ShoppingListItem[];
}

const props = defineProps<{
    shoppingList: ResourceProp<ShoppingList>;
}>();

const shoppingList = computed(() => resolveResource(props.shoppingList));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shopping Lists',
        href: shoppingListsIndex().url,
    },
    {
        title: shoppingList.value.meal_plan?.name ?? 'Shopping list',
        href: show(shoppingList.value.id).url,
    },
];

const displayMode = ref<'manual' | 'alphabetical'>(
    shoppingList.value.display_mode ?? 'manual',
);
const manualItems = ref<ShoppingListItem[]>([]);
const draggingItemId = ref<number | null>(null);

const normalizeOrder = (items: ShoppingListItem[]): ShoppingListItem[] =>
    items.map((item, index) => ({
        ...item,
        sort_order: index + 1,
    }));

const syncManualItems = () => {
    const items = [...(shoppingList.value.items ?? [])];

    items.sort((a, b) => {
        const orderA = a.sort_order ?? Number.MAX_SAFE_INTEGER;
        const orderB = b.sort_order ?? Number.MAX_SAFE_INTEGER;

        if (orderA === orderB) {
            return (a.ingredient?.name ?? '').localeCompare(
                b.ingredient?.name ?? '',
            );
        }

        return orderA - orderB;
    });

    manualItems.value = normalizeOrder(items);
};

watch(
    () => shoppingList.value.items,
    () => {
        syncManualItems();
    },
    { immediate: true },
);

watch(
    () => shoppingList.value.display_mode,
    (value) => {
        if (value) {
            displayMode.value = value;
        }
    },
);

watch(displayMode, (value) => {
    if (value === shoppingList.value.display_mode) {
        return;
    }

    router.patch(
        update(shoppingList.value.id),
        { display_mode: value },
        { preserveScroll: true },
    );
});

const sortedItems = computed(() => {
    const items = [...(shoppingList.value.items ?? [])];

    if (displayMode.value === 'alphabetical') {
        return items.sort((a, b) => {
            const nameA = a.ingredient?.name ?? '';
            const nameB = b.ingredient?.name ?? '';
            return nameA.localeCompare(nameB);
        });
    }

    return manualItems.value;
});

const formatDate = (value?: string | null): string => {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
};

const persistOrder = (items: ShoppingListItem[]) => {
    const payload = items.map((item, index) => ({
        id: item.id,
        sort_order: index + 1,
    }));

    router.patch(orderShoppingListItems(shoppingList.value.id), { items: payload }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const reorderItems = (draggedId: number, targetId: number) => {
    if (draggedId === targetId) {
        return;
    }

    const items = [...manualItems.value];
    const fromIndex = items.findIndex((item) => item.id === draggedId);
    const toIndex = items.findIndex((item) => item.id === targetId);

    if (fromIndex < 0 || toIndex < 0) {
        return;
    }

    const [moved] = items.splice(fromIndex, 1);
    items.splice(toIndex, 0, moved);
    manualItems.value = normalizeOrder(items);

    persistOrder(manualItems.value);
};

const moveItem = (itemId: number, direction: -1 | 1) => {
    const items = [...manualItems.value];
    const index = items.findIndex((item) => item.id === itemId);

    if (index < 0) {
        return;
    }

    const targetIndex = index + direction;

    if (targetIndex < 0 || targetIndex >= items.length) {
        return;
    }

    const [moved] = items.splice(index, 1);
    items.splice(targetIndex, 0, moved);
    manualItems.value = normalizeOrder(items);

    persistOrder(manualItems.value);
};

const handleDragStart = (event: DragEvent, item: ShoppingListItem) => {
    if (displayMode.value !== 'manual') {
        return;
    }

    draggingItemId.value = item.id;
    event.dataTransfer?.setData('text/plain', item.id.toString());
    event.dataTransfer?.setDragImage(event.currentTarget as Element, 20, 20);
};

const handleDragOver = (event: DragEvent) => {
    if (displayMode.value !== 'manual') {
        return;
    }

    event.preventDefault();
};

const handleDrop = (event: DragEvent, item: ShoppingListItem) => {
    if (displayMode.value !== 'manual') {
        return;
    }

    event.preventDefault();

    if (draggingItemId.value === null) {
        return;
    }

    reorderItems(draggingItemId.value, item.id);
    draggingItemId.value = null;
};

const handleDragEnd = () => {
    draggingItemId.value = null;
};

const togglePurchased = (item: ShoppingListItem) => {
    router.patch(
        updateShoppingListItem(item.id),
        {
            is_purchased: !item.is_purchased,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Shopping list" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    :title="
                        shoppingList.meal_plan?.name ?? 'Shopping list'
                    "
                    description="Customize order, mark purchases, and keep it tidy."
                />
                <div class="flex flex-wrap gap-2">
                    <Button variant="secondary" as-child>
                        <Link :href="edit(shoppingList.id)">Edit list</Link>
                    </Button>
                    <Button as-child>
                        <Link
                            :href="
                                createShoppingListItem({
                                    query: { shopping_list_id: shoppingList.id },
                                })
                            "
                        >
                            Add item
                        </Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Display</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Choose how this list is ordered.
                        </p>
                    </div>
                    <select
                        v-model="displayMode"
                        class="border-input dark:bg-input/30 h-9 rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    >
                        <option value="manual">Manual order</option>
                        <option value="alphabetical">Alphabetical</option>
                    </select>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Items</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-if="sortedItems.length === 0"
                        class="rounded-lg border border-dashed border-border p-6 text-sm text-muted-foreground"
                    >
                        No items yet. Add ingredients from your pantry or a
                        meal plan.
                    </div>

                    <div
                        v-for="item in sortedItems"
                        :key="item.id"
                        class="flex flex-col gap-2 rounded-lg border border-border/70 p-4 md:flex-row md:items-center md:justify-between"
                        :data-test="`shopping-item-${item.id}`"
                        :class="{
                            'cursor-grab active:cursor-grabbing':
                                displayMode === 'manual',
                            'opacity-70':
                                draggingItemId !== null &&
                                draggingItemId !== item.id,
                        }"
                        :draggable="displayMode === 'manual'"
                        @dragstart="handleDragStart($event, item)"
                        @dragover="handleDragOver"
                        @drop="handleDrop($event, item)"
                        @dragend="handleDragEnd"
                    >
                        <div class="flex items-start gap-3">
                            <button
                                v-if="displayMode === 'manual'"
                                type="button"
                                class="mt-0.5 text-muted-foreground"
                                aria-label="Drag to reorder"
                            >
                                <GripVertical class="size-4" />
                            </button>
                            <Checkbox
                                :id="`item-${item.id}`"
                                :default-value="item.is_purchased"
                                @click="togglePurchased(item)"
                                :data-test="`shopping-item-toggle-${item.id}`"
                            />
                            <div>
                                <p class="font-medium">
                                    {{ item.ingredient?.name ?? 'Ingredient' }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ item.quantity }} {{ item.unit }}
                                    <span
                                        v-if="
                                            displayMode === 'manual' &&
                                            item.sort_order
                                        "
                                    >
                                        - Order {{ item.sort_order }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div
                                v-if="displayMode === 'manual'"
                                class="flex items-center gap-1"
                            >
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Move item up"
                                    :data-test="`shopping-item-up-${item.id}`"
                                    @click="moveItem(item.id, -1)"
                                >
                                    <ArrowUp class="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Move item down"
                                    :data-test="`shopping-item-down-${item.id}`"
                                    @click="moveItem(item.id, 1)"
                                >
                                    <ArrowDown class="size-4" />
                                </Button>
                            </div>
                            <Button variant="ghost" size="sm" as-child>
                                <Link :href="editShoppingListItem(item.id)">
                                    Edit
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="shoppingList.meal_plan">
                <CardHeader>
                    <CardTitle>Linked meal plan</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        {{ shoppingList.meal_plan.name }}
                        <span v-if="shoppingList.meal_plan.start_date">
                            -
                            {{
                                formatDate(
                                    shoppingList.meal_plan.start_date,
                                )
                            }}
                            <span
                                v-if="shoppingList.meal_plan.end_date"
                            >
                                -
                            </span>
                            {{
                                formatDate(
                                    shoppingList.meal_plan.end_date,
                                )
                            }}
                        </span>
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
