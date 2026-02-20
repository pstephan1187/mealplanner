<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';

import IngredientResolutionModal, {
    type UnmatchedIngredient,
} from '@/components/IngredientResolutionModal.vue';
import InputError from '@/components/InputError.vue';
import SectionCreationModal from '@/components/SectionCreationModal.vue';
import StoreCreationModal from '@/components/StoreCreationModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { useRecipeIngredientModals } from '@/composables/useRecipeIngredientModals';
import type { GroceryStore, Ingredient, Recipe } from '@/types/models';

type IngredientOption = Pick<Ingredient, 'id' | 'name'>;

interface IngredientRow {
    ingredient_id: number | '';
    quantity: string;
    unit: string;
    note: string;
    importedName?: string;
    suggestions?: Array<{ id: number; name: string }>;
}

interface SectionRow {
    name: string;
    sort_order: number;
    instructions: string;
    ingredients: IngredientRow[];
}

interface Props {
    recipe?: Recipe | null;
    ingredients: IngredientOption[];
    groceryStores: GroceryStore[];
    errors: Record<string, string | undefined>;
}

const props = defineProps<Props>();

const localIngredients = ref<ComboboxOption[]>([...props.ingredients]);
const localStores = ref<GroceryStore[]>([...props.groceryStores]);

// Track which section the ingredient creation modal was opened from (null = flat mode)
const pendingSectionIndex = ref<number | null>(null);

const {
    showIngredientModal,
    newIngredientName,
    ingredientModalLoading,
    openIngredientModal,
    createIngredient,
    newIngredientStoreId,
    newIngredientSectionId,
    storeOptions,
    sectionOptions,
    showStoreModal,
    showSectionModal,
    prefillStoreName,
    prefillSectionName,
    openStoreModal,
    openSectionModal,
    handleStoreCreated,
    handleSectionCreated,
} = useRecipeIngredientModals(localStores, {
    onIngredientCreated: (ingredient, rowIndex) => {
        localIngredients.value.push({
            id: ingredient.id,
            name: ingredient.name,
        });
        localIngredients.value.sort((a, b) => a.name.localeCompare(b.name));

        if (rowIndex !== null) {
            if (pendingSectionIndex.value !== null) {
                const section = sectionRows.value[pendingSectionIndex.value];
                if (section?.ingredients[rowIndex]) {
                    section.ingredients[rowIndex].ingredient_id = ingredient.id;
                }
            } else {
                ingredientRows.value[rowIndex].ingredient_id = ingredient.id;
            }
        }
        pendingSectionIndex.value = null;
    },
});

// ─── Sections mode ───────────────────────────────────────────

const hasSectionsFromRecipe = (props.recipe?.sections?.length ?? 0) > 0;
const useSections = ref(hasSectionsFromRecipe);
const showRemoveSectionsDialog = ref(false);

const sectionRows = ref<SectionRow[]>(
    hasSectionsFromRecipe
        ? props.recipe!.sections!.map((section) => ({
              name: section.name,
              sort_order: section.sort_order,
              instructions: section.instructions ?? '',
              ingredients: (section.ingredients ?? []).map((ing) => ({
                  ingredient_id: ing.id || '',
                  quantity: ing.pivot?.quantity?.toString() ?? '',
                  unit: ing.pivot?.unit ?? '',
                  note: ing.pivot?.note ?? '',
              })),
          }))
        : [],
);

// ─── Flat ingredients ────────────────────────────────────────

const ingredientRows = ref<IngredientRow[]>(
    !hasSectionsFromRecipe && props.recipe?.ingredients?.length
        ? props.recipe.ingredients.map((ingredient) => ({
              ingredient_id: ingredient.id || '',
              quantity: ingredient.pivot?.quantity?.toString() ?? '',
              unit: ingredient.pivot?.unit ?? '',
              note: ingredient.pivot?.note ?? '',
              importedName:
                  !ingredient.id && ingredient.name
                      ? ingredient.name
                      : undefined,
              suggestions: ingredient.suggestions ?? [],
          }))
        : [],
);

