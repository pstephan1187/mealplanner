import { computed, ref, watch, type Ref } from 'vue';

import type { GroceryStore, GroceryStoreSection } from '@/types/models';

interface UseStoreSelectionOptions {
    initialStoreId?: number | string;
    initialSectionId?: number | string;
}

export function useStoreSelection(
    stores: Ref<GroceryStore[]>,
    options: UseStoreSelectionOptions = {},
) {
    const selectedStoreId = ref<number | string>(options.initialStoreId ?? '');
    const selectedSectionId = ref<number | string>(
        options.initialSectionId ?? '',
    );

    const storeOptions = computed(() =>
        stores.value.map((s) => ({ id: s.id, name: s.name })),
    );

    const availableSections = computed<GroceryStoreSection[]>(() => {
        if (!selectedStoreId.value) return [];
        const store = stores.value.find(
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

    return {
        selectedStoreId,
        selectedSectionId,
        storeOptions,
        availableSections,
        sectionOptions,
    };
}
