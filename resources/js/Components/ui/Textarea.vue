<script setup lang="ts">
import { computed, type TextareaHTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props extends /* @vue-ignore */ TextareaHTMLAttributes {
    modelValue?: string;
    error?: string;
    class?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const textareaClasses = computed(() =>
    cn(
        'flex min-h-[80px] w-full rounded-md border bg-background px-3 py-2 text-sm transition-colors',
        'placeholder:text-muted-foreground',
        'outline-none ring-0 focus:outline-none focus:ring-0 focus-visible:outline-none focus-visible:ring-0',
        'focus:border-primary/50 focus:bg-background',
        'disabled:cursor-not-allowed disabled:opacity-50',
        props.error ? 'border-destructive' : 'border-input',
        props.class
    )
);

function onInput(event: Event) {
    const target = event.target as HTMLTextAreaElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <div class="w-full">
        <textarea
            :value="modelValue"
            :class="textareaClasses"
            v-bind="$attrs"
            @input="onInput"
        />
        <p v-if="error" class="mt-1.5 text-sm text-destructive">
            {{ error }}
        </p>
    </div>
</template>