const instructionsContent = ref(props.recipe?.instructions ?? '');
const hasImportedPhotoUrl = !!props.recipe?.photo_url && !props.recipe?.id;
const photoMode = ref<'upload' | 'url'>(hasImportedPhotoUrl ? 'url' : 'upload');
const photoUrlInput = ref(hasImportedPhotoUrl ? props.recipe!.photo_url! : '');
const photoPreview = ref<string | null>(props.recipe?.photo_url ?? null);
let photoObjectUrl: string | null = null;

const switchPhotoMode = (mode: 'upload' | 'url') => {
    photoMode.value = mode;
    photoPreview.value = props.recipe?.photo_url ?? null;
    photoUrlInput.value = '';

    if (photoObjectUrl) {
        URL.revokeObjectURL(photoObjectUrl);
        photoObjectUrl = null;
    }
};

const handlePhotoUrlInput = () => {
    photoPreview.value =
        (photoUrlInput.value || props.recipe?.photo_url) ?? null;
};

// ─── Flat ingredient row management ──────────────────────────

const addIngredientRow = () => {
    ingredientRows.value.push({
        ingredient_id: '',
        quantity: '',
        unit: '',
        note: '',
    });
};

const removeIngredientRow = (index: number) => {
    ingredientRows.value.splice(index, 1);
};

// ─── Section management ──────────────────────────────────────

const enableSections = () => {
    if (sectionRows.value.length === 0) {
        sectionRows.value.push({
            name: '',
            sort_order: 0,
            instructions: instructionsContent.value,
            ingredients: [...ingredientRows.value],
        });
    }
    ingredientRows.value = [];
    instructionsContent.value = '';
    useSections.value = true;
};

const disableSections = () => {
    if (sectionRows.value.length > 0) {
        const allIngredients = sectionRows.value.flatMap((s) => s.ingredients);
        ingredientRows.value = allIngredients;
        instructionsContent.value =
            sectionRows.value
                .filter((s) => s.name || s.instructions)
                .map((s) => {
                    const header = s.name ? `<h2>${s.name}</h2>` : '';
                    return header + (s.instructions || '');
                })
                .join('') || '';
    }
    sectionRows.value = [];
    useSections.value = false;
};

const addSection = () => {
    sectionRows.value.push({
        name: '',
        sort_order: sectionRows.value.length,
        instructions: '',
        ingredients: [],
    });
};

const removeSection = (index: number) => {
    sectionRows.value.splice(index, 1);
    sectionRows.value.forEach((s, i) => (s.sort_order = i));
};

const addSectionIngredient = (sectionIndex: number) => {
    sectionRows.value[sectionIndex].ingredients.push({
        ingredient_id: '',
        quantity: '',
        unit: '',
        note: '',
    });
};

const removeSectionIngredient = (
    sectionIndex: number,
    ingredientIndex: number,
) => {
    sectionRows.value[sectionIndex].ingredients.splice(ingredientIndex, 1);
};

const openSectionIngredientModal = (
    name: string,
    sectionIndex: number,
    ingredientIndex: number,
) => {
    pendingSectionIndex.value = sectionIndex;
    openIngredientModal(name, ingredientIndex);
};

// ─── Ingredient resolution ───────────────────────────────────

const showResolutionModal = ref(false);

const unmatchedIngredients = computed<UnmatchedIngredient[]>(() =>
    ingredientRows.value
        .map((row, index) => ({ row, index }))
        .filter(({ row }) => row.importedName && row.ingredient_id === '')
        .map(({ row, index }) => ({
            rowIndex: index,
            name: row.importedName!,
            quantity: row.quantity,
            unit: row.unit,
            suggestions: row.suggestions ?? [],
        })),
);

