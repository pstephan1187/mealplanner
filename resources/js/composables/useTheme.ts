import { computed, onMounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { update } from '@/actions/App/Http/Controllers/Settings/AppearanceController';

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
    });

    function updateTheme(newTheme: Theme) {
        theme.value = newTheme;
        updateThemeClass(newTheme);

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
