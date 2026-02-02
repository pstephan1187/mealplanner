<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';

import InputError from '@/components/InputError.vue';
import SectionCreationModal from '@/components/SectionCreationModal.vue';
import StoreCreationModal from '@/components/StoreCreationModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Textarea } from '@/components/ui/textarea';
import { useRecipeIngredientModals } from '@/composables/useRecipeIngredientModals';
import { MEAL_TYPES } from '@/lib/constants';
import type { GroceryStore, Ingredient, Recipe } from '@/types/models';

type IngredientOption = Pick<Ingredient, 'id' | 'name'>;

interface IngredientRow {
    ingredient_id: number | '';
    quantity: string;
    unit: string;
    note: string;
    importedName?: string;
}

interface Props {
    recipe?: Recipe | null;
    ingredients: IngredientOption[];
    groceryStores: GroceryStore[];
    errors: Record<string, string | undefined>;
}

const props = defineProps<Props>();

const selectedMealTypes = computed(() => props.recipe?.meal_types ?? []);

const localIngredients = ref<ComboboxOption[]>([...props.ingredients]);
const localStores = ref<GroceryStore[]>([...props.groceryStores]);

// Ingredient creation modal + store/section selection (via composable)
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
            ingredientRows.value[rowIndex].ingredient_id = ingredient.id;
        }
    },
});

const ingredientRows = ref<IngredientRow[]>(
    props.recipe?.ingredients?.length
        ? props.recipe.ingredients.map((ingredient) => ({
              ingredient_id: ingredient.id || '',
              quantity: ingredient.pivot?.quantity?.toString() ?? '',
              unit: ingredient.pivot?.unit ?? '',
              note: ingredient.pivot?.note ?? '',
              importedName:
                  !ingredient.id && ingredient.name ? ingredient.name : undefined,
          }))
        : [],
);

const photoPreview = ref<string | null>(props.recipe?.photo_url ?? null);
let photoObjectUrl: string | null = null;

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
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Meal details</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-6">
                <div class="grid gap-2">
                    <Label>Meal types</Label>
                    <div class="flex flex-wrap gap-4">
                        <Label
                            v-for="mealType in MEAL_TYPES"
                            :key="mealType"
                            class="flex items-center gap-2 text-sm font-normal"
                        >
                            <Checkbox
                                :id="`meal-type-${mealType}`"
                                name="meal_types[]"
                                :value="mealType"
                                :default-value="
                                    selectedMealTypes.includes(mealType)
                                "
                                :data-test="`meal-type-${mealType.toLowerCase()}`"
                            />
                            <span>{{ mealType }}</span>
                        </Label>
                    </div>
                    <InputError :message="errors.meal_types" />
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="prep_time_minutes"
                            >Prep time (minutes)</Label
                        >
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
                        <Label for="cook_time_minutes"
                            >Cook time (minutes)</Label
                        >
                        <Input
                            id="cook_time_minutes"
                            name="cook_time_minutes"
                            type="number"
                            min="0"
                            :default-value="recipe?.cook_time_minutes ?? ''"
                        />
                        <InputError :message="errors.cook_time_minutes" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Ingredients</CardTitle>
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    @click="addIngredientRow"
                >
                    Add ingredient
                </Button>
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
                            @create="(name) => openIngredientModal(name, index)"
                        />
                        <p
                            v-if="
                                row.importedName && row.ingredient_id === ''
                            "
                            class="text-xs text-muted-foreground"
                        >
                            Imported as "{{ row.importedName }}" — select
                            or create a matching ingredient
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

        <Card>
            <CardHeader>
                <CardTitle>Instructions</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-2">
                <Label for="instructions">Step-by-step directions</Label>
                <Textarea
                    id="instructions"
                    name="instructions"
                    rows="8"
                    placeholder="Write clear steps so you can cook without thinking."
                    :default-value="recipe?.instructions"
                    required
                />
                <InputError :message="errors.instructions" />
            </CardContent>
        </Card>

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
    </div>
</template>
