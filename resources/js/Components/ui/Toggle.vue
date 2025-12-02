<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    modelValue: boolean;
    disabled?: boolean;
    label?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

function toggle() {
    if (!props.disabled) {
        emit('update:modelValue', !props.modelValue);
    }
}
</script>

<template>
    <label
        :class="cn(
            'inline-flex cursor-pointer items-center gap-3',
            disabled && 'cursor-not-allowed opacity-50',
            props.class
        )"
    >
        <button
            type="button"
            role="switch"
            :aria-checked="modelValue"
            :disabled="disabled"
            :class="cn(
                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                modelValue ? 'bg-primary' : 'bg-muted',
                disabled && 'cursor-not-allowed'
            )"
            @click="toggle"
        >
            <span
                :class="cn(
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-background shadow-lg ring-0 transition duration-200 ease-in-out',
                    modelValue ? 'translate-x-5' : 'translate-x-0'
                )"
            />
        </button>
        <span v-if="label" class="text-sm text-foreground">{{ label }}</span>
    </label>
</template>
