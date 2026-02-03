# Rich Text Recipe Instructions Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the plain-text instructions textarea with a Tiptap rich text editor supporting bold, italic, underline, lists, headings, blockquotes, horizontal rules, links, and inline images.

**Architecture:** Tiptap (ProseMirror-based) editor produces HTML stored in the existing `longText` column. A dedicated upload endpoint handles instruction images via the `public` disk. Server-side HTML purification via `mews/purifier` prevents XSS. The Show page renders sanitized HTML with `v-html` and scoped prose styles.

**Tech Stack:** Tiptap (Vue 3), mews/purifier (Laravel), Inertia Form component with hidden input sync.

---

### Task 1: Install backend dependency (mews/purifier)

**Files:**
- Modify: `composer.json`

**Step 1: Install the package**

Run:
```bash
composer require mews/purifier
```

**Step 2: Publish the config**

Run:
```bash
php artisan vendor:publish --provider="Mews\Purifier\PurifierServiceProvider"
```

**Step 3: Configure allowed HTML**

Modify `config/purifier.php` — set the `default` config to allow only the tags/attributes Tiptap produces:

```php
'default' => [
    'HTML.Allowed' => 'h1,h2,h3,p,br,strong,em,u,s,ul,ol,li,blockquote,hr,a[href|target|rel],img[src|alt|title]',
    'HTML.TargetBlank' => true,
    'URI.AllowedSchemes' => ['http', 'https'],
    'AutoFormat.RemoveEmpty' => true,
],
```

**Step 4: Commit**

```bash
git add composer.json composer.lock config/purifier.php
git commit -m "chore: install mews/purifier for HTML sanitization"
```

---

### Task 2: Create the image upload endpoint

**Files:**
- Create: `app/Http/Controllers/UploadController.php`
- Create: `app/Http/Requests/StoreImageRequest.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/ImageUploadTest.php`

**Step 1: Write the failing tests**

Create `tests/Feature/ImageUploadTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('uploads an image and returns the url', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/uploads/images', [
        'image' => UploadedFile::fake()->image('step-photo.jpg', 800, 600),
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['url']);

    $url = $response->json('url');
    expect($url)->toStartWith('/storage/recipe-images/');
});

it('rejects non-image files', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/uploads/images', [
        'image' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});

it('rejects images over 2MB', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/uploads/images', [
        'image' => UploadedFile::fake()->image('huge.jpg')->size(3000),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});

it('requires authentication', function () {
    $response = $this->postJson('/uploads/images', [
        'image' => UploadedFile::fake()->image('photo.jpg'),
    ]);

    $response->assertUnauthorized();
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/ImageUploadTest.php`
Expected: FAIL (route/controller don't exist)

**Step 3: Create the form request**

Run: `php artisan make:request StoreImageRequest --no-interaction`

Modify `app/Http/Requests/StoreImageRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:2048'],
        ];
    }
}
```

**Step 4: Create the controller**

Run: `php artisan make:controller UploadController --no-interaction`

Modify `app/Http/Controllers/UploadController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function __invoke(StoreImageRequest $request): JsonResponse
    {
        $path = $request->file('image')->store(
            'recipe-images',
            ['disk' => 'public', 'visibility' => 'public']
        );

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
```

**Step 5: Add the route**

In `routes/web.php`, inside the auth middleware group, add before the recipes resource:

```php
Route::post('uploads/images', UploadController::class)->name('uploads.images');
```

**Step 6: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/ImageUploadTest.php`
Expected: PASS

**Step 7: Commit**

```bash
git add app/Http/Controllers/UploadController.php app/Http/Requests/StoreImageRequest.php routes/web.php tests/Feature/ImageUploadTest.php
git commit -m "feat: add image upload endpoint for recipe instruction images"
```

---

### Task 3: Add HTML sanitization to recipe form requests

**Files:**
- Modify: `app/Http/Requests/Recipes/StoreRecipeRequest.php`
- Modify: `app/Http/Requests/Recipes/UpdateRecipeRequest.php`
- Create: `tests/Feature/RecipeInstructionSanitizationTest.php`

**Step 1: Write the failing tests**

Create `tests/Feature/RecipeInstructionSanitizationTest.php`:

```php
<?php

use App\Models\User;

it('strips script tags from instructions on create', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Test Recipe',
        'instructions' => '<p>Step one</p><script>alert("xss")</script><p>Step two</p>',
        'servings' => 4,
        'flavor_profile' => 'Savory',
        'meal_types' => ['Dinner'],
    ]);

    $recipe = $user->recipes()->latest()->first();
    expect($recipe->instructions)->toBe('<p>Step one</p><p>Step two</p>');
});

