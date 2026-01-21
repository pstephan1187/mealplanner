<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, ref, watch } from 'vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxViewport,
} from 'reka-ui';
import { Check, ChevronsUpDown, Plus } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

export interface ComboboxOption {
    id: number | string;
    name: string;
}

const props = withDefaults(
    defineProps<{
        modelValue?: number | string | '';
        options: ComboboxOption[];
        placeholder?: string;
        name?: string;
        class?: HTMLAttributes['class'];
        allowCreate?: boolean;
        createLabel?: string;
        disabled?: boolean;
    }>(),
    {
        placeholder: 'Select option...',
        allowCreate: false,
        createLabel: 'Create',
        disabled: false,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | string | ''): void;
    (e: 'create', name: string): void;
}>();

const open = ref(false);
const searchTerm = ref('');

const selectedOption = computed(() => {
    if (props.modelValue === '' || props.modelValue === undefined) {
        return null;
    }
    return props.options.find((opt) => opt.id === props.modelValue) ?? null;
});

const filteredOptions = computed(() => {
    if (!searchTerm.value) {
        return props.options;
    }
    const search = searchTerm.value.toLowerCase();
    return props.options.filter((opt) =>
        opt.name.toLowerCase().includes(search),
    );
});

const showCreateOption = computed(() => {
    if (!props.allowCreate || !searchTerm.value.trim()) {
        return false;
    }
    const search = searchTerm.value.toLowerCase().trim();
    return !props.options.some((opt) => opt.name.toLowerCase() === search);
});

const displayValue = computed(() => {
    if (selectedOption.value) {
        return selectedOption.value.name;
    }
    return '';
});

// The input value: show searchTerm when open, show selected option name when closed
const inputValue = computed({
    get() {
        if (open.value) {
            return searchTerm.value;
        }
        return displayValue.value;
    },
    set(value: string) {
        searchTerm.value = value;
    },
});

watch(open, (isOpen) => {
    if (!isOpen) {
        searchTerm.value = '';
    }
});

function handleSelect(option: ComboboxOption) {
    emit('update:modelValue', option.id);
    open.value = false;
    searchTerm.value = '';
}

function handleCreate() {
    const name = searchTerm.value.trim();
    if (name) {
        emit('create', name);
        open.value = false;
        searchTerm.value = '';
    }
}
</script>

<template>
    <ComboboxRoot
        v-model:open="open"
        :ignore-filter="true"
        :disabled="disabled"
    >
        <input
            v-if="name"
            type="hidden"
            :name="name"
            :value="modelValue"
        />

        <ComboboxAnchor
            :class="
                cn(
                    'border-input dark:bg-input/30 flex h-9 w-full items-center justify-between rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none',
                    'focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]',
                    disabled && 'cursor-not-allowed opacity-50',
                    props.class,
                )
            "
        >
            <ComboboxInput
                v-model="inputValue"
                :placeholder="selectedOption ? '' : placeholder"
                class="h-full flex-1 bg-transparent outline-none placeholder:text-muted-foreground"
            />
            <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
        </ComboboxAnchor>

        <ComboboxPortal>
            <ComboboxContent
                position="popper"
                :side-offset="4"
                :class="
                    cn(
                        'bg-popover text-popover-foreground z-50 max-h-[300px] min-w-[var(--reka-combobox-trigger-width)] overflow-hidden rounded-md border shadow-md',
                        'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
                        'data-[side=bottom]:slide-in-from-top-2 data-[side=top]:slide-in-from-bottom-2',
                    )
                "
            >
                <ComboboxViewport class="p-1">
                    <ComboboxItem
                        v-for="option in filteredOptions"
                        :key="option.id"
                        :value="option"
                        :class="
                            cn(
                                'relative flex cursor-default select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none',
                                'data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground',
                                'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                            )
                        "
                        @select.prevent="handleSelect(option)"
                    >
                        <Check
                            :class="
                                cn(
                                    'size-4',
                                    modelValue === option.id
                                        ? 'opacity-100'
                                        : 'opacity-0',
                                )
                            "
                        />
                        <span>{{ option.name }}</span>
                    </ComboboxItem>

                    <ComboboxItem
                        v-if="showCreateOption"
                        :value="{ id: '__create__', name: searchTerm.trim() }"
                        :class="
                            cn(
                                'relative flex cursor-default select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none',
                                'data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground',
                                'text-primary font-medium',
                            )
                        "
                        @select.prevent="handleCreate"
                    >
                        <Plus class="size-4" />
                        <span>{{ createLabel }} "{{ searchTerm.trim() }}"</span>
                    </ComboboxItem>

                    <ComboboxEmpty
                        v-if="filteredOptions.length === 0 && !showCreateOption"
                        class="px-2 py-6 text-center text-sm text-muted-foreground"
                    >
                        No results found.
                    </ComboboxEmpty>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>
