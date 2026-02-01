import { ref } from 'vue';

import type { GroceryStore, GroceryStoreSection } from '@/types/models';

interface UseStoreAndSectionModalsOptions {
    onStoreCreated?: (store: GroceryStore) => void;
    onSectionCreated?: (section: GroceryStoreSection) => void;
}

export function useStoreAndSectionModals(
    options: UseStoreAndSectionModalsOptions = {},
) {
    const showStoreModal = ref(false);
    const showSectionModal = ref(false);
    const prefillStoreName = ref('');
    const prefillSectionName = ref('');

    function openStoreModal(prefillName?: string) {
        prefillStoreName.value = prefillName ?? '';
        showStoreModal.value = true;
    }

    function openSectionModal(prefillName?: string) {
        prefillSectionName.value = prefillName ?? '';
        showSectionModal.value = true;
    }

    function handleStoreCreated(store: GroceryStore) {
        options.onStoreCreated?.(store);
    }

    function handleSectionCreated(section: GroceryStoreSection) {
        options.onSectionCreated?.(section);
    }

    return {
        showStoreModal,
        showSectionModal,
        prefillStoreName,
        prefillSectionName,
        openStoreModal,
        openSectionModal,
        handleStoreCreated,
        handleSectionCreated,
    };
}
