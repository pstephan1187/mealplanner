import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import { update } from '@/actions/App/Http/Controllers/Settings/AppearanceController';
import { updateFavicon } from '@/composables/useDynamicFavicon';

export type Theme = 'default' | 'blush-pink';

export function updateThemeClass(value: Theme) {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.remove('theme-blush-pink');

    if (value === 'blush-pink') {
        document.documentElement.classList.add('theme-blush-pink');
    }
}

const theme = ref<Theme>('default');

export function useTheme() {
    const page = usePage();

    onMounted(() => {
        const serverTheme = (page.props.currentTheme as Theme) ?? 'default';
        theme.value = serverTheme;
        updateThemeClass(serverTheme);
        updateFavicon();
    });

    function updateTheme(newTheme: Theme) {
        theme.value = newTheme;
        updateThemeClass(newTheme);
        updateFavicon();

        router.patch(
            update().url,
            { theme: newTheme },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    }

    return {
        theme: computed(() => theme.value),
        updateTheme,
    };
}
