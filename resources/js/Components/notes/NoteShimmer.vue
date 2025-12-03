<script setup lang="ts">
import { computed } from 'vue';
import { Skeleton } from '@/Components/ui';

interface Props {
    count?: number;
    gridCols?: 1 | 2 | 3 | 4 | 5;
}

const props = withDefaults(defineProps<Props>(), {
    count: 4,
    gridCols: 4,
});

const gridClasses = computed(() => {
    const classes: Record<number, string> = {
        1: 'grid-cols-1',
        2: 'grid-cols-2',
        3: 'grid-cols-2 sm:grid-cols-3',
        4: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
        5: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
    };
    return classes[props.gridCols] || classes[4];
});
</script>

<template>
    <div :class="['grid gap-4', gridClasses]">
        <div
            v-for="i in count"
            :key="i"
            class="aspect-square rounded-xl border-2 border-border/60 bg-card p-4"
        >
            <div class="flex flex-col h-full">
                <!-- Header skeleton -->
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <Skeleton width="14px" height="14px" rounded="full" />
                    </div>
                    <Skeleton width="20px" height="20px" rounded="md" />
                </div>

                <!-- Title skeleton -->
                <Skeleton width="85%" height="14px" class="mb-1" />
                <Skeleton width="60%" height="14px" class="mb-3" />

                <!-- Excerpt skeleton -->
                <div class="flex-1 space-y-1.5">
                    <Skeleton width="100%" height="10px" />
                    <Skeleton width="95%" height="10px" />
                    <Skeleton width="80%" height="10px" />
                    <Skeleton width="70%" height="10px" />
                </div>

                <!-- Tags skeleton -->
                <div class="flex gap-1.5 mt-2">
                    <Skeleton width="40px" height="16px" rounded="sm" />
                    <Skeleton width="50px" height="16px" rounded="sm" />
                </div>

                <!-- Footer skeleton -->
                <div class="mt-2 pt-2 border-t border-border/30">
                    <Skeleton width="50px" height="10px" />
                </div>
            </div>
        </div>
    </div>
</template>
