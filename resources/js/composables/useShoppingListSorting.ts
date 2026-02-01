import { computed, ref, watch, type Ref } from 'vue';

import type { ShoppingListItem } from '@/types/models';

export interface SectionGroup {
    sectionName: string | null;
    sectionId: number | null;
    sortOrder: number;
    items: ShoppingListItem[];
}

export interface StoreGroup {
    storeName: string;
    storeId: number | null;
    isUnassigned: boolean;
    sections: SectionGroup[];
}

export type DisplayMode = 'manual' | 'alphabetical' | 'store';

interface UseShoppingListSortingOptions {
    items: Ref<ShoppingListItem[]>;
    displayMode: Ref<DisplayMode>;
}

const normalizeOrder = (items: ShoppingListItem[]): ShoppingListItem[] =>
    items.map((item, index) => ({
        ...item,
        sort_order: index + 1,
    }));

export function useShoppingListSorting({
    items,
    displayMode,
}: UseShoppingListSortingOptions) {
    const manualItems = ref<ShoppingListItem[]>([]);

    const syncManualItems = () => {
        const raw = [...items.value];

        raw.sort((a, b) => {
            const orderA = a.sort_order ?? Number.MAX_SAFE_INTEGER;
            const orderB = b.sort_order ?? Number.MAX_SAFE_INTEGER;

            if (orderA === orderB) {
                return (a.ingredient?.name ?? '').localeCompare(
                    b.ingredient?.name ?? '',
                );
            }

            return orderA - orderB;
        });

        manualItems.value = normalizeOrder(raw);
    };

    watch(items, () => syncManualItems(), { immediate: true, deep: true });

    const sortedItems = computed(() => {
        const raw = [...items.value];

        if (displayMode.value === 'alphabetical') {
            return raw.sort((a, b) => {
                if (a.is_purchased !== b.is_purchased) {
                    return a.is_purchased ? 1 : -1;
                }
                const nameA = a.ingredient?.name ?? '';
                const nameB = b.ingredient?.name ?? '';
                return nameA.localeCompare(nameB);
            });
        }

        if (displayMode.value === 'store') {
            return raw.sort((a, b) => {
                if (a.is_purchased !== b.is_purchased) {
                    return a.is_purchased ? 1 : -1;
                }

                const storeA = a.effective_grocery_store;
                const storeB = b.effective_grocery_store;

                if (!storeA && storeB) return -1;
                if (storeA && !storeB) return 1;

                if (storeA && storeB) {
                    const storeCompare = storeA.name.localeCompare(storeB.name);
                    if (storeCompare !== 0) return storeCompare;
                }

                const sectionA = a.effective_grocery_store_section;
                const sectionB = b.effective_grocery_store_section;

                if (!sectionA && sectionB) return -1;
                if (sectionA && !sectionB) return 1;

                if (sectionA && sectionB) {
                    const sectionCompare =
                        (sectionA.sort_order ?? 0) - (sectionB.sort_order ?? 0);
                    if (sectionCompare !== 0) return sectionCompare;
                }

                const nameA = a.ingredient?.name ?? '';
                const nameB = b.ingredient?.name ?? '';
                return nameA.localeCompare(nameB);
            });
        }

        // Manual mode — purchased items go to the bottom
        const unpurchased = manualItems.value.filter(
            (item) => !item.is_purchased,
        );
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

            if (!stores.has(storeName)) {
                stores.set(storeName, {
                    storeName,
                    storeId,
                    isUnassigned,
                    sections: [],
                });
            }

            const storeGroup = stores.get(storeName)!;

            let sectionGroup = storeGroup.sections.find(
                (s) => s.sectionName === sectionName,
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
                if (a.sectionName === null && b.sectionName !== null) return -1;
                if (a.sectionName !== null && b.sectionName === null) return 1;
                return a.sortOrder - b.sortOrder;
            });
        }

        return sortedStores;
    });

    return {
        manualItems,
        syncManualItems,
        sortedItems,
        groupedByStore,
    };
}
