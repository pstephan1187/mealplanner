<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import { List, ListOrdered, MessageSquareQuote, Minus, Link2, ImagePlus } from 'lucide-vue-next';
import { onBeforeUnmount, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        placeholder?: string;
        imageMaxWidth?: string;
    }>(),
    {
        imageMaxWidth: '300px',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
        Underline,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
        }),
        Image.configure({ inline: false }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[12rem] px-4 py-3',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.modelValue, (value) => {
    if (editor.value && value !== editor.value.getHTML()) {
        editor.value.commands.setContent(value || '');
    }
});

const addImage = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = async () => {
        const file = input.files?.[0];
        if (!file || !editor.value) return;

        const formData = new FormData();
        formData.append('image', file);

        const response = await fetch('/uploads/images', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find((c) => c.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] ?? '',
                ),
            },
            body: formData,
        });

        if (response.ok) {
            const { url } = await response.json();
            editor.value.chain().focus().setImage({ src: url }).run();
        }
    };
    input.click();
};

const setLink = () => {
    if (!editor.value) return;

    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('URL', previousUrl);

    if (url === null) return;

    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div
        v-if="editor"
        class="rounded-md border border-input bg-transparent shadow-xs transition-[color,box-shadow] has-[:focus]:border-ring has-[:focus]:ring-ring/50 has-[:focus]:ring-[3px] dark:bg-input/30"
    >
        <div class="flex flex-wrap gap-0.5 border-b border-border px-2 py-1.5">
            <button
                v-for="action in [
                    { command: () => editor!.chain().focus().toggleBold().run(), active: editor.isActive('bold'), label: 'B', title: 'Bold', class: 'font-bold' },
                    { command: () => editor!.chain().focus().toggleItalic().run(), active: editor.isActive('italic'), label: 'I', title: 'Italic', class: 'italic' },
                    { command: () => editor!.chain().focus().toggleUnderline().run(), active: editor.isActive('underline'), label: 'U', title: 'Underline', class: 'underline' },
                    { command: () => editor!.chain().focus().toggleStrike().run(), active: editor.isActive('strike'), label: 'S', title: 'Strikethrough', class: 'line-through' },
                ]"
                :key="action.title"
                type="button"
                :title="action.title"
                :class="[
                    'flex size-8 items-center justify-center rounded text-sm transition-colors',
                    action.active ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                    action.class,
                ]"
                @click="action.command()"
            >
                {{ action.label }}
            </button>

            <div class="mx-1 w-px self-stretch bg-border" />

            <button
                v-for="heading in [
                    { level: 2 as const, label: 'H2' },
                    { level: 3 as const, label: 'H3' },
                ]"
                :key="heading.label"
                type="button"
                :title="`Heading ${heading.level}`"
                :class="[
                    'flex size-8 items-center justify-center rounded text-xs font-semibold transition-colors',
                    editor.isActive('heading', { level: heading.level }) ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                ]"
                @click="editor!.chain().focus().toggleHeading({ level: heading.level }).run()"
            >
                {{ heading.label }}
            </button>

            <div class="mx-1 w-px self-stretch bg-border" />

            <button
                type="button"
                title="Bullet list"
                :class="[
                    'flex size-8 items-center justify-center rounded transition-colors',
                    editor.isActive('bulletList') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                ]"
                @click="editor!.chain().focus().toggleBulletList().run()"
            >
                <List class="size-4" />
            </button>

            <button
                type="button"
                title="Ordered list"
                :class="[
                    'flex size-8 items-center justify-center rounded transition-colors',
                    editor.isActive('orderedList') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                ]"
                @click="editor!.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="size-4" />
            </button>

            <button
                type="button"
                title="Blockquote"
                :class="[
                    'flex size-8 items-center justify-center rounded transition-colors',
                    editor.isActive('blockquote') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                ]"
                @click="editor!.chain().focus().toggleBlockquote().run()"
            >
                <MessageSquareQuote class="size-4" />
            </button>

            <div class="mx-1 w-px self-stretch bg-border" />

            <button
                type="button"
                title="Horizontal rule"
                class="flex size-8 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-accent/50 hover:text-foreground"
                @click="editor!.chain().focus().setHorizontalRule().run()"
            >
                <Minus class="size-4" />
            </button>

            <button
                type="button"
                title="Link"
                :class="[
                    'flex size-8 items-center justify-center rounded transition-colors',
                    editor.isActive('link') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                ]"
                @click="setLink()"
            >
                <Link2 class="size-4" />
            </button>

            <button
                type="button"
                title="Image"
                class="flex size-8 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-accent/50 hover:text-foreground"
                @click="addImage()"
            >
                <ImagePlus class="size-4" />
            </button>
        </div>

        <EditorContent :editor="editor" :style="{ '--image-max-width': props.imageMaxWidth }" />
    </div>
</template>

<style scoped>
:deep(.ProseMirror img) {
    display: block;
    width: 100%;
    max-width: var(--image-max-width, 300px);
    height: auto;
    margin-inline: auto;
    border-radius: 0.375rem;
    cursor: pointer;
}

:deep(.ProseMirror img.ProseMirror-selectednode) {
    outline: 1px solid var(--color-ring);
    outline-offset: -1px;
    box-shadow: 0 0 0 3px color-mix(in oklch, var(--color-ring) 50%, transparent);
}
</style>
