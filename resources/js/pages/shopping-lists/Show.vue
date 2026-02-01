<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, GripVertical, Pencil } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SectionCreationModal from '@/components/SectionCreationModal.vue';
import StoreCreationModal from '@/components/StoreCreationModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
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
import { useShoppingListDragDrop } from '@/composables/useShoppingListDragDrop';
import { useShoppingListItemEdit } from '@/composables/useShoppingListItemEdit';
import type { DisplayMode } from '@/composables/useShoppingListSorting';
import { useShoppingListSorting } from '@/composables/useShoppingListSorting';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    formatDateShort,
    resolveResource,
    type ResourceProp,
} from '@/lib/utils';
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
import { type BreadcrumbItem } from '@/types';
import type {
    GroceryStore,
    ShoppingList,
    ShoppingListItem,
} from '@/types/models';

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

const displayMode = ref<DisplayMode>(
    shoppingList.value.display_mode ?? 'manual',
);

const items = computed(() => shoppingList.value.items ?? []);

// Composables
const { manualItems, syncManualItems, sortedItems, groupedByStore } =
    useShoppingListSorting({ items, displayMode });

const {
    draggingItemId,
    storeDraggingItemId,
    moveItem,
    handleDragStart,
    handleDragOver,
    handleDrop,
    handleStoreDragStart,
    handleStoreDragOver,
    handleStoreDropOnSection,
    handleDragEnd,
} = useShoppingListDragDrop({
    shoppingListId: computed(() => shoppingList.value.id),
    displayMode,
    manualItems,
});

const {
    showEditModal,
    editingItem,
    editQuantity,
    editUnit,
    editIsPurchased,
    editErrors,
    editProcessing,
    openEditModal,
    saveItemEdit,
    showIngredientModal,
    ingredientName,
    ingredientErrors,
    ingredientProcessing,
    openIngredientModal,
    saveIngredient,
    selectedStoreId,
    selectedSectionId,
    storeOptions,
    sectionOptions,
    showStoreModal,
    showSectionModal,
    prefillStoreName,
    prefillSectionName,
    openStoreModal,
    openSectionModal,
    handleStoreCreated,
    handleSectionCreated,
} = useShoppingListItemEdit({ groceryStores });

// Watchers
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

watch(
    () => props.shoppingList,
    () => {
        syncManualItems();
    },
    { deep: true },
);

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
                    :title="shoppingList.meal_plan?.name ?? 'Shopping list'"
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
                                    query: {
                                        shopping_list_id: shoppingList.id,
                                    },
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
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
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
                        No items yet. Add ingredients from your pantry or a meal
                        plan.
                    </div>

                    <!-- Store grouped view -->
                    <template v-else-if="displayMode === 'store'">
                        <div
                            v-for="storeGroup in groupedByStore"
                            :key="storeGroup.storeName"
                            class="space-y-4"
                        >
                            <!-- Store header -->
                            <h3
                                class="border-b-2 border-primary pb-2 text-lg font-semibold text-foreground"
                            >
                                {{ storeGroup.storeName }}
                            </h3>

                            <!-- Sections within store -->
                            <div
                                v-for="section in storeGroup.sections"
                                :key="`${storeGroup.storeName}-${section.sectionName}`"
                                class="space-y-2 rounded-lg p-2 transition-colors"
                                :class="{
                                    'bg-primary/10 ring-2 ring-primary/30':
                                        storeDraggingItemId !== null,
                                }"
                                @dragover="handleStoreDragOver"
                                @drop="
                                    handleStoreDropOnSection(
                                        $event,
                                        storeGroup.storeId,
                                        section.sectionId,
                                    )
                                "
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
                                        'cursor-grab active:cursor-grabbing':
                                            !item.is_purchased,
                                        'opacity-50':
                                            storeDraggingItemId !== null &&
                                            storeDraggingItemId !== item.id,
                                        'bg-muted/30 opacity-50':
                                            item.is_purchased,
                                    }"
                                    :draggable="!item.is_purchased"
                                    @dragstart="
                                        handleStoreDragStart($event, item)
                                    "
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
                                                :class="{
                                                    'line-through':
                                                        item.is_purchased,
                                                }"
                                            >
                                                {{
                                                    item.ingredient?.name ??
                                                    'Ingredient'
                                                }}
                                            </p>
                                            <p
                                                class="text-sm text-muted-foreground"
                                            >
                                                {{ item.quantity }}
                                                {{ item.unit }}
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
                                    displayMode === 'manual' &&
                                    !item.is_purchased,
                                'opacity-50':
                                    draggingItemId !== null &&
                                    draggingItemId !== item.id,
                                'bg-muted/30 opacity-50': item.is_purchased,
                            }"
                            :draggable="
                                displayMode === 'manual' && !item.is_purchased
                            "
                            @dragstart="handleDragStart($event, item)"
                            @dragover="handleDragOver"
                            @drop="handleDrop($event, item)"
                            @dragend="handleDragEnd"
                        >
                            <div class="flex items-start gap-3">
                                <button
                                    v-if="
                                        displayMode === 'manual' &&
                                        !item.is_purchased
                                    "
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
                                        :class="{
                                            'line-through': item.is_purchased,
                                        }"
                                    >
                                        {{
                                            item.ingredient?.name ??
                                            'Ingredient'
                                        }}
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
                                    v-if="
                                        displayMode === 'manual' &&
                                        !item.is_purchased
                                    "
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
                                formatDateShort(
                                    shoppingList.meal_plan.start_date,
                                )
                            }}
                            <span v-if="shoppingList.meal_plan.end_date">
                                -
                            </span>
                            {{
                                formatDateShort(shoppingList.meal_plan.end_date)
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
                            @change="
                                editIsPurchased = (
                                    $event.target as HTMLInputElement
                                ).checked
                            "
                        />
                        <Label for="edit-is-purchased">Purchased</Label>
                    </div>
                </div>

                <DialogFooter
                    class="flex-col gap-2 sm:flex-row sm:justify-between"
                >
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
                        Update ingredient details. Changes will apply to all
                        recipes using this ingredient.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="grid gap-2">
                        <Label for="ingredient-name">Ingredient name</Label>
                        <Input id="ingredient-name" v-model="ingredientName" />
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
                        <InputError
                            :message="ingredientErrors.grocery_store_id"
                        />
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
                        <InputError
                            :message="ingredientErrors.grocery_store_section_id"
                        />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        @click="saveIngredient"
                        :disabled="
                            ingredientProcessing || !ingredientName.trim()
                        "
                    >
                        Save ingredient
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Store & Section Creation Modals -->
        <StoreCreationModal
            v-model:open="showStoreModal"
            :prefill-name="prefillStoreName"
            @store-created="handleStoreCreated"
        />
        <SectionCreationModal
            v-model:open="showSectionModal"
            :store-id="selectedStoreId"
            :prefill-name="prefillSectionName"
            @section-created="handleSectionCreated"
        />
    </AppLayout>
</template>
