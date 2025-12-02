<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    disabled?: boolean;
    destructive?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    destructive: false,
});

const emit = defineEmits<{
    click: [];
}>();
</script>

<template>
    <button
        :class="cn(
            'flex w-full items-center px-4 py-2 text-left text-sm transition-colors',
            disabled
                ? 'cursor-not-allowed opacity-50'
                : 'hover:bg-muted',
            destructive ? 'text-destructive' : 'text-foreground',
            props.class
        )"
        :disabled="disabled"
        @click="emit('click')"
    >
        <slot />
    </button>
</template>
