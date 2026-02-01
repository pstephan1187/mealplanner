<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    links?: PaginationLink[];
}>();

/**
 * Decodes HTML entities safely without using innerHTML/v-html.
 * Laravel pagination uses &laquo; and &raquo; for navigation arrows.
 */
function decodeHtmlEntities(text: string): string {
    return text
        .replace(/&laquo;/g, '\u00AB')
        .replace(/&raquo;/g, '\u00BB')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&amp;/g, '&')
        .replace(/&nbsp;/g, '\u00A0');
}

const decodedLinks = computed(() => {
    return (props.links ?? []).map((link) => ({
        ...link,
        decodedLabel: decodeHtmlEntities(link.label),
    }));
});

const shouldShow = computed(() => {
    return props.links && props.links.length > 1;
});
</script>

<template>
    <nav v-if="shouldShow" class="flex flex-wrap gap-2" aria-label="Pagination">
        <template v-for="link in decodedLinks" :key="link.label">
            <Link
                v-if="link.url"
                :href="link.url"
                class="rounded-md border border-border px-3 py-1 text-sm transition hover:bg-muted"
                :class="{ 'bg-muted font-medium': link.active }"
                :aria-current="link.active ? 'page' : undefined"
            >
                {{ link.decodedLabel }}
            </Link>
            <span
                v-else
                class="rounded-md border border-border px-3 py-1 text-sm text-muted-foreground"
                aria-disabled="true"
            >
                {{ link.decodedLabel }}
            </span>
        </template>
    </nav>
</template>
