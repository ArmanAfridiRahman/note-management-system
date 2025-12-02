<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const cardVariants = cva('rounded-lg border bg-background text-foreground', {
    variants: {
        variant: {
            default: 'border-border',
            elevated: 'border-border shadow-md',
            outlined: 'border-border',
            flat: 'border-transparent bg-muted',
        },
        padding: {
            none: 'p-0',
            sm: 'p-3',
            md: 'p-4',
            lg: 'p-6',
        },
    },
    defaultVariants: {
        variant: 'default',
        padding: 'md',
    },
});

type CardVariants = VariantProps<typeof cardVariants>;

interface Props extends /* @vue-ignore */ HTMLAttributes {
    variant?: CardVariants['variant'];
    padding?: CardVariants['padding'];
    hoverable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    padding: 'md',
    hoverable: false,
});

const classes = computed(() =>
    cn(
        cardVariants({ variant: props.variant, padding: props.padding }),
        props.hoverable && 'transition-all hover:shadow-lg hover:-translate-y-0.5 cursor-pointer',
        props.class
    )
);
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
