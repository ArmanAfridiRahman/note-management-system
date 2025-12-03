<script setup lang="ts">
import { ref, computed, type InputHTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props extends /* @vue-ignore */ InputHTMLAttributes {
    modelValue?: string | number;
    type?: string;
    error?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const inputRef = ref<HTMLInputElement | null>(null);

const inputClasses = computed(() =>
    cn(
        'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm transition-colors',
        'file:border-0 file:bg-transparent file:text-sm file:font-medium',
        'placeholder:text-muted-foreground',
        'outline-none ring-0 focus:outline-none focus:ring-0 focus-visible:outline-none focus-visible:ring-0',
        'focus:border-primary/50 focus:bg-background',
        'disabled:cursor-not-allowed disabled:opacity-50',
        props.error ? 'border-destructive' : 'border-input',
        props.class
    )
);

function onInput(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
}

function focus() {
    inputRef.value?.focus();
}

function select() {
    inputRef.value?.select();
}

defineExpose({ focus, select });
</script>

<template>
    <div class="w-full">
        <input
            ref="inputRef"
            :type="type"
            :value="modelValue"
            :class="inputClasses"
            v-bind="$attrs"
            @input="onInput"
        />
        <p v-if="error" class="mt-1.5 text-sm text-destructive">
            {{ error }}
        </p>
    </div>
</template>
