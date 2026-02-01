import { ref, type Ref } from 'vue';

import { storeQuick } from '@/actions/App/Http/Controllers/IngredientController';
import { useStoreAndSectionModals } from '@/composables/useStoreAndSectionModals';
import { useStoreSelection } from '@/composables/useStoreSelection';
import { apiFetch } from '@/lib/utils';
import type { GroceryStore, GroceryStoreSection } from '@/types/models';

interface CreatedIngredient {
    id: number;
    name: string;
}

interface UseRecipeIngredientModalsOptions {
    onIngredientCreated?: (
        ingredient: CreatedIngredient,
        rowIndex: number | null,
    ) => void;
}

export function useRecipeIngredientModals(
    localStores: Ref<GroceryStore[]>,
    options: UseRecipeIngredientModalsOptions = {},
) {
    // Ingredient creation modal state
    const showIngredientModal = ref(false);
    const ingredientModalRowIndex = ref<number | null>(null);
    const newIngredientName = ref('');
    const ingredientModalLoading = ref(false);

    // Store/section selection for the ingredient modal
    const {
        selectedStoreId: newIngredientStoreId,
        selectedSectionId: newIngredientSectionId,
        storeOptions,
        sectionOptions,
    } = useStoreSelection(localStores);

    // Store/section modal management for creating new stores/sections from within the ingredient modal
    const {
        showStoreModal,
        showSectionModal,
        prefillStoreName,
        prefillSectionName,
        openStoreModal,
        openSectionModal,
        handleStoreCreated,
        handleSectionCreated,
    } = useStoreAndSectionModals({
        onStoreCreated: (store: GroceryStore) => {
            localStores.value = [...localStores.value, store].sort((a, b) =>
                a.name.localeCompare(b.name),
            );
            newIngredientStoreId.value = store.id;
        },
        onSectionCreated: (section: GroceryStoreSection) => {
            localStores.value = localStores.value.map((store) => {
                if (store.id === Number(newIngredientStoreId.value)) {
                    return {
                        ...store,
                        sections: [...(store.sections || []), section].sort(
                            (a, b) => a.name.localeCompare(b.name),
                        ),
                    };
                }
                return store;
            });
            newIngredientSectionId.value = section.id;
        },
    });

    function openIngredientModal(name: string, rowIndex: number) {
        newIngredientName.value = name;
        newIngredientStoreId.value = '';
        newIngredientSectionId.value = '';
        ingredientModalRowIndex.value = rowIndex;
        showIngredientModal.value = true;
    }

    async function createIngredient() {
        if (!newIngredientName.value.trim()) return;

        ingredientModalLoading.value = true;
        try {
            const response = await apiFetch(storeQuick.url(), {
                method: 'POST',
                body: JSON.stringify({
                    name: newIngredientName.value.trim(),
                    grocery_store_id: newIngredientStoreId.value || null,
                    grocery_store_section_id:
                        newIngredientSectionId.value || null,
                }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                console.error('Failed to create ingredient:', errorData);
                return;
            }

            const data = await response.json();
            const newIngredient: CreatedIngredient = {
                id: data.ingredient.id,
                name: data.ingredient.name,
            };

            options.onIngredientCreated?.(
                newIngredient,
                ingredientModalRowIndex.value,
            );
            showIngredientModal.value = false;
        } catch (error) {
            console.error('Error creating ingredient:', error);
        } finally {
            ingredientModalLoading.value = false;
        }
    }

    return {
        // Ingredient modal state
        showIngredientModal,
        ingredientModalRowIndex,
        newIngredientName,
        ingredientModalLoading,
        openIngredientModal,
        createIngredient,

        // Store/section selection (for ingredient modal's store/section dropdowns)
        newIngredientStoreId,
        newIngredientSectionId,
        storeOptions,
        sectionOptions,

        // Store/section modal management (for creating new stores/sections)
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
