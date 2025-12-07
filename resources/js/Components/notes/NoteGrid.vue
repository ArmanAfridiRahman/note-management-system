<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import NoteCard from './NoteCard.vue';
import NoteStack from './NoteStack.vue';
import NoteShimmer from './NoteShimmer.vue';
import { EmptyState } from '@/Components/shared';
import { useInfiniteScroll } from '@/Composables/useInfiniteScroll';
import type { NoteData, GroupData } from '@/types/models';

// Track the currently dragging note for preview
const draggingNote = ref<{ id: number; title: string } | null>(null);

// API returns items in this format
interface DisplayItem {
    type: 'note' | 'group';
    note?: NoteData;
    group?: GroupData;
    notes?: NoteData[];
}

interface Props {
    fetchUrl: string;
    initialNotes?: NoteData[];
    filters?: Record<string, unknown>;
    emptyIcon?: string;
    emptyTitle?: string;
    emptyDescription?: string;
    emptyActionLabel?: string;
    compact?: boolean;
    gridCols?: 2 | 3 | 4 | 5;
    draggable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    initialNotes: () => [],
    filters: () => ({}),
    emptyIcon: '📝',
    emptyTitle: 'No notes yet',
    emptyDescription: 'Create your first note to get started.',
    compact: false,
    gridCols: 4,
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
    unlock: [note: NoteData];
    createNew: [];
    createGroup: [sourceNoteId: number, targetNoteId: number];
    addToGroup: [noteId: number, groupId: number];
    removeFromGroup: [noteId: number];
    mergeGroups: [sourceGroup: GroupData, targetGroup: GroupData];
    viewGroup: [group: GroupData, notes: NoteData[]];
}>();

// Track dragging group
const draggingGroup = ref<{ id: number; name: string } | null>(null);

// Track dragging note from group
const draggingNoteFromGroup = ref<{ noteId: number; groupId: number } | null>(null);

// Grid drop zone state
const isGridDropZone = ref(false);

