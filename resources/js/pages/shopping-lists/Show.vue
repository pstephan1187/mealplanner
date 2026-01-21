<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, GripVertical, Pencil } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
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
import { update as updateIngredient } from '@/routes/ingredients';
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
    sections?: GroceryStoreSection[];
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
    grocery_store_id?: number | null;
    grocery_store_section_id?: number | null;
    ingredient?: Ingredient | null;
    effective_grocery_store?: GroceryStore | null;
    effective_grocery_store_section?: GroceryStoreSection | null;
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
    groceryStores: ResourceProp<GroceryStore[]>;
}>();

const shoppingList = computed(() => resolveResource(props.shoppingList));
const groceryStores = computed(() => resolveResource(props.groceryStores));

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

// Ingredient edit modal state
const showIngredientModal = ref(false);
const editingIngredientItem = ref<ShoppingListItem | null>(null);
const ingredientName = ref('');
const ingredientErrors = ref<Record<string, string>>({});
const ingredientProcessing = ref(false);
const localStores = ref<GroceryStore[]>([]);
const selectedStoreId = ref<number | string>('');
const selectedSectionId = ref<number | string>('');

// Store creation modal
const showStoreModal = ref(false);
const newStoreName = ref('');
const newStoreSections = ref<string[]>(['']);
const storeModalLoading = ref(false);

// Section creation modal
const showSectionModal = ref(false);
const newSectionName = ref('');
const sectionModalLoading = ref(false);

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
    sectionId: number | null;
    sortOrder: number;
    items: ShoppingListItem[];
}

interface StoreGroup {
    storeName: string;
    storeId: number | null;
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

            const storeA = a.effective_grocery_store;
            const storeB = b.effective_grocery_store;

            // Items without a store come first
            if (!storeA && storeB) return -1;
            if (storeA && !storeB) return 1;

            // Both have stores - compare store names
            if (storeA && storeB) {
                const storeCompare = storeA.name.localeCompare(storeB.name);
                if (storeCompare !== 0) return storeCompare;
            }

            const sectionA = a.effective_grocery_store_section;
            const sectionB = b.effective_grocery_store_section;

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
        const store = item.effective_grocery_store;
        const section = item.effective_grocery_store_section;

        const storeName = store?.name ?? 'Not assigned';
        const storeId = store?.id ?? null;
        const isUnassigned = !store;
        const sectionName = section?.name ?? null;
        const sectionId = section?.id ?? null;
        const sortOrder = section?.sort_order ?? 0;

        // Get or create store group
        if (!stores.has(storeName)) {
            stores.set(storeName, {
                storeName,
                storeId,
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
                sectionId,
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
    storeDraggingItemId.value = null;
};

// Store view drag and drop
const storeDraggingItemId = ref<number | null>(null);

const handleStoreDragStart = (event: DragEvent, item: ShoppingListItem) => {
    if (displayMode.value !== 'store' || item.is_purchased) {
        return;
    }

    storeDraggingItemId.value = item.id;
    event.dataTransfer?.setData('text/plain', item.id.toString());
    event.dataTransfer?.setDragImage(event.currentTarget as Element, 20, 20);
};

const handleStoreDragOver = (event: DragEvent) => {
    if (displayMode.value !== 'store') {
        return;
    }

    event.preventDefault();
};

const handleStoreDropOnSection = (
    event: DragEvent,
    storeId: number | null,
    sectionId: number | null
) => {
    if (displayMode.value !== 'store') {
        return;
    }

    event.preventDefault();

    if (storeDraggingItemId.value === null) {
        return;
    }

    const itemId = storeDraggingItemId.value;
    storeDraggingItemId.value = null;

    // Update the item's store and section
    router.patch(
        updateShoppingListItem(itemId),
        {
            grocery_store_id: storeId,
            grocery_store_section_id: sectionId,
        },
        {
            preserveScroll: true,
        },
    );
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

// Ingredient edit modal computed and functions
const storeOptions = computed(() =>
    localStores.value.map((s) => ({ id: s.id, name: s.name })),
);

const availableSections = computed(() => {
    if (!selectedStoreId.value) return [];
    const store = localStores.value.find(
        (s) => s.id === Number(selectedStoreId.value),
    );
    return store?.sections ?? [];
});

const sectionOptions = computed(() =>
    availableSections.value.map((s) => ({ id: s.id, name: s.name })),
);

watch(selectedStoreId, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        selectedSectionId.value = '';
    }
});

const openIngredientModal = async (item: ShoppingListItem) => {
    editingIngredientItem.value = item;
    ingredientName.value = item.ingredient?.name ?? '';
    localStores.value = [...groceryStores.value];
    selectedStoreId.value = item.ingredient?.grocery_store_id ?? '';
    selectedSectionId.value = item.ingredient?.grocery_store_section_id ?? '';
    ingredientErrors.value = {};
    showEditModal.value = false;
    await nextTick();
    showIngredientModal.value = true;
};

const saveIngredient = async () => {
    if (!editingIngredientItem.value?.ingredient) return;

    ingredientProcessing.value = true;
    ingredientErrors.value = {};

    const ingredientId = editingIngredientItem.value.ingredient.id;
    const itemId = editingIngredientItem.value.id;

    try {
        // Update the ingredient via fetch to avoid redirect
        const response = await fetch(updateIngredient.url(ingredientId), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find((row) => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] ?? '',
                ),
            },
            credentials: 'same-origin',
            redirect: 'manual',
            body: JSON.stringify({
                name: ingredientName.value,
                grocery_store_id: selectedStoreId.value || null,
                grocery_store_section_id: selectedSectionId.value || null,
            }),
        });

