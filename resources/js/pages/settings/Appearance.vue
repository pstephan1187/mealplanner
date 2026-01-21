<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import ThemePicker from '@/components/ThemePicker.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';
import { type BreadcrumbItem } from '@/types';

defineProps<{
    currentTheme: 'default' | 'blush-pink';
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <h1 class="sr-only">Appearance Settings</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Appearance settings"
                    description="Update your account's appearance settings"
                />

                <div class="space-y-8">
                    <div class="space-y-4">
                        <h3 class="text-sm font-medium">Theme</h3>
                        <ThemePicker />
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-sm font-medium">Mode</h3>
                        <AppearanceTabs />
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
