<script setup lang="ts">
import { ref } from 'vue';

import { storeQuick } from '@/actions/App/Http/Controllers/GroceryStoreSectionController';
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
import type { GroceryStoreSection } from '@/types/models';

const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
    storeId: number | string;
    prefillName?: string;
}>();

const emit = defineEmits<{
    'section-created': [section: GroceryStoreSection];
}>();

const sectionName = ref(props.prefillName ?? '');
const loading = ref(false);

function resetForm(prefill?: string) {
    sectionName.value = prefill ?? '';
    loading.value = false;
}

async function createSection() {
    if (!sectionName.value.trim() || !props.storeId) return;

    loading.value = true;
    try {
        const response = await apiFetch(storeQuick.url(props.storeId), {
            method: 'POST',
            body: JSON.stringify({
                name: sectionName.value.trim(),
            }),
        });

        if (response.ok) {
            const data = await response.json();
            const newSection: GroceryStoreSection = {
                id: data.section.id,
                name: data.section.name,
            };
            emit('section-created', newSection);
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
                        v-model="sectionName"
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
                    :disabled="loading || !sectionName.trim()"
                >
                    Create section
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
