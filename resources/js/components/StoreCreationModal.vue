<script setup lang="ts">
import { ref } from 'vue';

import { storeQuick } from '@/actions/App/Http/Controllers/GroceryStoreController';
import { Button } from '@/components/ui/button';
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
import { apiFetch } from '@/lib/utils';
import type { GroceryStore } from '@/types/models';

const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
    prefillName?: string;
}>();

const emit = defineEmits<{
    'store-created': [store: GroceryStore];
}>();

const storeName = ref(props.prefillName ?? '');
const sections = ref<string[]>(['']);
const loading = ref(false);

function resetForm(prefill?: string) {
    storeName.value = prefill ?? '';
    sections.value = [''];
    loading.value = false;
}

function addSectionInput() {
    sections.value.push('');
}

function removeSectionInput(index: number) {
    sections.value.splice(index, 1);
}

async function createStore() {
    if (!storeName.value.trim()) return;

    loading.value = true;
    try {
        const filteredSections = sections.value
            .map((s) => s.trim())
            .filter((s) => s.length > 0);

        const response = await apiFetch(storeQuick.url(), {
            method: 'POST',
            body: JSON.stringify({
                name: storeName.value.trim(),
                sections: filteredSections,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            const newStore: GroceryStore = {
                id: data.grocery_store.id,
                name: data.grocery_store.name,
                sections: data.grocery_store.sections || [],
            };
            emit('store-created', newStore);
            open.value = false;
        }
    } finally {
        loading.value = false;
    }
}

// Reset form when dialog opens
function handleOpenChange(value: boolean) {
    if (value) {
        resetForm(props.prefillName);
    }
}

defineExpose({ resetForm });
</script>

<template>
    <Dialog v-model:open="open" @update:open="handleOpenChange">
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
                        v-model="storeName"
                        placeholder="Whole Foods"
                    />
                </div>

                <div class="grid gap-2">
                    <Label>Sections (optional)</Label>
                    <div class="space-y-2">
                        <div
                            v-for="(_, index) in sections"
                            :key="index"
                            class="flex gap-2"
                        >
                            <Input
                                v-model="sections[index]"
                                placeholder="e.g., Produce, Dairy, Bakery"
                            />
                            <Button
                                v-if="sections.length > 1"
                                type="button"
                                variant="ghost"
                                size="icon"
                                @click="removeSectionInput(index)"
                            >
                                &times;
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
                    :disabled="loading || !storeName.trim()"
                >
                    Create store
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
