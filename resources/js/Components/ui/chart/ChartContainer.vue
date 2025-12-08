<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface ChartConfig {
    [key: string]: {
        label: string;
        color: string;
    };
}

interface Props extends /* @vue-ignore */ HTMLAttributes {
    config: ChartConfig;
    class?: string;
}

const props = defineProps<Props>();

const cssVars = computed(() => {
    const vars: Record<string, string> = {};
    Object.entries(props.config).forEach(([key, value]) => {
        vars[`--color-${key}`] = value.color;
    });
    return vars;
});
</script>

<template>
    <div :class="cn('flex aspect-video justify-center text-xs', props.class)" :style="cssVars">
        <slot />
    </div>
</template>
