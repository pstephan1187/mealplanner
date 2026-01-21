<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import InputError from '@/components/InputError.vue';
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
import { MEAL_TYPES } from '@/lib/constants';
import { storeQuick } from '@/actions/App/Http/Controllers/IngredientController';

interface IngredientOption {
    id: number;
    name: string;
}

interface GroceryStoreSection {
    id: number;
    name: string;
}

interface GroceryStore {
    id: number;
    name: string;
    sections?: GroceryStoreSection[];
}

interface RecipeIngredientPivot {
    quantity: string | number;
    unit: string;
    note?: string | null;
}

interface RecipeIngredient {
    id: number;
    name: string;
    pivot?: RecipeIngredientPivot | null;
}

interface Recipe {
    id: number;
    name: string;
    instructions: string;
    servings: number;
    flavor_profile: string;
    meal_types?: string[];
    photo_url?: string | null;
    prep_time_minutes?: number | null;
    cook_time_minutes?: number | null;
    ingredients?: RecipeIngredient[];
}

interface IngredientRow {
    ingredient_id: number | '';
    quantity: string;
    unit: string;
    note: string;
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

// Ingredient creation modal state
const showIngredientModal = ref(false);
const ingredientModalRowIndex = ref<number | null>(null);
const newIngredientName = ref('');
const newIngredientStoreId = ref<number | string>('');
const newIngredientSectionId = ref<number | string>('');
const ingredientModalLoading = ref(false);

// Store creation modal state
const showStoreModal = ref(false);
const newStoreName = ref('');
const newStoreSections = ref<string[]>(['']);
const storeModalLoading = ref(false);

// Section creation modal state
const showSectionModal = ref(false);
const newSectionName = ref('');
const sectionModalLoading = ref(false);

const storeOptions = computed(() =>
    localStores.value.map((s) => ({ id: s.id, name: s.name })),
);

const availableSections = computed(() => {
    if (!newIngredientStoreId.value) return [];
    const store = localStores.value.find(
        (s) => s.id === Number(newIngredientStoreId.value),
    );
    return store?.sections ?? [];
});

const sectionOptions = computed(() =>
    availableSections.value.map((s) => ({ id: s.id, name: s.name })),
);

watch(newIngredientStoreId, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        newIngredientSectionId.value = '';
    }
});

const ingredientRows = ref<IngredientRow[]>(
    props.recipe?.ingredients?.length
        ? props.recipe.ingredients.map((ingredient) => ({
              ingredient_id: ingredient.id,
              quantity: ingredient.pivot?.quantity?.toString() ?? '',
              unit: ingredient.pivot?.unit ?? '',
              note: ingredient.pivot?.note ?? '',
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

function getXsrfToken(): string {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );
}

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
        const response = await fetch(storeQuick.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getXsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: newIngredientName.value.trim(),
                grocery_store_id: newIngredientStoreId.value || null,
                grocery_store_section_id: newIngredientSectionId.value || null,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json();
            console.error('Failed to create ingredient:', errorData);
            return;
        }

        const data = await response.json();
        const newIngredient = data.ingredient;

        localIngredients.value.push({
            id: newIngredient.id,
            name: newIngredient.name,
        });

        localIngredients.value.sort((a, b) => a.name.localeCompare(b.name));

        if (ingredientModalRowIndex.value !== null) {
            ingredientRows.value[ingredientModalRowIndex.value].ingredient_id =
                newIngredient.id;
        }

        showIngredientModal.value = false;
    } catch (error) {
        console.error('Error creating ingredient:', error);
    } finally {
        ingredientModalLoading.value = false;
    }
}

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
                Accept: 'application/json',
                'X-XSRF-TOKEN': getXsrfToken(),
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
            newIngredientStoreId.value = newStore.id;
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
    if (!newSectionName.value.trim() || !newIngredientStoreId.value) return;

    sectionModalLoading.value = true;
    try {
        const response = await fetch(
            `/grocery-stores/${newIngredientStoreId.value}/sections/quick`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getXsrfToken(),
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

            localStores.value = localStores.value.map((store) => {
                if (store.id === Number(newIngredientStoreId.value)) {
                    return {
                        ...store,
                        sections: [...(store.sections || []), newSection].sort(
                            (a, b) => a.name.localeCompare(b.name),
                        ),
                    };
                }
                return store;
            });

            newIngredientSectionId.value = newSection.id;
            showSectionModal.value = false;
        }
    } finally {
        sectionModalLoading.value = false;
    }
}

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
                    >
                    <span v-else>Square preview</span>
                </div>
                <div class="grid gap-2">
                    <Label for="photo">Upload square photo</Label>
                    <input
                        id="photo"
                        name="photo"
                        type="file"
                        accept="image/*"
                        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        @change="handlePhotoChange"
                    >
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
                            placeholder="Select or create ingredient..."
                            :allow-create="true"
                            create-label="Create"
                            @create="(name) => openIngredientModal(name, index)"
                        />
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
                            type="number"
                            min="0.01"
                            step="0.01"
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
                        Add a new ingredient and optionally assign it to a store.
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
                        :disabled="ingredientModalLoading || !newIngredientName.trim()"
                    >
                        Create ingredient
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
    </div>
</template>
