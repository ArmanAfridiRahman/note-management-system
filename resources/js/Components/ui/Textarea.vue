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
        'flex min-h-[80px] w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background',
        'placeholder:text-muted-foreground',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
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
