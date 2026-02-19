<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import { bulkStore } from '@/actions/App/Http/Controllers/IngredientController';
import { Button } from '@/components/ui/button';
import { Combobox, type ComboboxOption } from '@/components/ui/combobox';
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


export interface UnmatchedIngredient {
    rowIndex: number;
    name: string;
    quantity: string | number | null;
    unit: string | null;
    suggestions: Array<{ id: number; name: string }>;
}

interface ResolvedIngredient {
    id: number;
    name: string;
}

type ResolutionMode = 'match' | 'create';

interface RowState {
    mode: ResolutionMode;
    matchedId: number | string | '';
    newName: string;
    storeId: number | string | '';
    sectionId: number | string | '';
}

const props = defineProps<{
    open: boolean;
    unmatchedIngredients: UnmatchedIngredient[];
    existingIngredients: ComboboxOption[];
    groceryStores: GroceryStore[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'resolved', map: Record<number, ResolvedIngredient>): void;
}>();

const saving = ref(false);
const saveError = ref('');

const localStores = ref<GroceryStore[]>([...props.groceryStores]);

const rows = ref<RowState[]>([]);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            rows.value = props.unmatchedIngredients.map((item) => ({
                mode: item.suggestions.length > 0 ? 'match' : 'create',
                matchedId: item.suggestions[0]?.id ?? '',
                newName: item.name,
                storeId: '',
                sectionId: '',
            }));
            saveError.value = '';
        }
    },
);

function storeOptionsFor(row: RowState) {
    return localStores.value.map((s) => ({ id: s.id, name: s.name }));
}

function sectionOptionsFor(row: RowState) {
    if (!row.storeId) return [];
    const store = localStores.value.find((s) => s.id === Number(row.storeId));
    return (store?.sections ?? []).map((s) => ({ id: s.id, name: s.name }));
}

const newCount = computed(
    () => rows.value.filter((r) => r.mode === 'create').length,
);
const matchedCount = computed(
    () => rows.value.filter((r) => r.mode === 'match' && r.matchedId !== '').length,
);

const canSave = computed(() => {
    return rows.value.every((row) => {
        if (row.mode === 'match') return row.matchedId !== '';
        return row.newName.trim() !== '';
    });
});

const saveLabel = computed(() => {
    const parts: string[] = [];
    if (newCount.value > 0) parts.push(`${newCount.value} new`);
    if (matchedCount.value > 0) parts.push(`${matchedCount.value} matched`);
    return parts.length > 0 ? `Save all (${parts.join(', ')})` : 'Save all';
});

async function handleSave() {
    saving.value = true;
    saveError.value = '';

    try {
        const resolutionMap: Record<number, ResolvedIngredient> = {};

        // Collect rows that need new ingredients created
        const toCreate: Array<{ index: number; data: RowState }> = [];
        rows.value.forEach((row, i) => {
            const original = props.unmatchedIngredients[i];
            if (row.mode === 'match' && row.matchedId !== '') {
                const matched = props.existingIngredients.find(
                    (ing) => ing.id === row.matchedId,
                );
                resolutionMap[original.rowIndex] = {
                    id: Number(row.matchedId),
                    name: matched?.name ?? '',
                };
            } else if (row.mode === 'create') {
                toCreate.push({ index: i, data: row });
            }
        });

        // Bulk create new ingredients if any
        if (toCreate.length > 0) {
            const response = await apiFetch(bulkStore.url(), {
                method: 'POST',
                body: JSON.stringify({
                    ingredients: toCreate.map(({ data }) => ({
                        name: data.newName.trim(),
                        grocery_store_id: data.storeId || null,
                        grocery_store_section_id: data.sectionId || null,
                    })),
                }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                saveError.value =
                    errorData.message || 'Failed to create ingredients.';
                return;
            }

            const created = await response.json();
            toCreate.forEach(({ index }, i) => {
                const original = props.unmatchedIngredients[index];
                resolutionMap[original.rowIndex] = {
                    id: created.ingredients[i].id,
                    name: created.ingredients[i].name,
                };
            });
        }

        emit('resolved', resolutionMap);
        emit('update:open', false);
    } catch {
        saveError.value = 'A network error occurred. Please try again.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Dialog
        :open="props.open"
        @update:open="(val) => emit('update:open', val)"
    >
        <DialogContent class="sm:max-w-2xl max-h-[85vh] flex flex-col">
            <DialogHeader>
                <DialogTitle>Resolve unmatched ingredients</DialogTitle>
                <DialogDescription>
                    Match imported ingredients to existing ones or create new
                    ones.
                </DialogDescription>
            </DialogHeader>

            <div v-if="rows.length > 0 && unmatchedIngredients.length === rows.length" class="flex-1 overflow-y-auto space-y-4 py-4">
                <div
                    v-for="(row, index) in rows"
                    :key="index"
                    class="rounded-lg border border-border p-4 space-y-3"
                >
                    <div class="flex items-baseline justify-between gap-4">
                        <div>
                            <p class="font-medium">
                                {{ unmatchedIngredients[index].name }}
                            </p>
                            <p
                                v-if="
                                    unmatchedIngredients[index].quantity ||
                                    unmatchedIngredients[index].unit
                                "
                                class="text-xs text-muted-foreground"
                            >
                                {{
                                    [
                                        unmatchedIngredients[index].quantity,
                                        unmatchedIngredients[index].unit,
                                    ]
                                        .filter(Boolean)
                                        .join(' ')
                                }}
                            </p>
                        </div>
                        <div class="flex gap-1 rounded-md bg-muted p-0.5">
                            <Button
                                type="button"
                                :variant="
                                    row.mode === 'match'
                                        ? 'secondary'
                                        : 'ghost'
                                "
                                size="sm"
                                class="h-7 text-xs px-2"
                                @click="row.mode = 'match'"
                            >
                                Match existing
                            </Button>
                            <Button
                                type="button"
                                :variant="
                                    row.mode === 'create'
                                        ? 'secondary'
                                        : 'ghost'
                                "
                                size="sm"
                                class="h-7 text-xs px-2"
                                @click="row.mode = 'create'"
                            >
                                Create new
                            </Button>
                        </div>
                    </div>

                    <div v-if="row.mode === 'match'">
                        <Combobox
                            v-model="row.matchedId"
                            :options="existingIngredients"
                            placeholder="Search ingredients..."
                        />
                    </div>

                    <div v-else class="space-y-3">
                        <div class="grid gap-2">
                            <Label>Name</Label>
                            <Input v-model="row.newName" />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label>Grocery store (optional)</Label>
                                <Combobox
                                    v-model="row.storeId"
                                    :options="storeOptionsFor(row)"
                                    placeholder="Select store..."
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label>Store section (optional)</Label>
                                <Combobox
                                    v-model="row.sectionId"
                                    :options="sectionOptionsFor(row)"
                                    placeholder="Select section..."
                                    :disabled="!row.storeId"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="saveError" class="text-sm text-destructive">
                {{ saveError }}
            </p>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    @click="handleSave"
                    :disabled="saving || !canSave"
                >
                    {{ saving ? 'Saving...' : saveLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
