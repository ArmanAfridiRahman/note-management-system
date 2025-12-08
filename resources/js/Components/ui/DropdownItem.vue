<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';

interface Props {
    as?: 'button' | 'link';
    href?: string;
    method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    disabled?: boolean;
    destructive?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    as: 'button',
    method: 'get',
    disabled: false,
    destructive: false,
});

const emit = defineEmits<{
    click: [];
}>();

const baseClasses = 'flex w-full items-center gap-2 px-4 py-2 text-left text-sm transition-colors';
</script>

<template>
    <Link
        v-if="as === 'link' && href"
        :href="href"
        :method="method"
        :class="cn(
            baseClasses,
            'hover:bg-muted',
            destructive ? 'text-destructive' : 'text-foreground',
            props.class
        )"
    >
        <slot />
    </Link>
    <button
        v-else
        type="button"
        :class="cn(
            baseClasses,
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