function handleIngredientsResolved(
    map: Record<number, { id: number; name: string }>,
) {
    for (const [rowIndex, resolved] of Object.entries(map)) {
        const idx = Number(rowIndex);
        ingredientRows.value[idx].ingredient_id = resolved.id;
        ingredientRows.value[idx].importedName = undefined;
        ingredientRows.value[idx].suggestions = [];

        if (!localIngredients.value.some((ing) => ing.id === resolved.id)) {
            localIngredients.value.push({
                id: resolved.id,
                name: resolved.name,
            });
            localIngredients.value.sort((a, b) => a.name.localeCompare(b.name));
        }
    }
}

// ─── Photo handling ──────────────────────────────────────────

const handlePhotoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        photoPreview.value = props.recipe?.photo_url ?? null;
        return;
    }

    if (photoObjectUrl) {
        URL.revokeObjectURL(photoObjectUrl);
    }

    photoObjectUrl = URL.createObjectURL(file);
    photoPreview.value = photoObjectUrl;
};

onBeforeUnmount(() => {
    if (photoObjectUrl) {
        URL.revokeObjectURL(photoObjectUrl);
    }
});
</script>

<template>
    <div class="space-y-8">
        <Card>
            <CardHeader>
                <CardTitle>Recipe basics</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2 md:col-span-2">
                    <Label for="name">Recipe name</Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="Weeknight lemon chicken"
                        :default-value="recipe?.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="servings">Servings</Label>
                    <Input
                        id="servings"
                        name="servings"
                        type="number"
                        min="1"
                        :default-value="recipe?.servings ?? 2"
                        required
                    />
                    <InputError :message="errors.servings" />
                </div>

                <div class="grid gap-2">
                    <Label for="flavor_profile">Flavor profile</Label>
                    <Input
                        id="flavor_profile"
                        name="flavor_profile"
                        placeholder="Bright, savory, herb-forward"
                        :default-value="recipe?.flavor_profile"
                        required
                    />
                    <InputError :message="errors.flavor_profile" />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Photo</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-6 md:grid-cols-[200px_1fr]">
                <div
                    class="flex size-48 items-center justify-center overflow-hidden rounded-xl border border-dashed border-border bg-muted text-xs text-muted-foreground"
                >
                    <img
                        v-if="photoPreview"
                        :src="photoPreview"
                        alt="Recipe photo preview"
                        class="size-full object-cover"
                    />
                    <span v-else>Square preview</span>
                </div>
                <div class="grid gap-2">
                    <div class="flex gap-1 rounded-md bg-muted p-1">
                        <Button
                            type="button"
                            :variant="
                                photoMode === 'upload' ? 'secondary' : 'ghost'
                            "
                            size="sm"
                            class="flex-1"
                            @click="switchPhotoMode('upload')"
                        >
                            Upload
                        </Button>
                        <Button
                            type="button"
                            :variant="
                                photoMode === 'url' ? 'secondary' : 'ghost'
                            "
                            size="sm"
                            class="flex-1"
                            @click="switchPhotoMode('url')"
                        >
                            URL
                        </Button>
                    </div>

                    <template v-if="photoMode === 'upload'">
                        <Label for="photo">Upload square photo</Label>
                        <input
                            id="photo"
                            name="photo"
                            type="file"
                            accept="image/*"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none selection:bg-primary selection:text-primary-foreground file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
                            @change="handlePhotoChange"
                        />
                        <p class="text-sm text-muted-foreground">
                            Use a square photo up to 2048x2048 pixels.
                        </p>
                        <InputError :message="errors.photo" />
                    </template>

                    <template v-else>
                        <Label for="photo_url">Image URL</Label>
                        <Input
                            id="photo_url"
                            name="photo_url"
                            type="url"
                            placeholder="https://example.com/photo.jpg"
                            v-model="photoUrlInput"
                            @input="handlePhotoUrlInput"
                        />
                        <p class="text-sm text-muted-foreground">
                            Paste a direct link to an image. It will be
                            downloaded and cropped to a square.
                        </p>
                        <InputError :message="errors.photo_url" />
                    </template>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Timing</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="prep_time_minutes">Prep time (minutes)</Label>
                    <Input
                        id="prep_time_minutes"
                        name="prep_time_minutes"
                        type="number"
                        min="0"
                        :default-value="recipe?.prep_time_minutes ?? ''"
                    />
                    <InputError :message="errors.prep_time_minutes" />
                </div>

                <div class="grid gap-2">
                    <Label for="cook_time_minutes">Cook time (minutes)</Label>
                    <Input
                        id="cook_time_minutes"
                        name="cook_time_minutes"
                        type="number"
                        min="0"
                        :default-value="recipe?.cook_time_minutes ?? ''"
                    />
                    <InputError :message="errors.cook_time_minutes" />
                </div>
            </CardContent>
        </Card>

        <!-- ═══ Flat ingredients (no sections) ═══ -->
        <Card v-if="!useSections">
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Ingredients</CardTitle>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="unmatchedIngredients.length > 0"
                        type="button"
                        variant="default"
                        size="sm"
                        @click="showResolutionModal = true"
                    >
                        Resolve {{ unmatchedIngredients.length }} unmatched
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="enableSections"
                    >
                        Use sections
                    </Button>
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        @click="addIngredientRow"
                    >
                        Add ingredient
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-if="ingredientRows.length === 0"
                    class="rounded-lg border border-dashed border-border p-6 text-sm text-muted-foreground"
                >
                    Add ingredients as you need them. You can always come back
                    later.
                </div>
                <div
                    v-for="(row, index) in ingredientRows"
                    :key="`ingredient-${index}`"
                    class="grid gap-4 rounded-lg border border-border/70 p-4 md:grid-cols-[2fr_1fr_1fr_1fr_auto]"
                >
                    <div class="grid gap-2">
                        <Label :for="`ingredient-${index}`">Ingredient</Label>
                        <Combobox
                            v-model="row.ingredient_id"
                            :options="localIngredients"
                            :name="`ingredients[${index}][ingredient_id]`"
                            :placeholder="
                                row.importedName
                                    ? `${row.importedName} (unmatched)`
                                    : 'Select or create ingredient...'
                            "
                            :allow-create="true"
                            create-label="Create"
                            @create="
                                (name: string) =>
                                    openIngredientModal(name, index)
                            "
                        />
                        <p
                            v-if="row.importedName && row.ingredient_id === ''"
                            class="text-xs text-muted-foreground"
                        >
                            Imported as "{{ row.importedName }}" — select or
                            create a matching ingredient
                        </p>
                        <InputError
                            :message="
                                errors[`ingredients.${index}.ingredient_id`]
                            "
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`quantity-${index}`">Qty</Label>
                        <Input
                            :id="`quantity-${index}`"
                            :name="`ingredients[${index}][quantity]`"
                            placeholder="1/2"
                            v-model="row.quantity"
                        />
                        <InputError
                            :message="errors[`ingredients.${index}.quantity`]"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`unit-${index}`">Unit</Label>
                        <Input
                            :id="`unit-${index}`"
                            :name="`ingredients[${index}][unit]`"
                            placeholder="cups"
                            v-model="row.unit"
                        />
                        <InputError
                            :message="errors[`ingredients.${index}.unit`]"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`note-${index}`">Note</Label>
                        <Input
                            :id="`note-${index}`"
                            :name="`ingredients[${index}][note]`"
                            placeholder="divided"
                            v-model="row.note"
                        />
                        <InputError
                            :message="errors[`ingredients.${index}.note`]"
                        />
                    </div>

                    <div class="flex items-end">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            @click="removeIngredientRow(index)"
                            :aria-label="`Remove ingredient row ${index + 1}`"
                        >
                            &times;
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- ═══ Flat instructions (no sections) ═══ -->
        <Card v-if="!useSections">
            <CardHeader>
                <CardTitle>Instructions</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-2">
                <Label for="instructions">Step-by-step directions</Label>
                <input
                    type="hidden"
                    name="instructions"
                    :value="instructionsContent"
                />
                <RichTextEditor
                    v-model="instructionsContent"
                    placeholder="Write clear steps so you can cook without thinking."
                />
                <InputError :message="errors.instructions" />
            </CardContent>
        </Card>

        <!-- ═══ Sections mode ═══ -->
        <template v-if="useSections">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>Recipe sections</CardTitle>
                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="showRemoveSectionsDialog = true"
                        >
                            Remove sections
                        </Button>
                        <Dialog v-model:open="showRemoveSectionsDialog">
                            <DialogContent>
                                <DialogHeader class="space-y-3">
                                    <DialogTitle>Remove sections?</DialogTitle>
                                    <DialogDescription>
                                        Ingredients will be moved to a single
                                        flat list. Section instructions will be
                                        merged into the main instructions with
                                        section names as headers.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button
                                            data-test="cancel-remove-sections"
                                            variant="secondary"
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        data-test="confirm-remove-sections"
                                        variant="destructive"
                                        @click="
                                            disableSections();
                                            showRemoveSectionsDialog = false;
                                        "
                                    >
                                        Remove sections
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            @click="addSection"
                        >
                            Add section
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        Group ingredients and instructions by component (e.g.,
                        "For the Crust", "For the Filling").
                    </p>
                    <InputError :message="errors.sections" />
                </CardContent>
            </Card>

            <Card
                v-for="(section, sIdx) in sectionRows"
                :key="`section-${sIdx}`"
            >
                <CardHeader class="flex flex-row items-center justify-between">
                    <div class="grid grow gap-2">
                        <Label :for="`section-name-${sIdx}`"
                            >Section name</Label
                        >
                        <Input
                            :id="`section-name-${sIdx}`"
                            :name="`sections[${sIdx}][name]`"
                            placeholder="For the crust"
                            v-model="section.name"
                            required
                        />
                        <InputError
                            :message="errors[`sections.${sIdx}.name`]"
                        />
                    </div>
                    <div class="ml-4 flex shrink-0 items-end">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            @click="removeSection(sIdx)"
                            :aria-label="`Remove section ${sIdx + 1}`"
                        >
                            &times;
                        </Button>
                    </div>
                    <input
                        type="hidden"
                        :name="`sections[${sIdx}][sort_order]`"
                        :value="section.sort_order"
                    />
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Section ingredients -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <Label class="text-base">Ingredients</Label>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                @click="addSectionIngredient(sIdx)"
                            >
                                Add ingredient
                            </Button>
                        </div>

                        <div
                            v-if="section.ingredients.length === 0"
                            class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
                        >
                            No ingredients in this section yet.
                        </div>

                        <div
                            v-for="(row, iIdx) in section.ingredients"
                            :key="`s${sIdx}-i${iIdx}`"
                            class="grid gap-4 rounded-lg border border-border/70 p-4 md:grid-cols-[2fr_1fr_1fr_1fr_auto]"
                        >
                            <div class="grid gap-2">
                                <Label :for="`s${sIdx}-ingredient-${iIdx}`"
                                    >Ingredient</Label
                                >
                                <Combobox
                                    v-model="row.ingredient_id"
                                    :options="localIngredients"
                                    :name="`sections[${sIdx}][ingredients][${iIdx}][ingredient_id]`"
                                    placeholder="Select or create ingredient..."
                                    :allow-create="true"
                                    create-label="Create"
                                    @create="
                                        (name: string) =>
                                            openSectionIngredientModal(
                                                name,
                                                sIdx,
                                                iIdx,
                                            )
                                    "
                                />
                                <InputError
                                    :message="
                                        errors[
                                            `sections.${sIdx}.ingredients.${iIdx}.ingredient_id`
                                        ]
                                    "
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`s${sIdx}-qty-${iIdx}`">Qty</Label>
                                <Input
                                    :id="`s${sIdx}-qty-${iIdx}`"
                                    :name="`sections[${sIdx}][ingredients][${iIdx}][quantity]`"
                                    placeholder="1/2"
                                    v-model="row.quantity"
                                />
                                <InputError
                                    :message="
                                        errors[
                                            `sections.${sIdx}.ingredients.${iIdx}.quantity`
                                        ]
                                    "
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`s${sIdx}-unit-${iIdx}`"
                                    >Unit</Label
                                >
                                <Input
                                    :id="`s${sIdx}-unit-${iIdx}`"
                                    :name="`sections[${sIdx}][ingredients][${iIdx}][unit]`"
                                    placeholder="cups"
                                    v-model="row.unit"
                                />
                                <InputError
                                    :message="
                                        errors[
                                            `sections.${sIdx}.ingredients.${iIdx}.unit`
                                        ]
                                    "
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`s${sIdx}-note-${iIdx}`"
                                    >Note</Label
                                >
                                <Input
                                    :id="`s${sIdx}-note-${iIdx}`"
                                    :name="`sections[${sIdx}][ingredients][${iIdx}][note]`"
                                    placeholder="divided"
                                    v-model="row.note"
                                />
                                <InputError
                                    :message="
                                        errors[
                                            `sections.${sIdx}.ingredients.${iIdx}.note`
                                        ]
                                    "
                                />
                            </div>

                            <div class="flex items-end">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    @click="removeSectionIngredient(sIdx, iIdx)"
                                    :aria-label="`Remove ingredient ${iIdx + 1} from section ${sIdx + 1}`"
                                >
                                    &times;
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Section instructions -->
                    <div class="grid gap-2">
                        <Label :for="`section-instructions-${sIdx}`"
                            >Instructions</Label
                        >
                        <input
                            type="hidden"
                            :name="`sections[${sIdx}][instructions]`"
                            :value="section.instructions"
                        />
                        <RichTextEditor
                            v-model="section.instructions"
                            placeholder="Instructions for this section..."
                        />
                        <InputError
                            :message="errors[`sections.${sIdx}.instructions`]"
                        />
                    </div>
                </CardContent>
            </Card>
        </template>

        <!-- Ingredient Creation Modal -->
        <Dialog v-model:open="showIngredientModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Create ingredient</DialogTitle>
                    <DialogDescription>
                        Add a new ingredient and optionally assign it to a
                        store.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="grid gap-2">
                        <Label for="ingredient-name">Ingredient name</Label>
                        <Input
                            id="ingredient-name"
                            v-model="newIngredientName"
                            placeholder="Extra virgin olive oil"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label>Grocery store (optional)</Label>
                        <Combobox
                            v-model="newIngredientStoreId"
                            :options="storeOptions"
                            placeholder="Select or create a store..."
                            allow-create
                            create-label="Create store"
                            @create="openStoreModal"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label>Store section (optional)</Label>
                        <Combobox
                            v-model="newIngredientSectionId"
                            :options="sectionOptions"
                            placeholder="Select or create a section..."
                            :disabled="!newIngredientStoreId"
                            allow-create
                            create-label="Create section"
                            @create="openSectionModal"
                        />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        @click="createIngredient"
                        :disabled="
                            ingredientModalLoading || !newIngredientName.trim()
                        "
                    >
                        Create ingredient
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Store & Section Creation Modals -->
        <StoreCreationModal
            v-model:open="showStoreModal"
            :prefill-name="prefillStoreName"
            @store-created="handleStoreCreated"
        />

        <SectionCreationModal
            v-model:open="showSectionModal"
            :store-id="newIngredientStoreId"
            :prefill-name="prefillSectionName"
            @section-created="handleSectionCreated"
        />

        <IngredientResolutionModal
            v-model:open="showResolutionModal"
            :unmatched-ingredients="unmatchedIngredients"
            :existing-ingredients="localIngredients"
            :grocery-stores="localStores"
            @resolved="handleIngredientsResolved"
        />
    </div>
</template>