it('allows supported HTML tags in instructions', function () {
    $user = User::factory()->create();

    $html = '<h2>Sauce</h2><p><strong>Bold</strong> and <em>italic</em> and <u>underline</u></p><ul><li>Item</li></ul><ol><li>Step</li></ol><blockquote>Tip</blockquote><hr>';

    $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'HTML Recipe',
        'instructions' => $html,
        'servings' => 2,
        'flavor_profile' => 'Sweet',
        'meal_types' => ['Breakfast'],
    ]);

    $recipe = $user->recipes()->latest()->first();
    expect($recipe->instructions)
        ->toContain('<strong>Bold</strong>')
        ->toContain('<em>italic</em>')
        ->toContain('<u>underline</u>')
        ->toContain('<ul>')
        ->toContain('<ol>')
        ->toContain('<blockquote>')
        ->toContain('<h2>');
});

it('strips script tags from instructions on update', function () {
    $user = User::factory()->create();
    $recipe = $user->recipes()->create([
        'name' => 'Old Recipe',
        'instructions' => '<p>Original</p>',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'meal_types' => ['Dinner'],
    ]);

    $this->actingAs($user)->put(route('recipes.update', $recipe), [
        'instructions' => '<p>Updated</p><script>alert("xss")</script>',
    ]);

    expect($recipe->fresh()->instructions)->toBe('<p>Updated</p>');
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/RecipeInstructionSanitizationTest.php`
Expected: FAIL (script tags not stripped)

**Step 3: Add `prepareForValidation` to both form requests**

In `StoreRecipeRequest.php`, add:

```php
use Mews\Purifier\Facades\Purifier;

protected function prepareForValidation(): void
{
    if ($this->has('instructions')) {
        $this->merge([
            'instructions' => Purifier::clean($this->input('instructions')),
        ]);
    }
}
```

Add the same method to `UpdateRecipeRequest.php`.

**Step 4: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/RecipeInstructionSanitizationTest.php`
Expected: PASS

**Step 5: Run existing recipe tests to make sure nothing broke**

Run: `php artisan test --compact tests/Feature/RecipeControllerTest.php tests/Feature/RecipeFractionTest.php`
Expected: PASS

**Step 6: Commit**

```bash
git add app/Http/Requests/Recipes/StoreRecipeRequest.php app/Http/Requests/Recipes/UpdateRecipeRequest.php tests/Feature/RecipeInstructionSanitizationTest.php
git commit -m "feat: sanitize HTML in recipe instructions via mews/purifier"
```

---

### Task 4: Install frontend Tiptap packages

**Files:**
- Modify: `package.json`

**Step 1: Install Tiptap packages**

```bash
npm install @tiptap/vue-3 @tiptap/starter-kit @tiptap/extension-underline @tiptap/extension-link @tiptap/extension-image
```

**Step 2: Commit**

```bash
git add package.json package-lock.json
git commit -m "chore: install tiptap editor packages"
```

---

### Task 5: Create the RichTextEditor Vue component

**Files:**
- Create: `resources/js/components/ui/rich-text-editor/RichTextEditor.vue`
- Create: `resources/js/components/ui/rich-text-editor/index.ts`

**Step 1: Create the barrel export**

Create `resources/js/components/ui/rich-text-editor/index.ts`:

```typescript
export { default as RichTextEditor } from './RichTextEditor.vue';
```

**Step 2: Create the RichTextEditor component**

Create `resources/js/components/ui/rich-text-editor/RichTextEditor.vue`:

```vue
<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import { onBeforeUnmount, watch } from 'vue';

const props = defineProps<{
    modelValue?: string;
    placeholder?: string;
}>();

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
                    'flex size-8 items-center justify-center rounded text-sm transition-colors',
                    editor.isActive('bulletList') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                ]"
                @click="editor!.chain().focus().toggleBulletList().run()"
            >
                &bull;
            </button>

            <button
                type="button"
                title="Ordered list"
                :class="[
                    'flex size-8 items-center justify-center rounded text-sm transition-colors',
                    editor.isActive('orderedList') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                ]"
                @click="editor!.chain().focus().toggleOrderedList().run()"
            >
                1.
            </button>

            <button
                type="button"
                title="Blockquote"
                :class="[
                    'flex size-8 items-center justify-center rounded text-sm transition-colors',
                    editor.isActive('blockquote') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                ]"
                @click="editor!.chain().focus().toggleBlockquote().run()"
            >
                &ldquo;
            </button>

            <div class="mx-1 w-px self-stretch bg-border" />

            <button
                type="button"
                title="Horizontal rule"
                class="flex size-8 items-center justify-center rounded text-sm text-muted-foreground transition-colors hover:bg-accent/50 hover:text-foreground"
                @click="editor!.chain().focus().setHorizontalRule().run()"
            >
                &mdash;
            </button>

            <button
                type="button"
                title="Link"
                :class="[
                    'flex size-8 items-center justify-center rounded text-sm transition-colors',
                    editor.isActive('link') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                ]"
                @click="setLink()"
            >
                &#128279;
            </button>

            <button
                type="button"
                title="Image"
                class="flex size-8 items-center justify-center rounded text-sm text-muted-foreground transition-colors hover:bg-accent/50 hover:text-foreground"
                @click="addImage()"
            >
                &#128247;
            </button>
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>
```

**Step 3: Commit**

```bash
git add resources/js/components/ui/rich-text-editor/
git commit -m "feat: create RichTextEditor component wrapping Tiptap"
```

---

### Task 6: Integrate the editor into RecipeForm

**Files:**
- Modify: `resources/js/pages/recipes/Partials/RecipeForm.vue`

**Step 1: Replace the Textarea with the RichTextEditor**

In `RecipeForm.vue`:

1. Replace the `Textarea` import with `RichTextEditor`:
   ```typescript
   import { RichTextEditor } from '@/components/ui/rich-text-editor';
   ```

2. Add a ref for the instructions content:
   ```typescript
   const instructionsContent = ref(props.recipe?.instructions ?? '');
   ```

3. Replace the `<Textarea>` block (lines 393-400) with:
   ```vue
   <input type="hidden" name="instructions" :value="instructionsContent" />
   <RichTextEditor
       v-model="instructionsContent"
       placeholder="Write clear steps so you can cook without thinking."
   />
   ```

4. Remove the `Textarea` import if no longer used elsewhere.

**Step 2: Build and verify**

Run: `npm run build`

**Step 3: Commit**

```bash
git add resources/js/pages/recipes/Partials/RecipeForm.vue
git commit -m "feat: replace plain textarea with rich text editor for instructions"
```

---

### Task 7: Render HTML instructions on the Show page

**Files:**
- Modify: `resources/js/pages/recipes/Show.vue`

**Step 1: Replace plain text rendering with v-html**

In `Show.vue`, replace lines 208-212:

```vue
<p
    class="text-sm leading-relaxed whitespace-pre-line text-muted-foreground"
>
    {{ recipe.instructions }}
</p>
```

With:

```vue
<div
    class="recipe-instructions prose prose-sm max-w-none text-muted-foreground prose-headings:text-foreground prose-strong:text-foreground prose-a:text-primary"
    v-html="recipe.instructions"
/>
```

**Step 2: Verify Tailwind Typography plugin is available**

The `prose` classes come from `@tailwindcss/typography`. Check if installed. If not:

```bash
npm install @tailwindcss/typography
```

And add to the CSS file (Tailwind v4 style):

```css
@import "tailwindcss";
@plugin "@tailwindcss/typography";
```

**Step 3: Build and verify**

Run: `npm run build`

**Step 4: Commit**

```bash
git add resources/js/pages/recipes/Show.vue
# Also add package.json/css if typography plugin was added
git commit -m "feat: render rich HTML instructions on recipe show page"
```

---

### Task 8: Update existing tests for HTML content

**Files:**
- Modify: `tests/Feature/RecipeControllerTest.php`
- Modify: `tests/Feature/RecipeFractionTest.php`

**Step 1: Update tests that assert on instructions content**

Any tests that submit `instructions` as plain text will still work (purifier passes through plain text as-is, wrapping in `<p>` tags). However, tests that assert exact instruction values may need updating.

Review each test that sets `instructions` and adjust assertions if they check for exact string matches against the stored value.

For tests that check instructions display on the page, note that `v-html` renders HTML so Inertia test assertions using `assertInertia` on the `instructions` prop will still work since they check the data, not the rendered output.

**Step 2: Run the full recipe test suite**

Run: `php artisan test --compact tests/Feature/RecipeControllerTest.php tests/Feature/RecipeFractionTest.php tests/Feature/RecipeInstructionSanitizationTest.php tests/Feature/ImageUploadTest.php`
Expected: PASS

**Step 3: Commit if any test adjustments were needed**

```bash
git add tests/
git commit -m "test: update existing tests for HTML instruction content"
```

---

### Task 9: Wayfinder generation + final verification

**Step 1: Generate Wayfinder types for the new upload route**

Run: `php artisan wayfinder:generate`

**Step 2: Run pint**

Run: `vendor/bin/pint --dirty`

**Step 3: Run the full test suite**

Run: `php artisan test --compact`
Expected: ALL PASS

**Step 4: Final commit**

```bash
git add -A
git commit -m "chore: regenerate wayfinder types and format code"
```
