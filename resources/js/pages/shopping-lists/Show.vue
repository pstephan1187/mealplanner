<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, GripVertical } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import {
    create as createShoppingListItem,
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

interface GroceryStoreSection {
    id: number;
    grocery_store_id: number;
    name: string;
    sort_order: number;
}

interface GroceryStore {
    id: number;
    name: string;
}

interface Ingredient {
    id: number;
    name: string;
    grocery_store_id?: number | null;
    grocery_store_section_id?: number | null;
    grocery_store?: GroceryStore | null;
    grocery_store_section?: GroceryStoreSection | null;
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
    display_mode?: 'manual' | 'alphabetical' | 'store';
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

const displayMode = ref<'manual' | 'alphabetical' | 'store'>(
    shoppingList.value.display_mode ?? 'manual',
);
const manualItems = ref<ShoppingListItem[]>([]);
const draggingItemId = ref<number | null>(null);

// Edit modal state
const showEditModal = ref(false);
const editingItem = ref<ShoppingListItem | null>(null);
const editQuantity = ref('');
const editUnit = ref('');
const editIsPurchased = ref(false);
const editErrors = ref<Record<string, string>>({});
const editProcessing = ref(false);

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
    { immediate: true, deep: true },
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

interface SectionGroup {
    sectionName: string | null;
    sortOrder: number;
    items: ShoppingListItem[];
}

interface StoreGroup {
    storeName: string;
    isUnassigned: boolean;
    sections: SectionGroup[];
}

const sortedItems = computed(() => {
    const items = [...(shoppingList.value.items ?? [])];

    if (displayMode.value === 'alphabetical') {
        return items.sort((a, b) => {
            // Purchased items go to the bottom
            if (a.is_purchased !== b.is_purchased) {
                return a.is_purchased ? 1 : -1;
            }
            const nameA = a.ingredient?.name ?? '';
            const nameB = b.ingredient?.name ?? '';
            return nameA.localeCompare(nameB);
        });
    }

    if (displayMode.value === 'store') {
        return items.sort((a, b) => {
            // Purchased items go to the bottom
            if (a.is_purchased !== b.is_purchased) {
                return a.is_purchased ? 1 : -1;
            }

            const storeA = a.ingredient?.grocery_store;
            const storeB = b.ingredient?.grocery_store;

            // Items without a store come first
            if (!storeA && storeB) return -1;
            if (storeA && !storeB) return 1;

            // Both have stores - compare store names
            if (storeA && storeB) {
                const storeCompare = storeA.name.localeCompare(storeB.name);
                if (storeCompare !== 0) return storeCompare;
            }

            const sectionA = a.ingredient?.grocery_store_section;
            const sectionB = b.ingredient?.grocery_store_section;

            // Items without a section come first within the same store
            if (!sectionA && sectionB) return -1;
            if (sectionA && !sectionB) return 1;

            // Both have sections - compare by sort order
            if (sectionA && sectionB) {
                const sectionCompare = sectionA.sort_order - sectionB.sort_order;
                if (sectionCompare !== 0) return sectionCompare;
            }

            // Finally, sort alphabetically by ingredient name
            const nameA = a.ingredient?.name ?? '';
            const nameB = b.ingredient?.name ?? '';
            return nameA.localeCompare(nameB);
        });
    }

    // For manual mode, also move purchased items to the bottom
    const unpurchased = manualItems.value.filter((item) => !item.is_purchased);
    const purchased = manualItems.value.filter((item) => item.is_purchased);
    return [...unpurchased, ...purchased];
});

const groupedByStore = computed((): StoreGroup[] => {
    if (displayMode.value !== 'store') return [];

    const stores: Map<string, StoreGroup> = new Map();

    for (const item of sortedItems.value) {
        const store = item.ingredient?.grocery_store;
        const section = item.ingredient?.grocery_store_section;

        const storeName = store?.name ?? 'Not assigned';
        const isUnassigned = !store;
        const sectionName = section?.name ?? null;
        const sortOrder = section?.sort_order ?? 0;

        // Get or create store group
        if (!stores.has(storeName)) {
            stores.set(storeName, {
                storeName,
                isUnassigned,
                sections: [],
            });
        }

        const storeGroup = stores.get(storeName)!;

        // Find or create section within store
        let sectionGroup = storeGroup.sections.find(
            (s) => s.sectionName === sectionName
        );

        if (!sectionGroup) {
            sectionGroup = {
                sectionName,
                sortOrder,
                items: [],
            };
            storeGroup.sections.push(sectionGroup);
        }

        sectionGroup.items.push(item);
    }

    // Sort stores: "Not assigned" first, then alphabetically
    const sortedStores = Array.from(stores.values()).sort((a, b) => {
        if (a.isUnassigned && !b.isUnassigned) return -1;
        if (!a.isUnassigned && b.isUnassigned) return 1;
        return a.storeName.localeCompare(b.storeName);
    });

    // Sort sections within each store by sort order
    for (const store of sortedStores) {
        store.sections.sort((a, b) => {
            // Null section (no section assigned) comes first
            if (a.sectionName === null && b.sectionName !== null) return -1;
            if (a.sectionName !== null && b.sectionName === null) return 1;
            return a.sortOrder - b.sortOrder;
        });
    }

    return sortedStores;
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

const openEditModal = async (item: ShoppingListItem) => {
    editingItem.value = item;
    editQuantity.value = String(item.quantity);
    editUnit.value = item.unit;
    editIsPurchased.value = Boolean(item.is_purchased);
    editErrors.value = {};
    await nextTick();
    showEditModal.value = true;
};

const saveItemEdit = () => {
    if (!editingItem.value) return;

    editProcessing.value = true;
    editErrors.value = {};

    router.patch(
        updateShoppingListItem(editingItem.value.id),
        {
            quantity: editQuantity.value,
            unit: editUnit.value,
            is_purchased: Boolean(editIsPurchased.value),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showEditModal.value = false;
                editingItem.value = null;
                router.reload();
            },
            onError: (errors) => {
                editErrors.value = errors as Record<string, string>;
            },
            onFinish: () => {
                editProcessing.value = false;
            },
        },
    );
};

// Watch for prop changes to keep shoppingList reactive
watch(
    () => props.shoppingList,
    () => {
        syncManualItems();
    },
    { deep: true },
);
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
                        <option value="store">By store</option>
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

                    <!-- Store grouped view -->
                    <template v-else-if="displayMode === 'store'">
                        <div
                            v-for="storeGroup in groupedByStore"
                            :key="storeGroup.storeName"
                            class="space-y-4"
                        >
                            <!-- Store header -->
                            <h3 class="border-b-2 border-primary pb-2 text-lg font-semibold text-foreground">
                                {{ storeGroup.storeName }}
                            </h3>

                            <!-- Sections within store -->
                            <div
                                v-for="section in storeGroup.sections"
                                :key="`${storeGroup.storeName}-${section.sectionName}`"
                                class="space-y-2"
                            >
                                <!-- Section header -->
                                <h4
                                    v-if="section.sectionName"
                                    class="text-sm font-medium text-muted-foreground"
                                >
                                    {{ section.sectionName }}
                                </h4>

                                <!-- Items in section -->
                                <div
                                    v-for="item in section.items"
                                    :key="item.id"
                                    class="flex flex-col gap-2 rounded-lg border border-border/70 p-4 md:flex-row md:items-center md:justify-between"
                                    :data-test="`shopping-item-${item.id}`"
                                    :class="{
                                        'opacity-50 bg-muted/30': item.is_purchased,
                                    }"
                                >
                                    <div class="flex items-start gap-3">
                                        <input
                                            type="checkbox"
                                            :id="`item-${item.id}`"
                                            :checked="item.is_purchased"
                                            class="size-4 rounded border-gray-300 text-primary focus:ring-primary"
                                            :data-test="`shopping-item-toggle-${item.id}`"
                                            @change="togglePurchased(item)"
                                        />
                                        <div>
                                            <p
                                                class="font-medium"
                                                :class="{ 'line-through': item.is_purchased }"
                                            >
                                                {{ item.ingredient?.name ?? 'Ingredient' }}
                                            </p>
                                            <p class="text-sm text-muted-foreground">
                                                {{ item.quantity }} {{ item.unit }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openEditModal(item)"
                                        >
                                            Edit
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Manual and alphabetical view -->
                    <template v-else>
                        <div
                            v-for="item in sortedItems"
                            :key="item.id"
                            class="flex flex-col gap-2 rounded-lg border border-border/70 p-4 md:flex-row md:items-center md:justify-between"
                            :data-test="`shopping-item-${item.id}`"
                            :class="{
                                'cursor-grab active:cursor-grabbing':
                                    displayMode === 'manual' && !item.is_purchased,
                                'opacity-50':
                                    draggingItemId !== null &&
                                    draggingItemId !== item.id,
                                'opacity-50 bg-muted/30': item.is_purchased,
                            }"
                            :draggable="displayMode === 'manual' && !item.is_purchased"
                            @dragstart="handleDragStart($event, item)"
                            @dragover="handleDragOver"
                            @drop="handleDrop($event, item)"
                            @dragend="handleDragEnd"
                        >
                            <div class="flex items-start gap-3">
                                <button
                                    v-if="displayMode === 'manual' && !item.is_purchased"
                                    type="button"
                                    class="mt-0.5 text-muted-foreground"
                                    aria-label="Drag to reorder"
                                >
                                    <GripVertical class="size-4" />
                                </button>
                                <input
                                    type="checkbox"
                                    :id="`item-${item.id}`"
                                    :checked="item.is_purchased"
                                    class="size-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    :data-test="`shopping-item-toggle-${item.id}`"
                                    @change="togglePurchased(item)"
                                />
                                <div>
                                    <p
                                        class="font-medium"
                                        :class="{ 'line-through': item.is_purchased }"
                                    >
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
                                    v-if="displayMode === 'manual' && !item.is_purchased"
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
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openEditModal(item)"
                                >
                                    Edit
                                </Button>
                            </div>
                        </div>
                    </template>
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

        <!-- Edit Item Modal -->
        <Dialog v-model:open="showEditModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit item</DialogTitle>
                    <DialogDescription>
                        {{ editingItem?.ingredient?.name ?? 'Item' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="edit-quantity">Quantity</Label>
                            <Input
                                id="edit-quantity"
                                v-model="editQuantity"
                                type="number"
                                min="0.01"
                                step="0.01"
                            />
                            <InputError :message="editErrors.quantity" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-unit">Unit</Label>
                            <Input
                                id="edit-unit"
                                v-model="editUnit"
                                placeholder="cups, oz, etc."
                            />
                            <InputError :message="editErrors.unit" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="edit-is-purchased"
                            :checked="editIsPurchased"
                            class="size-4 rounded border-gray-300"
                            @change="editIsPurchased = ($event.target as HTMLInputElement).checked"
                        />
                        <Label for="edit-is-purchased">Purchased</Label>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        @click="saveItemEdit"
                        :disabled="editProcessing"
                    >
                        Save changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
