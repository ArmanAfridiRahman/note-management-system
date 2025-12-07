<script setup lang="ts">
import { computed } from 'vue';
import { VisXYContainer, VisSingleContainer, VisDonut } from '@unovis/vue';

interface TopNote {
    id: number;
    title: string;
    open_count: number;
    color: string | null;
}

interface Props {
    notes: TopNote[];
}

const props = defineProps<Props>();

const defaultColors = [
    'var(--color-chart-1)',
    'var(--color-chart-2)',
    'var(--color-chart-3)',
    'var(--color-chart-4)',
    'var(--color-chart-5)',
];

const chartData = computed(() =>
    props.notes.map((note, index) => ({
        ...note,
        displayColor: note.color || defaultColors[index % defaultColors.length],
    }))
);

const value = (d: any) => d.open_count;
const colors = computed(() => chartData.value.map(d => d.displayColor));

const maxCount = computed(() => Math.max(...props.notes.map(n => n.open_count), 1));
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-muted-foreground">Most Opened Notes</h3>
        </div>

        <div v-if="notes.length === 0" class="text-center py-8 text-sm text-muted-foreground">
            No data available
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="(note, index) in chartData"
                :key="note.id"
                class="space-y-1"
            >
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-foreground truncate max-w-[180px]">
                        {{ index + 1 }}. {{ note.title }}
                    </span>
                    <span class="text-muted-foreground ml-2 shrink-0">
                        {{ note.open_count }} opens
                    </span>
                </div>
                <div class="h-2 w-full bg-muted rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :style="{
                            width: `${(note.open_count / maxCount) * 100}%`,
                            backgroundColor: note.displayColor
                        }"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
