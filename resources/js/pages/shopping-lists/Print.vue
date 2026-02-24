<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

import type { DisplayMode } from '@/composables/useShoppingListSorting';
import { useShoppingListSorting } from '@/composables/useShoppingListSorting';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import type { ShoppingList } from '@/types/models';

const props = defineProps<{
    shoppingList: ResourceProp<ShoppingList>;
    displayMode: DisplayMode;
}>();

const shoppingList = computed(() => resolveResource(props.shoppingList));
const items = computed(() => shoppingList.value.items ?? []);

const displayMode = computed(() => props.displayMode);

const { sortedItems, groupedByStore } = useShoppingListSorting({
    items,
    displayMode,
});

const unpurchasedItems = computed(() =>
    sortedItems.value.filter((item) => !item.is_purchased),
);

const title = computed(
    () => shoppingList.value.meal_plan?.name ?? 'Shopping List',
);

const displayModeLabel = computed(() => {
    const labels: Record<DisplayMode, string> = {
        manual: 'Custom order',
        alphabetical: 'Alphabetical',
        store: 'By store',
    };

    return labels[props.displayMode];
});

onMounted(() => {
    window.print();
});
</script>

<template>
    <Head :title="`Print - ${title}`" />

    <div class="print-page">
        <header class="mb-8 border-b-2 border-gray-800 pb-4">
            <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ displayModeLabel }} &middot;
                {{ unpurchasedItems.length }}
                {{ unpurchasedItems.length === 1 ? 'item' : 'items' }}
            </p>
        </header>

        <!-- Store grouped view -->
        <template v-if="displayMode === 'store'">
            <div
                v-for="storeGroup in groupedByStore"
                :key="storeGroup.storeName"
                class="mb-6"
            >
                <h2
                    class="mb-2 border-b border-gray-400 pb-1 text-lg font-semibold text-gray-800"
                >
                    {{ storeGroup.storeName }}
                </h2>

                <div
                    v-for="section in storeGroup.sections"
                    :key="`${storeGroup.storeName}-${section.sectionName}`"
                    class="mb-3"
                >
                    <h3
                        v-if="section.sectionName"
                        class="mb-1 text-sm font-medium text-gray-500 uppercase"
                    >
                        {{ section.sectionName }}
                    </h3>

                    <ul class="columns-2 gap-x-8">
                        <li
                            v-for="item in section.items"
                            :key="item.id"
                            class="flex items-baseline gap-2 break-inside-avoid py-0.5"
                            :class="{
                                'text-gray-400 line-through': item.is_purchased,
                            }"
                        >
                            <span
                                class="inline-block size-3.5 shrink-0 rounded-sm border border-gray-400"
                            />
                            <span class="font-medium">
                                {{ item.ingredient?.name ?? 'Ingredient' }}
                            </span>
                            <span class="text-sm text-gray-500">
                                {{ item.quantity }} {{ item.unit }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </template>

        <!-- Manual / Alphabetical view -->
        <template v-else>
            <ul class="columns-2 gap-x-8">
                <li
                    v-for="item in sortedItems"
                    :key="item.id"
                    class="flex items-baseline gap-2 break-inside-avoid py-0.5"
                    :class="{
                        'text-gray-400 line-through': item.is_purchased,
                    }"
                >
                    <span
                        class="inline-block size-3.5 shrink-0 rounded-sm border border-gray-400"
                    />
                    <span class="font-medium">
                        {{ item.ingredient?.name ?? 'Ingredient' }}
                    </span>
                    <span class="text-sm text-gray-500">
                        {{ item.quantity }} {{ item.unit }}
                    </span>
                </li>
            </ul>
        </template>
    </div>
</template>

<style>
@media print {
    @page {
        margin: 0.75in;
        size: auto;
    }

    body {
        background: none !important;
    }

    /* Hide Inertia progress bar and any navigation injected by the app shell */
    #nprogress,
    nav,
    [data-sidebar] {
        display: none !important;
    }
}

/* Screen-only: subtle back link for previewing before printing */
@media screen {
    .print-page {
        font-family:
            ui-sans-serif,
            system-ui,
            -apple-system,
            BlinkMacSystemFont,
            'Segoe UI',
            Roboto,
            'Helvetica Neue',
            Arial,
            sans-serif;

        min-height: 100vh;
        width: 1200px;
        max-width: 100%;
        padding: 4rem;
    }
}
</style>