        // redirect: 'manual' returns type 'opaqueredirect' with status 0 on redirect
        if (response.ok || response.type === 'opaqueredirect') {
            // Clear item's store/section override so it uses ingredient's values
            router.patch(
                updateShoppingListItem(itemId),
                {
                    grocery_store_id: null,
                    grocery_store_section_id: null,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        showIngredientModal.value = false;
                        editingIngredientItem.value = null;
                        router.reload();
                    },
                    onFinish: () => {
                        ingredientProcessing.value = false;
                    },
                },
            );
        } else if (response.status === 422) {
            const data = await response.json();
            ingredientErrors.value = data.errors || {};
            ingredientProcessing.value = false;
        } else {
            ingredientProcessing.value = false;
        }
    } catch {
        ingredientProcessing.value = false;
    }
};

function openStoreModal(prefillName?: string) {
    newStoreName.value = prefillName || '';
    newStoreSections.value = [''];
    showStoreModal.value = true;
}

function addSectionInput() {
    newStoreSections.value.push('');
}

function removeSectionInput(index: number) {
    newStoreSections.value.splice(index, 1);
}

async function createStore() {
    if (!newStoreName.value.trim()) return;

    storeModalLoading.value = true;
    try {
        const sections = newStoreSections.value
            .map((s) => s.trim())
            .filter((s) => s.length > 0);

        const response = await fetch('/grocery-stores/quick', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find((row) => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] ?? '',
                ),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: newStoreName.value.trim(),
                sections: sections,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            const newStore: GroceryStore = {
                id: data.grocery_store.id,
                name: data.grocery_store.name,
                sections: data.grocery_store.sections || [],
            };
            localStores.value = [...localStores.value, newStore].sort((a, b) =>
                a.name.localeCompare(b.name),
            );
            selectedStoreId.value = newStore.id;
            showStoreModal.value = false;
        }
    } finally {
        storeModalLoading.value = false;
    }
}

function openSectionModal(prefillName?: string) {
    newSectionName.value = prefillName || '';
    showSectionModal.value = true;
}

