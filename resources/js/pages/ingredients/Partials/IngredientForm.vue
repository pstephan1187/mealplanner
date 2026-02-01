<script setup lang="ts">
import { ref } from 'vue';

import InputError from '@/components/InputError.vue';
import SectionCreationModal from '@/components/SectionCreationModal.vue';
import StoreCreationModal from '@/components/StoreCreationModal.vue';
import { Combobox } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useStoreAndSectionModals } from '@/composables/useStoreAndSectionModals';
import { useStoreSelection } from '@/composables/useStoreSelection';
import type {
    GroceryStore,
    GroceryStoreSection,
    Ingredient,
} from '@/types/models';

interface Props {
    groceryStores: GroceryStore[];
    ingredient?: Ingredient | null;
    errors: Record<string, string | undefined>;
}

const props = defineProps<Props>();

const localStores = ref<GroceryStore[]>([...props.groceryStores]);

const { selectedStoreId, selectedSectionId, storeOptions, sectionOptions } =
    useStoreSelection(localStores, {
        initialStoreId: props.ingredient?.grocery_store_id ?? '',
        initialSectionId: props.ingredient?.grocery_store_section_id ?? '',
    });

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
</script>

<template>
    <div class="grid gap-2">
        <Label for="name">Ingredient name</Label>
        <Input
            id="name"
            name="name"
            placeholder="Extra virgin olive oil"
            :default-value="ingredient?.name"
            required
        />
        <InputError :message="errors.name" />
    </div>

    <div class="grid gap-2">
        <Label>Grocery store (optional)</Label>
        <Combobox
            v-model="selectedStoreId"
            :options="storeOptions"
            placeholder="Select or create a store..."
            name="grocery_store_id"
            allow-create
            create-label="Create store"
            @create="openStoreModal"
        />
        <InputError :message="errors.grocery_store_id" />
    </div>

    <div class="grid gap-2">
        <Label>Store section (optional)</Label>
        <Combobox
            v-model="selectedSectionId"
            :options="sectionOptions"
            placeholder="Select or create a section..."
            name="grocery_store_section_id"
            :disabled="!selectedStoreId"
            allow-create
            create-label="Create section"
            @create="openSectionModal"
        />
        <InputError :message="errors.grocery_store_section_id" />
    </div>

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
</template>
