<script setup lang="ts">
import { computed } from 'vue';
import { Card, Skeleton } from '@/Components/ui';

interface Props {
    count?: number;
    gridCols?: 1 | 2 | 3 | 4;
}

const props = withDefaults(defineProps<Props>(), {
    count: 3,
    gridCols: 3,
});

const gridClasses = computed(() => ({
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
}[props.gridCols]));
</script>

<template>
    <div :class="['grid gap-4', gridClasses]">
        <Card v-for="i in count" :key="i" padding="md" variant="outlined">
            <div class="space-y-3">
                <!-- Title -->
                <Skeleton width="70%" height="20px" />

                <!-- Excerpt -->
                <div class="space-y-1.5">
                    <Skeleton width="100%" height="14px" />
                    <Skeleton width="90%" height="14px" />
                    <Skeleton width="60%" height="14px" />
                </div>

                <!-- Tags -->
                <div class="flex flex-wrap gap-2 pt-2">
                    <Skeleton width="60px" height="22px" rounded="full" />
                    <Skeleton width="75px" height="22px" rounded="full" />
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-4 flex justify-between border-t border-border pt-3">
                <Skeleton width="70px" height="12px" />
                <Skeleton width="50px" height="12px" />
            </div>
        </Card>
    </div>
</template>
