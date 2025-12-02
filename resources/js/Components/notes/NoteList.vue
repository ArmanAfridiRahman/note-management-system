<script setup lang="ts">
import { watch, computed } from 'vue';
import NoteCard from './NoteCard.vue';
import NoteShimmer from './NoteShimmer.vue';
import { EmptyState } from '@/Components/shared';
import { useInfiniteScroll } from '@/Composables/useInfiniteScroll';

interface Tag {
    id: number;
    name: string;
    slug: string;
    color: string;
}

interface Group {
    id: number;
    name: string;
    slug: string;
    color?: string;
}

interface Note {
    id: number;
    title: string;
    slug: string;
    excerpt?: string;
    is_encrypted: boolean;
    is_pinned: boolean;
    is_archived: boolean;
    is_favorited: boolean;
    color?: string;
    created_at: string;
    updated_at: string;
    group?: Group;
    tags: Tag[];
}

interface Props {
    fetchUrl: string;
    initialNotes?: Note[];
    filters?: Record<string, unknown>;
    emptyIcon?: string;
    emptyTitle?: string;
    emptyDescription?: string;
    emptyActionLabel?: string;
    compact?: boolean;
    gridCols?: 1 | 2 | 3 | 4;
}

const props = withDefaults(defineProps<Props>(), {
    initialNotes: () => [],
    filters: () => ({}),
    emptyIcon: '📝',
    emptyTitle: 'No notes yet',
    emptyDescription: 'Create your first note to get started.',
    compact: false,
    gridCols: 3,
});

const emit = defineEmits<{
    view: [note: Note];
    edit: [note: Note];
    delete: [note: Note];
    archive: [note: Note];
    share: [note: Note];
    togglePin: [note: Note];
    toggleFavorite: [note: Note];
    createNew: [];
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
} = useInfiniteScroll<Note>({
    url: props.fetchUrl,
    initialItems: props.initialNotes,
    filters: props.filters,
});

// Expose refresh to parent
defineExpose({ refresh, updateFilters });

// Watch for filter changes - debounced to prevent rapid refreshes
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

const gridClasses = computed(() => ({
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
}[props.gridCols]));

const showEmptyState = computed(() => !loading.value && !initialLoading.value && notes.value.length === 0 && !error.value);
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
            <NoteCard
                v-for="note in notes"
                :key="note.id"
                :note="note"
                :compact="compact"
                @view="emit('view', $event)"
                @edit="emit('edit', $event)"
                @delete="emit('delete', $event)"
                @archive="emit('archive', $event)"
                @share="emit('share', $event)"
                @toggle-pin="emit('togglePin', $event)"
                @toggle-favorite="emit('toggleFavorite', $event)"
            />
        </div>

        <!-- Loading More State (Shimmer) -->
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
