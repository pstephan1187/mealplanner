<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

import PlaceholderPattern from './PlaceholderPattern.vue';

interface Props {
    title: string;
    subtitle?: string;
}

defineProps<Props>();
</script>

<template>
    <Head :title="title" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold text-foreground">
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="text-sm text-muted-foreground">
                    {{ subtitle }}
                </p>
            </div>
            <div
                class="relative min-h-[60vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
