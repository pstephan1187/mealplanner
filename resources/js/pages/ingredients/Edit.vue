<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import { edit, index as ingredientsIndex, update } from '@/routes/ingredients';
import { type BreadcrumbItem } from '@/types';

interface GroceryStoreSection {
    id: number;
    name: string;
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
}

const props = defineProps<{
    ingredient: ResourceProp<Ingredient>;
    groceryStores: ResourceProp<GroceryStore[]>;
}>();

const ingredient = resolveResource(props.ingredient);
const groceryStores = resolveResource(props.groceryStores);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ingredients',
        href: ingredientsIndex().url,
    },
    {
        title: ingredient.name,
        href: edit(ingredient).url,
    },
];

// Local state for stores (so we can add new ones)
const localStores = ref<GroceryStore[]>([...groceryStores]);

const selectedStoreId = ref<number | string>(
    ingredient.grocery_store_id ?? '',
);
const selectedSectionId = ref<number | string>(
    ingredient.grocery_store_section_id ?? '',
);

// Store creation modal
const showStoreModal = ref(false);
const newStoreName = ref('');
const newStoreSections = ref<string[]>(['']);
const storeModalLoading = ref(false);

// Section creation modal
const showSectionModal = ref(false);
const newSectionName = ref('');
const sectionModalLoading = ref(false);

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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit ingredient" />

        <div class="flex flex-col gap-8 px-6 py-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    title="Edit ingredient"
                    description="Rename or refine this pantry item."
                />
                <Button variant="ghost" as-child>
                    <Link :href="ingredientsIndex()">Back to ingredients</Link>
                </Button>
            </div>

            <Form
                v-bind="update.form.patch(ingredient)"
                class="max-w-xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Ingredient name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="ingredient.name"
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

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="ingredientsIndex()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>
                </div>
            </Form>
        </div>

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
