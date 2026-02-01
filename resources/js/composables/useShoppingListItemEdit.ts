import { router } from '@inertiajs/vue3';
import { nextTick, ref, type Ref } from 'vue';

import { useStoreAndSectionModals } from '@/composables/useStoreAndSectionModals';
import { useStoreSelection } from '@/composables/useStoreSelection';
import { apiFetch } from '@/lib/utils';
import { update as updateIngredient } from '@/routes/ingredients';
import { update as updateShoppingListItem } from '@/routes/shopping-list-items';
import type {
    GroceryStore,
    GroceryStoreSection,
    ShoppingListItem,
} from '@/types/models';

interface UseShoppingListItemEditOptions {
    groceryStores: Ref<GroceryStore[]>;
}

export function useShoppingListItemEdit(
    options: UseShoppingListItemEditOptions,
) {
    const { groceryStores } = options;

    // --- Edit item modal state ---
    const showEditModal = ref(false);
    const editingItem = ref<ShoppingListItem | null>(null);
    const editQuantity = ref('');
    const editUnit = ref('');
    const editIsPurchased = ref(false);
    const editErrors = ref<Record<string, string>>({});
    const editProcessing = ref(false);

    // --- Ingredient edit modal state ---
    const showIngredientModal = ref(false);
    const editingIngredientItem = ref<ShoppingListItem | null>(null);
    const ingredientName = ref('');
    const ingredientErrors = ref<Record<string, string>>({});
    const ingredientProcessing = ref(false);

    // Local stores for the ingredient edit modal (can be mutated with new stores/sections)
    const localStores = ref<GroceryStore[]>([]);

    // Store/section selection for the ingredient modal
    const { selectedStoreId, selectedSectionId, storeOptions, sectionOptions } =
        useStoreSelection(localStores);

    // Store/section modal management (create new stores/sections from within ingredient edit)
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
            selectedStoreId.value = store.id;
        },
        onSectionCreated: (section: GroceryStoreSection) => {
            localStores.value = localStores.value.map((store) => {
                if (store.id === Number(selectedStoreId.value)) {
                    return {
                        ...store,
                        sections: [...(store.sections || []), section].sort(
                            (a, b) => a.name.localeCompare(b.name),
                        ),
                    };
                }
                return store;
            });
            selectedSectionId.value = section.id;
        },
    });

    // --- Edit item modal methods ---

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

    // --- Ingredient edit modal methods ---

    const openIngredientModal = async (item: ShoppingListItem) => {
        editingIngredientItem.value = item;
        ingredientName.value = item.ingredient?.name ?? '';
        localStores.value = [...groceryStores.value];
        selectedStoreId.value = item.ingredient?.grocery_store_id ?? '';
        selectedSectionId.value =
            item.ingredient?.grocery_store_section_id ?? '';
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
            const response = await apiFetch(
                updateIngredient.url(ingredientId),
                {
                    method: 'PATCH',
                    redirect: 'manual',
                    body: JSON.stringify({
                        name: ingredientName.value,
                        grocery_store_id: selectedStoreId.value || null,
                        grocery_store_section_id:
                            selectedSectionId.value || null,
                    }),
                },
            );

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

    return {
        // Edit item modal
        showEditModal,
        editingItem,
        editQuantity,
        editUnit,
        editIsPurchased,
        editErrors,
        editProcessing,
        openEditModal,
        saveItemEdit,

        // Ingredient edit modal
        showIngredientModal,
        editingIngredientItem,
        ingredientName,
        ingredientErrors,
        ingredientProcessing,
        openIngredientModal,
        saveIngredient,

        // Store/section selection (for ingredient modal's store/section dropdowns)
        selectedStoreId,
        selectedSectionId,
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
