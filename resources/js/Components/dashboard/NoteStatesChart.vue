<script setup lang="ts">
import { computed } from 'vue';

interface NoteStates {
    total: number;
    pinned: number;
    favorites: number;
    encrypted: number;
    archived: number;
    regular: number;
    pinned_favorite: number;
    pinned_encrypted: number;
    favorite_encrypted: number;
    pinned_favorite_encrypted: number;
}

interface Props {
    noteStates: NoteStates;
}

const props = defineProps<Props>();

const categories = computed(() => [
    { name: 'Pinned', value: props.noteStates.pinned, color: 'var(--color-chart-1)' },
    { name: 'Favorites', value: props.noteStates.favorites, color: 'var(--color-chart-2)' },
    { name: 'Encrypted', value: props.noteStates.encrypted, color: 'var(--color-chart-3)' },
    { name: 'Archived', value: props.noteStates.archived, color: 'var(--color-chart-4)' },
    { name: 'Regular', value: props.noteStates.regular, color: 'var(--color-chart-5)' },
]);

const maxValue = computed(() => Math.max(...categories.value.map(c => c.value), 1));

const overlapData = computed(() => {
    const overlaps = [];
    if (props.noteStates.pinned_favorite > 0) {
        overlaps.push({ label: 'Pinned + Favorite', count: props.noteStates.pinned_favorite });
    }
    if (props.noteStates.pinned_encrypted > 0) {
        overlaps.push({ label: 'Pinned + Encrypted', count: props.noteStates.pinned_encrypted });
    }
    if (props.noteStates.favorite_encrypted > 0) {
        overlaps.push({ label: 'Favorite + Encrypted', count: props.noteStates.favorite_encrypted });
    }
    if (props.noteStates.pinned_favorite_encrypted > 0) {
        overlaps.push({ label: 'All Three', count: props.noteStates.pinned_favorite_encrypted });
    }
    return overlaps;
});

// Calculate bar width percentages for stacked horizontal bar
const totalForBar = computed(() =>
    props.noteStates.pinned +
    props.noteStates.favorites +
    props.noteStates.encrypted +
    props.noteStates.archived +
    props.noteStates.regular
);

const stackedSegments = computed(() => {
    if (totalForBar.value === 0) return [];
    return categories.value
        .filter(c => c.value > 0)
        .map(c => ({
            ...c,
            width: (c.value / totalForBar.value) * 100
        }));
});
</script>

<template>
    <div class="space-y-4">
        <!-- Stacked Horizontal Bar -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-muted-foreground">Distribution</span>
                <span class="text-xs font-medium">{{ noteStates.total }} total</span>
            </div>
            <div class="h-8 w-full bg-muted rounded-lg overflow-hidden flex">
                <div
                    v-for="segment in stackedSegments"
                    :key="segment.name"
                    class="h-full transition-all duration-500 flex items-center justify-center"
                    :style="{
                        width: `${segment.width}%`,
                        backgroundColor: segment.color
                    }"
                >
                    <span
                        v-if="segment.width > 10"
                        class="text-[10px] font-medium text-white drop-shadow-sm"
                    >
                        {{ segment.value }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Individual Bars -->
        <div class="space-y-2">
            <div
                v-for="category in categories"
                :key="category.name"
                class="space-y-1"
            >
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span
                            class="h-2 w-2 rounded-full shrink-0"
                            :style="{ backgroundColor: category.color }"
                        />
                        <span class="text-muted-foreground">{{ category.name }}</span>
                    </div>
                    <span class="font-medium">{{ category.value }}</span>
                </div>
                <div class="h-1.5 w-full bg-muted rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :style="{
                            width: `${(category.value / maxValue) * 100}%`,
                            backgroundColor: category.color
                        }"
                    />
                </div>
            </div>
        </div>

        <!-- Overlapping States -->
        <div v-if="overlapData.length > 0" class="pt-3 border-t border-border">
            <p class="text-xs font-medium text-muted-foreground mb-2">Overlapping States</p>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="overlap in overlapData"
                    :key="overlap.label"
                    class="inline-flex items-center gap-1 text-xs bg-muted px-2 py-1 rounded-full"
                >
                    {{ overlap.label }}: <strong>{{ overlap.count }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
