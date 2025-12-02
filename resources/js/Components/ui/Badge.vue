<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-primary text-primary-foreground',
                secondary: 'border-transparent bg-secondary text-secondary-foreground',
                destructive: 'border-transparent bg-destructive text-destructive-foreground',
                outline: 'text-foreground',
                accent: 'border-transparent bg-accent text-accent-foreground',
                success: 'border-transparent bg-green-500 text-white',
                warning: 'border-transparent bg-yellow-500 text-white',
            },
            size: {
                sm: 'px-2 py-0.5 text-xs',
                md: 'px-2.5 py-0.5 text-xs',
                lg: 'px-3 py-1 text-sm',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'md',
        },
    }
);

type BadgeVariants = VariantProps<typeof badgeVariants>;

interface Props extends /* @vue-ignore */ HTMLAttributes {
    variant?: BadgeVariants['variant'];
    size?: BadgeVariants['size'];
    removable?: boolean;
    color?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    size: 'md',
    removable: false,
});

const emit = defineEmits<{
    remove: [];
}>();

const classes = computed(() =>
    cn(badgeVariants({ variant: props.variant, size: props.size }), props.class)
);

const customStyle = computed(() => {
    if (props.color) {
        return {
            backgroundColor: `${props.color}20`,
            color: props.color,
            borderColor: `${props.color}40`,
        };
    }
    return {};
});
</script>

<template>
    <span :class="classes" :style="customStyle">
        <slot />
        <button
            v-if="removable"
            type="button"
            class="ml-1 inline-flex h-3.5 w-3.5 items-center justify-center rounded-full hover:bg-black/10 focus:outline-none"
            @click="emit('remove')"
        >
            <svg
                class="h-3 w-3"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                />
            </svg>
        </button>
    </span>
</template>
