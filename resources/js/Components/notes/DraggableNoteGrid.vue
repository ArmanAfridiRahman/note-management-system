<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import DraggableNoteCard from './DraggableNoteCard.vue';
import NoteShimmer from './NoteShimmer.vue';
import { EmptyState } from '@/Components/shared';
import { useInfiniteScroll } from '@/Composables/useInfiniteScroll';
import type { NoteData } from '@/types/models';

interface Props {
    fetchUrl: string;
    initialNotes?: NoteData[];
    filters?: Record<string, unknown>;
    emptyIcon?: string;
    emptyTitle?: string;
    emptyDescription?: string;
    emptyActionLabel?: string;
    compact?: boolean;
    gridCols?: 1 | 2 | 3 | 4 | 5;
    draggable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    initialNotes: () => [],
    filters: () => ({}),
    emptyIcon: '📝',
    emptyTitle: 'No notes yet',
    emptyDescription: 'Create your first note to get started.',
    compact: false,
    gridCols: 3,
    draggable: true,
});

const emit = defineEmits<{
    view: [note: NoteData];
    edit: [note: NoteData];
    delete: [note: NoteData];
    archive: [note: NoteData];
    share: [note: NoteData];
    togglePin: [note: NoteData];
    toggleFavorite: [note: NoteData];
    createNew: [];
    noteDragStart: [note: NoteData, event: DragEvent];
    noteDragEnd: [note: NoteData, event: DragEvent];
}>();

const {
    items: notes,
    loading,
    initialLoading,
    hasMore,
    error,
    sentinel,
    refresh,
    updateFilters,
} = useInfiniteScroll<NoteData>({
    url: props.fetchUrl,
    initialItems: props.initialNotes,
    filters: props.filters,
});

// Expose refresh to parent
defineExpose({ refresh, updateFilters });

// Watch for filter changes
let filterTimeout: ReturnType<typeof setTimeout> | null = null;
watch(
    () => props.filters,
    (newFilters) => {
        if (filterTimeout) clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => {
            updateFilters(newFilters as Record<string, unknown>);
        }, 100);
    },
    { deep: true }
);

const gridClasses = computed(() => {
    const classes: Record<number, string> = {
        1: 'grid-cols-1',
        2: 'grid-cols-1 sm:grid-cols-2',
        3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
        5: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
    };
    return classes[props.gridCols] || classes[3];
});

const showEmptyState = computed(() => !loading.value && !initialLoading.value && notes.value.length === 0 && !error.value);

const handleDragStart = (note: NoteData, event: DragEvent) => {
    emit('noteDragStart', note, event);
};

const handleDragEnd = (note: NoteData, event: DragEvent) => {
    emit('noteDragEnd', note, event);
};
</script>

<template>
    <div>
        <!-- Error State -->
        <div
            v-if="error"
            class="rounded-lg border border-destructive bg-destructive/10 p-4 text-destructive"
        >
            {{ error }}
            <button class="ml-2 underline" @click="() => refresh()">Retry</button>
        </div>

        <!-- Initial Loading State -->
        <NoteShimmer v-if="initialLoading" :count="6" :grid-cols="gridCols" />

        <!-- Notes Grid -->
        <div
            v-else-if="notes.length > 0"
            :class="['grid gap-4', gridClasses]"
        >
            <DraggableNoteCard
                v-for="note in notes"
                :key="note.id"
                :note="note"
                :compact="compact"
                :draggable="draggable"
                @view="emit('view', $event)"
                @edit="emit('edit', $event)"
                @delete="emit('delete', $event)"
                @archive="emit('archive', $event)"
                @share="emit('share', $event)"
                @toggle-pin="emit('togglePin', $event)"
                @toggle-favorite="emit('toggleFavorite', $event)"
                @drag-start="handleDragStart"
                @drag-end="handleDragEnd"
            />
        </div>

        <!-- Loading More State -->
        <NoteShimmer v-if="loading && !initialLoading && notes.length > 0" :count="3" :grid-cols="gridCols" class="mt-4" />

        <!-- Infinite Scroll Sentinel -->
        <div v-if="hasMore && !loading" ref="sentinel" class="h-4" />

        <!-- Empty State -->
        <EmptyState
            v-if="showEmptyState"
            :icon="emptyIcon"
            :title="emptyTitle"
            :description="emptyDescription"
            :action-label="emptyActionLabel"
            @action="emit('createNew')"
        />
    </div>
</template>
