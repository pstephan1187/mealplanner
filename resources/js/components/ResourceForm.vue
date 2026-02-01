<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type FormMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        backRoute: string;
        backLabel: string;
        submitLabel: string;
        formAction: { action: string; method: FormMethod };
        narrow?: boolean;
    }>(),
    {
        narrow: false,
    },
);
</script>

<template>
    <div class="flex flex-col gap-8 px-6 py-8">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading :title="title" :description="description" />
            <Button variant="ghost" as-child>
                <Link :href="backRoute">{{ backLabel }}</Link>
            </Button>
        </div>

        <Form
            v-bind="formAction"
            :class="[props.narrow ? 'max-w-xl space-y-6' : 'space-y-6']"
            v-slot="{
                errors,
                hasErrors,
                processing,
                progress,
                wasSuccessful,
                recentlySuccessful,
                setError,
                clearErrors,
                resetAndClearErrors,
                defaults,
                isDirty,
                reset,
                submit,
            }"
        >
            <slot
                :errors="errors"
                :has-errors="hasErrors"
                :processing="processing"
                :progress="progress"
                :was-successful="wasSuccessful"
                :recently-successful="recentlySuccessful"
                :set-error="setError"
                :clear-errors="clearErrors"
                :reset-and-clear-errors="resetAndClearErrors"
                :defaults="defaults"
                :is-dirty="isDirty"
                :reset="reset"
                :submit="submit"
            />

            <div class="flex flex-wrap items-center gap-3">
                <Button variant="secondary" as-child>
                    <Link :href="backRoute">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ submitLabel }}
                </Button>
            </div>
        </Form>
    </div>
</template>
