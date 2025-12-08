<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    modelValue: boolean;
    disabled?: boolean;
    label?: string;
    size?: 'sm' | 'default';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    size: 'default',
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

function toggle() {
    if (!props.disabled) {
        emit('update:modelValue', !props.modelValue);
    }
}

const sizeClasses = computed(() => {
    if (props.size === 'sm') {
        return {
            track: 'h-5 w-9',
            thumb: 'h-4 w-4',
            translate: props.modelValue ? 'translate-x-4' : 'translate-x-0',
        };
    }
    return {
        track: 'h-6 w-11',
        thumb: 'h-5 w-5',
        translate: props.modelValue ? 'translate-x-5' : 'translate-x-0',
    };
});
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
                'relative inline-flex shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                sizeClasses.track,
                modelValue ? 'bg-primary' : 'bg-muted',
                disabled && 'cursor-not-allowed'
            )"
            @click="toggle"
        >
            <span
                :class="cn(
                    'pointer-events-none inline-block transform rounded-full bg-background shadow-lg ring-0 transition duration-200 ease-in-out',
                    sizeClasses.thumb,
                    sizeClasses.translate
                )"
            />
        </button>
        <span v-if="label" class="text-sm text-foreground">{{ label }}</span>
    </label>
</template>
