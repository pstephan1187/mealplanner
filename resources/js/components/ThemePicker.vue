<script setup lang="ts">
import { useTheme, type Theme } from '@/composables/useTheme';

const { theme, updateTheme } = useTheme();

interface ThemeOption {
    value: Theme;
    label: string;
    description: string;
    colors: string[];
}

const themes: ThemeOption[] = [
    {
        value: 'default',
        label: 'Default',
        description: 'Clean and minimal',
        colors: ['bg-neutral-900', 'bg-neutral-400', 'bg-neutral-200'],
    },
    {
        value: 'blush-pink',
        label: 'Blush Pink',
        description: 'Soft pinks and golden yellows',
        colors: ['bg-rose-400', 'bg-amber-300', 'bg-rose-200'],
    },
];
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <button
            v-for="option in themes"
            :key="option.value"
            type="button"
            @click="updateTheme(option.value)"
            :class="[
                'rounded-lg border-2 p-4 text-left transition-all',
                theme === option.value
                    ? 'border-primary ring-2 ring-primary/20'
                    : 'border-border hover:border-primary/50',
            ]"
        >
            <div class="mb-3 flex gap-2">
                <div
                    v-for="(colorClass, index) in option.colors"
                    :key="index"
                    :class="['size-6 rounded-full', colorClass]"
                />
            </div>
            <div class="font-medium">{{ option.label }}</div>
            <div class="text-sm text-muted-foreground">
                {{ option.description }}
            </div>
        </button>
    </div>
</template>