const {
    items: displayItems,
    loading,
    initialLoading,
    hasMore,
    error,
    sentinel,
    refresh,
    updateFilters,
} = useInfiniteScroll<DisplayItem>({
    url: props.fetchUrl,
    initialItems: [],
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

const gridClasses = computed(() => ({
    2: 'grid-cols-2',
    3: 'grid-cols-2 sm:grid-cols-3',
    4: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
    5: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
}[props.gridCols]));

const showEmptyState = computed(() =>
    !loading.value && !initialLoading.value && displayItems.value.length === 0 && !error.value
);

// Helper to check if an item is pinned
const isItemPinned = (item: DisplayItem): boolean => {
    if (item.type === 'note' && item.note) {
        return item.note.is_pinned;
    }
    if (item.type === 'group' && item.notes) {
        // A group is pinned if any of its notes are pinned
        return item.notes.some(note => note.is_pinned);
    }
    return false;
};

// Separate pinned items from regular items
const pinnedItems = computed(() =>
    displayItems.value.filter(item => isItemPinned(item))
);

const regularItems = computed(() =>
    displayItems.value.filter(item => !isItemPinned(item))
);

// Handle note drop on another note (create group)
const handleNoteDrop = (targetNote: NoteData, event: DragEvent) => {
    const data = event.dataTransfer?.getData('application/json');
    if (!data) return;

    try {
        const parsed = JSON.parse(data);
        if (parsed.type === 'note' && parsed.noteId && parsed.noteId !== targetNote.id) {
            emit('createGroup', parsed.noteId, targetNote.id);
        }
    } catch (e) {
        console.error('Failed to parse drop data:', e);
    }
};

// Handle note or group drop on a group stack
const handleGroupDrop = (targetGroup: GroupData, event: DragEvent) => {
    const data = event.dataTransfer?.getData('application/json');
    if (!data) return;

    try {
        const parsed = JSON.parse(data);
        if (parsed.type === 'note' && parsed.noteId) {
            emit('addToGroup', parsed.noteId, targetGroup.id);
        } else if (parsed.type === 'group' && parsed.groupId && parsed.groupId !== targetGroup.id) {
            // Group dropped on another group - emit merge event
            const sourceGroup: GroupData = {
                id: parsed.groupId,
                name: parsed.groupName,
                slug: '',
            };
            emit('mergeGroups', sourceGroup, targetGroup);
        }
    } catch (e) {
        console.error('Failed to parse drop data:', e);
    }
};

// Wrapper to handle drop events from NoteCard
const onNoteCardDrop = (note: NoteData, event: DragEvent) => {
    handleNoteDrop(note, event);
};

// Wrapper to handle drop events from NoteStack
const onNoteStackDrop = (group: GroupData, event: DragEvent) => {
    handleGroupDrop(group, event);
};

// Track when a note starts being dragged
const onNoteDragStart = (note: NoteData, _event: DragEvent) => {
    draggingNote.value = { id: note.id, title: note.title };
};

// Clear dragging note when drag ends
const onNoteDragEnd = (_note: NoteData, _event: DragEvent) => {
    draggingNote.value = null;
};

// Track when a group starts being dragged
const onGroupDragStart = (group: GroupData, _event: DragEvent) => {
    draggingGroup.value = { id: group.id, name: group.name };
};

// Clear dragging group when drag ends
const onGroupDragEnd = (_group: GroupData, _event: DragEvent) => {
    draggingGroup.value = null;
};

// Track when a note from a group starts being dragged
const onNoteFromGroupDragStart = (note: NoteData, groupId: number, _event: DragEvent) => {
    draggingNoteFromGroup.value = { noteId: note.id, groupId };
};

// Clear dragging note from group when drag ends
const onNoteFromGroupDragEnd = (_note: NoteData, _event: DragEvent) => {
    draggingNoteFromGroup.value = null;
    isGridDropZone.value = false;
};

// Grid drop zone handlers
const onGridDragOver = (event: DragEvent) => {
    if (!draggingNoteFromGroup.value) return;
    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
    isGridDropZone.value = true;
};

const onGridDragLeave = (event: DragEvent) => {
    const relatedTarget = event.relatedTarget as HTMLElement | null;
    const currentTarget = event.currentTarget as HTMLElement;
    if (relatedTarget && currentTarget.contains(relatedTarget)) {
        return;
    }
    isGridDropZone.value = false;
};

const onGridDrop = (event: DragEvent) => {
    event.preventDefault();
    isGridDropZone.value = false;

    const data = event.dataTransfer?.getData('application/json');
    if (!data) return;

    try {
        const parsed = JSON.parse(data);
        if (parsed.type === 'note-from-group' && parsed.noteId) {
            emit('removeFromGroup', parsed.noteId);
        }
    } catch (e) {
        console.error('Failed to parse drop data:', e);
    }

    draggingNoteFromGroup.value = null;
};
</script>

<template>
    <div
        @dragover="onGridDragOver"
        @dragleave="onGridDragLeave"
        @drop="onGridDrop"
        :class="[
            'relative transition-all',
            isGridDropZone && 'ring-2 ring-primary ring-offset-4 rounded-lg'
        ]"
    >
        <!-- Drop Zone Indicator -->
        <div
            v-if="isGridDropZone"
            class="absolute inset-0 bg-primary/5 rounded-lg pointer-events-none z-10 flex items-center justify-center"
        >
            <div class="bg-primary text-primary-foreground px-4 py-2 rounded-full text-sm font-medium shadow-lg">
                Drop here to remove from group
            </div>
        </div>

        <!-- Error State -->
        <div
            v-if="error"
            class="rounded-lg border border-destructive bg-destructive/10 p-4 text-destructive"
        >
            {{ error }}
            <button class="ml-2 underline" @click="() => refresh()">Retry</button>
        </div>

        <!-- Initial Loading State -->
        <NoteShimmer v-if="initialLoading" :count="8" :grid-cols="gridCols" />

        <!-- Notes Grid -->
        <div v-else-if="displayItems.length > 0" class="space-y-6">
            <!-- Pinned Section -->
            <div v-if="pinnedItems.length > 0">
                <h2 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">
                    Pinned
                </h2>
                <div :class="['grid gap-4', gridClasses]">
                    <template v-for="item in pinnedItems" :key="'pinned-' + (item.note?.id || item.group?.id)">
                        <!-- Individual Note Card -->
                        <NoteCard
                            v-if="item.type === 'note' && item.note"
                            :note="item.note"
                            :compact="compact"
                            :draggable="draggable"
                            :dragging-note="draggingNote"
                            @view="emit('view', $event)"
                            @edit="emit('edit', $event)"
                            @delete="emit('delete', $event)"
                            @archive="emit('archive', $event)"
                            @share="emit('share', $event)"
                            @toggle-pin="emit('togglePin', $event)"
                            @toggle-favorite="emit('toggleFavorite', $event)"
                            @unlock="emit('unlock', $event)"
                            @drag-start="onNoteDragStart"
                            @drag-end="onNoteDragEnd"
                            @drop="(note, event) => onNoteCardDrop(note, event)"
                        />

                        <!-- Grouped Notes Stack -->
                        <NoteStack
                            v-else-if="item.type === 'group' && item.group && item.notes && item.notes.length > 0"
                            :group="item.group"
                            :notes="Array.isArray(item.notes) ? item.notes : []"
                            :draggable="draggable"
                            @view-note="emit('view', $event)"
                            @view-group="emit('viewGroup', item.group, item.notes)"
                            @drag-start="onGroupDragStart"
                            @drag-end="onGroupDragEnd"
                            @note-drag-start="onNoteFromGroupDragStart"
                            @note-drag-end="onNoteFromGroupDragEnd"
                            @drop="(group, event) => onNoteStackDrop(group, event)"
                        />
                    </template>
                </div>
            </div>

            <!-- Regular Notes Section -->
            <div v-if="regularItems.length > 0">
                <h2
                    v-if="pinnedItems.length > 0"
                    class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3"
                >
                    Others
                </h2>
                <div :class="['grid gap-4', gridClasses]">
                    <template v-for="item in regularItems" :key="'regular-' + (item.note?.id || item.group?.id)">
                        <!-- Individual Note Card -->
                        <NoteCard
                            v-if="item.type === 'note' && item.note"
                            :note="item.note"
                            :compact="compact"
                            :draggable="draggable"
                            :dragging-note="draggingNote"
                            @view="emit('view', $event)"
                            @edit="emit('edit', $event)"
                            @delete="emit('delete', $event)"
                            @archive="emit('archive', $event)"
                            @share="emit('share', $event)"
                            @toggle-pin="emit('togglePin', $event)"
                            @toggle-favorite="emit('toggleFavorite', $event)"
                            @unlock="emit('unlock', $event)"
                            @drag-start="onNoteDragStart"
                            @drag-end="onNoteDragEnd"
                            @drop="(note, event) => onNoteCardDrop(note, event)"
                        />

                        <!-- Grouped Notes Stack -->
                        <NoteStack
                            v-else-if="item.type === 'group' && item.group && item.notes && item.notes.length > 0"
                            :group="item.group"
                            :notes="Array.isArray(item.notes) ? item.notes : []"
                            :draggable="draggable"
                            @view-note="emit('view', $event)"
                            @view-group="emit('viewGroup', item.group, item.notes)"
                            @drag-start="onGroupDragStart"
                            @drag-end="onGroupDragEnd"
                            @note-drag-start="onNoteFromGroupDragStart"
                            @note-drag-end="onNoteFromGroupDragEnd"
                            @drop="(group, event) => onNoteStackDrop(group, event)"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Loading More State -->
        <NoteShimmer
            v-if="loading && !initialLoading && displayItems.length > 0"
            :count="4"
            :grid-cols="gridCols"
            class="mt-4"
        />

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
