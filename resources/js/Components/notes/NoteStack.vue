<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@/Components/ui';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight, Lock } from 'lucide-vue-next';
import type { NoteData, GroupData } from '@/types/models';

interface Props {
    group: GroupData;
    notes: NoteData[];
    draggable?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    draggable: false,
});

const emit = defineEmits<{
    viewNote: [note: NoteData];
    viewGroup: [group: GroupData];
    removeFromGroup: [noteId: number, groupId: number];
    dragStart: [group: GroupData, event: DragEvent];
    dragEnd: [group: GroupData, event: DragEvent];
    drop: [group: GroupData, event: DragEvent];
    dragOver: [group: GroupData, event: DragEvent];
    dragLeave: [group: GroupData, event: DragEvent];
}>();

const isDragging = ref(false);
const isHoveredForDrop = ref(false);
const currentStartIndex = ref(0);

// Show up to 3 mini notes at a time
const visibleNotes = computed(() => {
    return props.notes.slice(currentStartIndex.value, currentStartIndex.value + 3);
});

const hasMoreNotes = computed(() => props.notes.length > 3);
const totalNotes = computed(() => props.notes.length);
const remainingCount = computed(() => Math.max(0, props.notes.length - currentStartIndex.value - 3));

const canGoNext = computed(() => currentStartIndex.value + 3 < props.notes.length);
const canGoPrev = computed(() => currentStartIndex.value > 0);

const goNext = (event: Event) => {
    event.stopPropagation();
    if (canGoNext.value) {
        currentStartIndex.value = Math.min(currentStartIndex.value + 1, props.notes.length - 3);
    }
};

const goPrev = (event: Event) => {
    event.stopPropagation();
    if (canGoPrev.value) {
        currentStartIndex.value = Math.max(0, currentStartIndex.value - 1);
    }
};

// Convert hex to RGB
const hexToRgb = (hex: string) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
};

// Get mini card styles based on note color
const getMiniCardStyles = (note: NoteData) => {
    if (!note.color) return {};
    const rgb = hexToRgb(note.color);
    if (!rgb) return {};
    return {
        backgroundColor: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.12)`,
        borderColor: note.color,
    };
};

// Drag handlers
const handleDragStart = (event: DragEvent) => {
    if (!props.draggable) return;
    isDragging.value = true;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('application/json', JSON.stringify({
            type: 'group',
            groupId: props.group.id,
            groupName: props.group.name,
        }));
    }

    emit('dragStart', props.group, event);
};

const handleDragEnd = (event: DragEvent) => {
    isDragging.value = false;
    emit('dragEnd', props.group, event);
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
    isHoveredForDrop.value = true;
    emit('dragOver', props.group, event);
};

const handleDragLeave = (event: DragEvent) => {
    const relatedTarget = event.relatedTarget as HTMLElement | null;
    const currentTarget = event.currentTarget as HTMLElement;
    if (relatedTarget && currentTarget.contains(relatedTarget)) {
        return;
    }
    isHoveredForDrop.value = false;
    emit('dragLeave', props.group, event);
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    isHoveredForDrop.value = false;
    emit('drop', props.group, event);
};

const handleClick = (event: Event) => {
    event.stopPropagation();
    emit('viewGroup', props.group);
};
</script>

<template>
    <div
        :class="cn(
            'group relative aspect-square rounded-xl border-2 bg-card transition-all duration-200 cursor-pointer',
            'hover:shadow-md border-border/60 hover:border-border',
            isDragging && 'opacity-50 scale-95',
            isHoveredForDrop && 'ring-2 ring-primary ring-offset-2 scale-[1.02]',
            draggable && 'cursor-grab active:cursor-grabbing',
            props.class
        )"
        :draggable="draggable"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
        @click="handleClick"
    >
        <div class="flex flex-col h-full p-2">
            <!-- Mini Notes Grid -->
            <div
                :class="[
                    'flex-1 grid gap-1.5',
                    notes.length === 2 ? 'grid-cols-2' : 'grid-cols-3'
                ]"
            >
                <!-- Mini Note Cards (up to 3 visible) -->
                <div
                    v-for="(note, idx) in visibleNotes"
                    :key="note.id"
                    class="rounded-lg border bg-card overflow-hidden transition-all relative"
                    :class="[
                        !note.color && 'border-border/50',
                        note.is_encrypted ? 'p-0' : 'p-1.5 flex flex-col'
                    ]"
                    :style="getMiniCardStyles(note)"
                >
                    <!-- Locked State for Encrypted Notes -->
                    <template v-if="note.is_encrypted">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-purple-500/5" />
                        <div class="relative h-full flex flex-col items-center justify-center text-center p-1">
                            <Lock class="w-3 h-3 text-blue-500 mb-0.5" />
                            <span class="text-[7px] text-muted-foreground line-clamp-1">{{ note.title }}</span>
                        </div>
                    </template>

                    <!-- Normal State -->
                    <template v-else>
                        <!-- Mini Note Title -->
                        <h4 class="font-medium text-[9px] leading-tight line-clamp-2 text-foreground mb-0.5">
                            {{ note.title }}
                        </h4>
                        <!-- Mini Note Excerpt -->
                        <p class="text-[7px] text-muted-foreground line-clamp-3 flex-1">
                            {{ note.excerpt || 'No content' }}
                        </p>
                    </template>
                </div>
            </div>

            <!-- Footer with navigation or count -->
            <div class="flex items-center justify-between mt-1.5 pt-1.5 border-t border-border/30">
                <!-- Left: Note count -->
                <span class="text-[9px] text-muted-foreground font-medium">
                    {{ totalNotes }} notes
                </span>

                <!-- Right: Navigation arrows (only if 4+ notes) -->
                <div v-if="hasMoreNotes" class="flex items-center gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-5 w-5"
                        :disabled="!canGoPrev"
                        @click="goPrev"
                    >
                        <ChevronLeft class="h-3 w-3" />
                    </Button>
                    <span class="text-[9px] text-muted-foreground min-w-[24px] text-center">
                        +{{ remainingCount }}
                    </span>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-5 w-5"
                        :disabled="!canGoNext"
                        @click="goNext"
                    >
                        <ChevronRight class="h-3 w-3" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Drop indicator overlay -->
        <div
            v-if="isHoveredForDrop"
            class="absolute inset-0 rounded-xl bg-primary/10 flex items-center justify-center pointer-events-none z-10"
        >
            <div class="bg-primary text-primary-foreground px-3 py-1.5 rounded-full text-xs font-medium shadow-lg">
                Add to group
            </div>
        </div>
    </div>
</template>
