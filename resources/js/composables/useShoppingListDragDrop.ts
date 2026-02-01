import { router } from '@inertiajs/vue3';
import { ref, type Ref } from 'vue';

import { update as updateShoppingListItem } from '@/routes/shopping-list-items';
import { order as orderShoppingListItems } from '@/routes/shopping-lists/items';
import type { ShoppingListItem } from '@/types/models';

import type { DisplayMode } from './useShoppingListSorting';

interface UseShoppingListDragDropOptions {
    shoppingListId: Ref<number>;
    displayMode: Ref<DisplayMode>;
    manualItems: Ref<ShoppingListItem[]>;
}

const normalizeOrder = (items: ShoppingListItem[]): ShoppingListItem[] =>
    items.map((item, index) => ({
        ...item,
        sort_order: index + 1,
    }));

export function useShoppingListDragDrop({
    shoppingListId,
    displayMode,
    manualItems,
}: UseShoppingListDragDropOptions) {
    const draggingItemId = ref<number | null>(null);
    const storeDraggingItemId = ref<number | null>(null);

    // ── Persist helpers ──────────────────────────────────────────────────

    const persistOrder = (items: ShoppingListItem[]) => {
        const payload = items.map((item, index) => ({
            id: item.id,
            sort_order: index + 1,
        }));

        router.patch(
            orderShoppingListItems(shoppingListId.value),
            { items: payload },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    // ── Manual mode ──────────────────────────────────────────────────────

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
        event.dataTransfer?.setDragImage(
            event.currentTarget as Element,
            20,
            20,
        );
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

    // ── Store mode ───────────────────────────────────────────────────────

    const handleStoreDragStart = (event: DragEvent, item: ShoppingListItem) => {
        if (displayMode.value !== 'store' || item.is_purchased) {
            return;
        }

        storeDraggingItemId.value = item.id;
        event.dataTransfer?.setData('text/plain', item.id.toString());
        event.dataTransfer?.setDragImage(
            event.currentTarget as Element,
            20,
            20,
        );
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
        sectionId: number | null,
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

    // ── Shared ───────────────────────────────────────────────────────────

    const handleDragEnd = () => {
        draggingItemId.value = null;
        storeDraggingItemId.value = null;
    };

    return {
        // State
        draggingItemId,
        storeDraggingItemId,

        // Manual mode
        moveItem,
        handleDragStart,
        handleDragOver,
        handleDrop,

        // Store mode
        handleStoreDragStart,
        handleStoreDragOver,
        handleStoreDropOnSection,

        // Shared
        handleDragEnd,
    };
}