async function createSection() {
    if (!newSectionName.value.trim() || !selectedStoreId.value) return;

    sectionModalLoading.value = true;
    try {
        const response = await fetch(
            `/grocery-stores/${selectedStoreId.value}/sections/quick`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie
                            .split('; ')
                            .find((row) => row.startsWith('XSRF-TOKEN='))
                            ?.split('=')[1] ?? '',
                    ),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    name: newSectionName.value.trim(),
                }),
            },
        );

        if (response.ok) {
            const data = await response.json();
            const newSection: GroceryStoreSection = {
                id: data.section.id,
                name: data.section.name,
                grocery_store_id: Number(selectedStoreId.value),
                sort_order: data.section.sort_order ?? 0,
            };

            // Update the store's sections in localStores
            localStores.value = localStores.value.map((store) => {
                if (store.id === Number(selectedStoreId.value)) {
                    return {
                        ...store,
                        sections: [...(store.sections || []), newSection].sort(
                            (a, b) => a.name.localeCompare(b.name),
                        ),
                    };
                }
                return store;
            });

            selectedSectionId.value = newSection.id;
            showSectionModal.value = false;
        }
    } finally {
        sectionModalLoading.value = false;
    }
}

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
                                class="space-y-2 rounded-lg p-2 transition-colors"
                                :class="{
                                    'bg-primary/10 ring-2 ring-primary/30': storeDraggingItemId !== null,
                                }"
                                @dragover="handleStoreDragOver"
                                @drop="handleStoreDropOnSection($event, storeGroup.storeId, section.sectionId)"
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
                                    class="flex flex-col gap-2 rounded-lg border border-border/70 bg-background p-4 md:flex-row md:items-center md:justify-between"
                                    :data-test="`shopping-item-${item.id}`"
                                    :class="{
                                        'cursor-grab active:cursor-grabbing': !item.is_purchased,
                                        'opacity-50': storeDraggingItemId !== null && storeDraggingItemId !== item.id,
                                        'opacity-50 bg-muted/30': item.is_purchased,
                                    }"
                                    :draggable="!item.is_purchased"
                                    @dragstart="handleStoreDragStart($event, item)"
                                    @dragend="handleDragEnd"
                                >
                                    <div class="flex items-start gap-3">
                                        <button
                                            v-if="!item.is_purchased"
                                            type="button"
                                            class="mt-0.5 text-muted-foreground"
                                            aria-label="Drag to move to another store or section"
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

                <DialogFooter class="flex-col gap-2 sm:flex-row sm:justify-between">
                    <Button
                        variant="outline"
                        @click="openIngredientModal(editingItem!)"
                        :disabled="editProcessing || !editingItem?.ingredient"
                    >
                        <Pencil class="mr-2 size-4" />
                        Edit ingredient
                    </Button>
                    <div class="flex gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button
                            @click="saveItemEdit"
                            :disabled="editProcessing"
                        >
                            Save changes
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Ingredient Edit Modal -->
        <Dialog v-model:open="showIngredientModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit ingredient</DialogTitle>
                    <DialogDescription>
                        Update ingredient details. Changes will apply to all recipes using this ingredient.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="grid gap-2">
                        <Label for="ingredient-name">Ingredient name</Label>
                        <Input
                            id="ingredient-name"
                            v-model="ingredientName"
                        />
                        <InputError :message="ingredientErrors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Grocery store (optional)</Label>
                        <Combobox
                            v-model="selectedStoreId"
                            :options="storeOptions"
                            placeholder="Select or create a store..."
                            allow-create
                            create-label="Create store"
                            @create="openStoreModal"
                        />
                        <InputError :message="ingredientErrors.grocery_store_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Store section (optional)</Label>
                        <Combobox
                            v-model="selectedSectionId"
                            :options="sectionOptions"
                            placeholder="Select or create a section..."
                            :disabled="!selectedStoreId"
                            allow-create
                            create-label="Create section"
                            @create="openSectionModal"
                        />
                        <InputError :message="ingredientErrors.grocery_store_section_id" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        @click="saveIngredient"
                        :disabled="ingredientProcessing || !ingredientName.trim()"
                    >
                        Save ingredient
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Store Creation Modal -->
        <Dialog v-model:open="showStoreModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Create grocery store</DialogTitle>
                    <DialogDescription>
                        Add a new store and optionally define its sections.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="grid gap-2">
                        <Label for="store-name">Store name</Label>
                        <Input
                            id="store-name"
                            v-model="newStoreName"
                            placeholder="Whole Foods"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label>Sections (optional)</Label>
                        <div class="space-y-2">
                            <div
                                v-for="(_, index) in newStoreSections"
                                :key="index"
                                class="flex gap-2"
                            >
                                <Input
                                    v-model="newStoreSections[index]"
                                    placeholder="e.g., Produce, Dairy, Bakery"
                                />
                                <Button
                                    v-if="newStoreSections.length > 1"
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    @click="removeSectionInput(index)"
                                >
                                    ×
                                </Button>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addSectionInput"
                            >
                                Add section
                            </Button>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        @click="createStore"
                        :disabled="storeModalLoading || !newStoreName.trim()"
                    >
                        Create store
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Section Creation Modal -->
        <Dialog v-model:open="showSectionModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Create section</DialogTitle>
                    <DialogDescription>
                        Add a new section to the selected store.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="grid gap-2">
                        <Label for="section-name">Section name</Label>
                        <Input
                            id="section-name"
                            v-model="newSectionName"
                            placeholder="e.g., Produce"
                        />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        @click="createSection"
                        :disabled="sectionModalLoading || !newSectionName.trim()"
                    >
                        Create section
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
