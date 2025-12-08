<script setup lang="ts">
import { ref, computed, type HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';

interface Props extends /* @vue-ignore */ HTMLAttributes {
    title: string;
    defaultOpen?: boolean;
    class?: string;
    headerClass?: string;
    contentClass?: string;
    count?: number;
}

const props = withDefaults(defineProps<Props>(), {
    defaultOpen: false,
});

const isOpen = ref(props.defaultOpen);

function toggle() {
    isOpen.value = !isOpen.value;
}
</script>

<template>
    <div :class="cn('border-b border-border', props.class)">
        <button
            type="button"
            :class="cn(
                'flex w-full items-center justify-between py-4 text-left transition-all hover:opacity-80',
                props.headerClass
            )"
            @click="toggle"
        >
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-foreground">{{ title }}</span>
                <span
                    v-if="count !== undefined"
                    class="text-xs text-muted-foreground bg-muted px-2 py-0.5 rounded-full"
                >
                    {{ count }}
                </span>
            </div>
            <ChevronDown
                :class="cn(
                    'h-4 w-4 text-muted-foreground transition-transform duration-200',
                    isOpen && 'rotate-180'
                )"
            />
        </button>
        <div
            :class="cn(
                'overflow-hidden transition-all duration-200',
                isOpen ? 'max-h-[5000px] opacity-100' : 'max-h-0 opacity-0'
            )"
        >
            <div :class="cn('pb-4', props.contentClass)">
                <slot />
            </div>
        </div>
    </div>
</template>
