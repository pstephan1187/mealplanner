<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { toggleItem } from '@/actions/App/Http/Controllers/SharedShoppingListController';
import type { DisplayMode } from '@/composables/useShoppingListSorting';
import { useShoppingListSorting } from '@/composables/useShoppingListSorting';
import { resolveResource, type ResourceProp } from '@/lib/utils';
import type { ShoppingList, ShoppingListItem } from '@/types/models';

const props = defineProps<{
    shoppingList: ResourceProp<ShoppingList>;
    shareToken: string;
}>();

const shoppingList = computed(() => resolveResource(props.shoppingList));
const items = computed(() => shoppingList.value.items ?? []);

const displayMode = ref<DisplayMode>(
    shoppingList.value.display_mode ?? 'alphabetical',
);

const { sortedItems, groupedByStore } = useShoppingListSorting({
    items,
    displayMode,
});

const title = computed(
    () => shoppingList.value.meal_plan?.name ?? 'Shopping List',
);

const purchasedCount = computed(
    () => items.value.filter((item) => item.is_purchased).length,
);

const totalCount = computed(() => items.value.length);

const togglePurchased = (item: ShoppingListItem) => {
    router.patch(
        toggleItem({ shareToken: props.shareToken, shoppingListItem: item.id }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};
</script>

<template>
    <Head :title="title" />

    <div class="mx-auto min-h-screen max-w-2xl bg-white px-4 py-8 sm:px-6">
        <header class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ purchasedCount }} of {{ totalCount }}
                {{ totalCount === 1 ? 'item' : 'items' }} purchased
            </p>
        </header>

        <div class="mb-4">
            <select
                v-model="displayMode"
                class="h-9 rounded-md border border-gray-300 bg-white px-3 text-sm shadow-xs outline-none focus-visible:border-blue-500 focus-visible:ring-[3px] focus-visible:ring-blue-500/20"
            >
                <option value="alphabetical">Alphabetical</option>
                <option value="store">By store</option>
            </select>
        </div>

        <!-- Store grouped view -->
        <template v-if="displayMode === 'store'">
            <div
                v-for="storeGroup in groupedByStore"
                :key="storeGroup.storeName"
                class="mb-6"
            >
                <h2
                    class="mb-2 border-b-2 border-gray-800 pb-1 text-lg font-semibold text-gray-800"
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

                    <ul class="space-y-1">
                        <li
                            v-for="item in section.items"
                            :key="item.id"
                            class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-gray-50"
                            :class="{
                                'opacity-50': item.is_purchased,
                            }"
                            @click="togglePurchased(item)"
                        >
                            <input
                                type="checkbox"
                                :checked="item.is_purchased"
                                class="size-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                @click.stop
                                @change="togglePurchased(item)"
                            />
                            <div class="min-w-0 flex-1">
                                <span
                                    class="font-medium"
                                    :class="{
                                        'line-through': item.is_purchased,
                                    }"
                                >
                                    {{ item.ingredient?.name ?? 'Ingredient' }}
                                </span>
                                <span class="ml-2 text-sm text-gray-500">
                                    {{ item.quantity }} {{ item.unit }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </template>

        <!-- Alphabetical view -->
        <template v-else>
            <ul class="space-y-1">
                <li
                    v-for="item in sortedItems"
                    :key="item.id"
                    class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-gray-50"
                    :class="{
                        'opacity-50': item.is_purchased,
                    }"
                    @click="togglePurchased(item)"
                >
                    <input
                        type="checkbox"
                        :checked="item.is_purchased"
                        class="size-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        @click.stop
                        @change="togglePurchased(item)"
                    />
                    <div class="min-w-0 flex-1">
                        <span
                            class="font-medium"
                            :class="{
                                'line-through': item.is_purchased,
                            }"
                        >
                            {{ item.ingredient?.name ?? 'Ingredient' }}
                        </span>
                        <span class="ml-2 text-sm text-gray-500">
                            {{ item.quantity }} {{ item.unit }}
                        </span>
                    </div>
                </li>
            </ul>
        </template>

        <div
            v-if="totalCount === 0"
            class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500"
        >
            This shopping list has no items yet.
        </div>
    </div>
</template>
