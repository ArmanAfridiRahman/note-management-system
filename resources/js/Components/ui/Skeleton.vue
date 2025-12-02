<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    width?: string;
    height?: string;
    rounded?: 'none' | 'sm' | 'md' | 'lg' | 'full';
    animate?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    width: '100%',
    height: '1rem',
    rounded: 'md',
    animate: true,
});

const roundedClasses = {
    none: 'rounded-none',
    sm: 'rounded-sm',
    md: 'rounded-md',
    lg: 'rounded-lg',
    full: 'rounded-full',
};

const classes = computed(() =>
    cn(
        'shimmer bg-muted',
        roundedClasses[props.rounded],
        props.animate && 'animate-pulse',
        props.class
    )
);

const style = computed(() => ({
    width: props.width,
    height: props.height,
}));
</script>

<template>
    <div :class="classes" :style="style" />
</template>
