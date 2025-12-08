<script setup lang="ts">
import { computed, ref, inject } from 'vue';
import { Button, Dropdown, DropdownItem } from '@/Components/ui';
import { formatRelativeTime, cn } from '@/lib/utils';
import {
    Pin,
    Lock,
    Star,
    MoreHorizontal,
    Edit,
    Share2,
    Archive,
    Trash2
} from 'lucide-vue-next';
import type { NoteData } from '@/types/models';

interface Props {
    note: NoteData;
    compact?: boolean;
    draggable?: boolean;
    draggingNote?: { id: number; title: string } | null;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
    draggable: false,
    draggingNote: null,
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
    dragStart: [note: NoteData, event: DragEvent];
    dragEnd: [note: NoteData, event: DragEvent];
    drop: [note: NoteData, event: DragEvent];
    dragOver: [note: NoteData, event: DragEvent];
    dragLeave: [note: NoteData, event: DragEvent];
}>();

// Check if note is encrypted and locked
const isLocked = computed(() => props.note.is_encrypted);

const isDragging = ref(false);
const isHoveredForDrop = ref(false);
const justDropped = ref(false);

const formattedDate = computed(() => formatRelativeTime(props.note.updated_at));

// Check if the currently dragging note is different from this one
const showDropPreview = computed(() => {
    return isHoveredForDrop.value &&
           props.draggingNote &&
           props.draggingNote.id !== props.note.id;
});

// Convert hex to RGB for creating light background
const hexToRgb = (hex: string) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
};

// Card styles based on note color
const cardStyles = computed(() => {
    if (!props.note.color) return {};

    const rgb = hexToRgb(props.note.color);
    if (!rgb) return {};

    return {
        backgroundColor: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.08)`,
        borderColor: props.note.color,
    };
});

// Drag handlers
const handleDragStart = (event: DragEvent) => {
    if (!props.draggable) return;
    isDragging.value = true;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('application/json', JSON.stringify({
            type: 'note',
            noteId: props.note.id,
            noteTitle: props.note.title,
        }));
    }

    emit('dragStart', props.note, event);
};

const handleDragEnd = (event: DragEvent) => {
    isDragging.value = false;
    emit('dragEnd', props.note, event);
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
    isHoveredForDrop.value = true;
    emit('dragOver', props.note, event);
};

const handleDragLeave = (event: DragEvent) => {
    // Only handle if we're actually leaving this element (not entering a child)
    const relatedTarget = event.relatedTarget as HTMLElement | null;
    const currentTarget = event.currentTarget as HTMLElement;
    if (relatedTarget && currentTarget.contains(relatedTarget)) {
        return;
    }
    isHoveredForDrop.value = false;
    emit('dragLeave', props.note, event);
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    isHoveredForDrop.value = false;

    // Only emit if there's actual data and it's a different note
    const data = event.dataTransfer?.getData('application/json');
    if (data) {
        try {
            const parsed = JSON.parse(data);
            if (parsed.type === 'note' && parsed.noteId !== props.note.id) {
                justDropped.value = true;
                emit('drop', props.note, event);
                // Reset after a short delay to prevent click from firing
                setTimeout(() => {
                    justDropped.value = false;
                }, 100);
            }
        } catch (e) {
            console.error('Failed to parse drop data:', e);
        }
    }
};

const handleClick = () => {
    // Don't trigger view if we just dropped something
    if (!justDropped.value && !isDragging.value) {
        if (isLocked.value) {
            emit('unlock', props.note);
        } else {
            emit('view', props.note);
        }
    }
};
</script>

<template>
    <div
        :class="cn(
            'group relative aspect-square rounded-xl border-2 bg-card transition-all duration-200',
            'hover:shadow-md',
            !note.color && 'border-border/60 hover:border-border',
            isDragging && 'opacity-50 scale-95 rotate-1',
            showDropPreview && 'scale-[1.02]',
            draggable && 'cursor-grab active:cursor-grabbing',
            props.class
        )"
        :style="cardStyles"
        :draggable="draggable"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
        @click="handleClick"
    >
        <!-- Floating Pin Indicator -->
        <div
            v-if="note.is_pinned && !showDropPreview"
            class="absolute -top-2 -right-2 z-20 w-7 h-7 rounded-full bg-primary flex items-center justify-center shadow-md border-2 border-background"
            @click.stop="emit('togglePin', note)"
            title="Unpin"
        >
            <Pin class="h-3.5 w-3.5 text-primary-foreground fill-current" />
        </div>
        <!-- Locked State for Encrypted Notes -->
        <div
            v-if="isLocked && !showDropPreview"
            class="flex flex-col h-full p-4 relative"
        >
            <!-- Blurred background effect -->
            <div class="absolute inset-0 rounded-xl overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-purple-500/5" />
                <div class="absolute inset-0 backdrop-blur-[2px]" />
            </div>

            <!-- Lock overlay -->
            <div class="relative z-10 flex flex-col h-full items-center justify-center text-center">
                <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3">
                    <Lock class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <h3 class="font-semibold text-sm text-foreground mb-1">{{ note.title }}</h3>
                <p class="text-[10px] text-muted-foreground mt-1">Click to unlock</p>
            </div>

            <!-- Status icons in corner -->
            <div v-if="note.is_favorited" class="absolute top-3 left-3 flex items-center gap-1.5">
                <Star
                    class="h-4 w-4 fill-amber-400 text-amber-400"
                />
            </div>

            <!-- Actions Menu -->
            <div class="absolute top-2 right-2" @click.stop>
                <Dropdown align="right">
                    <template #trigger>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <MoreHorizontal class="h-4 w-4" />
                        </Button>
                    </template>

                    <template #default="{ close }">
                        <DropdownItem @click="emit('unlock', note); close()">
                            <Lock class="mr-2 h-4 w-4" />
                            Unlock
                        </DropdownItem>
                        <DropdownItem @click="emit('togglePin', note); close()">
                            <Pin class="mr-2 h-4 w-4" />
                            {{ note.is_pinned ? 'Unpin' : 'Pin' }}
                        </DropdownItem>
                        <DropdownItem @click="emit('toggleFavorite', note); close()">
                            <Star class="mr-2 h-4 w-4" />
                            {{ note.is_favorited ? 'Unfavorite' : 'Favorite' }}
                        </DropdownItem>
                        <DropdownItem @click="emit('archive', note); close()">
                            <Archive class="mr-2 h-4 w-4" />
                            {{ note.is_archived ? 'Unarchive' : 'Archive' }}
                        </DropdownItem>
                        <DropdownItem destructive @click="emit('delete', note); close()">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Delete
                        </DropdownItem>
                    </template>
                </Dropdown>
            </div>

            <!-- Tags for encrypted notes -->
            <div v-if="note.tags && note.tags.length" class="absolute bottom-10 left-4 right-4 flex flex-wrap gap-1 justify-center">
                <span
                    v-for="tag in note.tags.slice(0, 2)"
                    :key="tag.id"
                    class="inline-flex items-center px-1.5 py-0.5 rounded border border-primary/50 bg-primary/10 text-[10px] font-medium text-primary"
                >
                    {{ tag.name }}
                </span>
                <span
                    v-if="note.tags.length > 2"
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] text-muted-foreground"
                >
                    +{{ note.tags.length - 2 }}
                </span>
            </div>

            <!-- Footer -->
            <div class="absolute bottom-3 left-4 right-4 flex items-center justify-between text-[10px] text-muted-foreground pt-2 border-t border-border/30">
                <span>{{ formattedDate }}</span>
            </div>
        </div>

        <!-- Normal Card Content (for non-encrypted notes) -->
        <div
            v-else-if="!showDropPreview"
            class="flex flex-col h-full p-4"
        >
            <!-- Header with icons and menu -->
            <div class="flex items-start justify-between gap-2 mb-2">
                <!-- Status Icons -->
                <div class="flex items-center gap-1.5">
                    <Star
                        v-if="note.is_favorited"
                        class="h-4 w-4 fill-amber-400 text-amber-400"
                    />
                </div>

                <!-- Quick Actions -->
                <div class="flex items-center gap-0.5 -mr-1 -mt-1">
                    <!-- Pin Button (only for unpinned notes, shown on hover) -->
                    <Button
                        v-if="!note.is_pinned"
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 transition-opacity opacity-0 group-hover:opacity-100"
                        @click.stop="emit('togglePin', note)"
                        title="Pin"
                    >
                        <Pin class="h-4 w-4" />
                    </Button>

                    <!-- Actions Menu -->
                    <div @click.stop>
                        <Dropdown align="right">
                            <template #trigger>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <MoreHorizontal class="h-4 w-4" />
                                </Button>
                            </template>

                            <template #default="{ close }">
                                <DropdownItem @click="emit('edit', note); close()">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Edit
                                </DropdownItem>
                                <DropdownItem @click="emit('share', note); close()">
                                    <Share2 class="mr-2 h-4 w-4" />
                                    Share
                                </DropdownItem>
                                <DropdownItem @click="emit('toggleFavorite', note); close()">
                                    <Star class="mr-2 h-4 w-4" />
                                    {{ note.is_favorited ? 'Unfavorite' : 'Favorite' }}
                                </DropdownItem>
                                <DropdownItem @click="emit('archive', note); close()">
                                    <Archive class="mr-2 h-4 w-4" />
                                    {{ note.is_archived ? 'Unarchive' : 'Archive' }}
                                </DropdownItem>
                                <DropdownItem destructive @click="emit('delete', note); close()">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Delete
                                </DropdownItem>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>

            <!-- Title -->
            <h3 class="font-semibold text-sm leading-tight line-clamp-2 text-foreground">
                {{ note.title }}
            </h3>

            <!-- Excerpt -->
            <p
                v-if="note.excerpt && !compact"
                class="mt-2 text-xs leading-relaxed line-clamp-4 text-muted-foreground flex-1"
            >
                {{ note.excerpt }}
            </p>

            <!-- Spacer -->
            <div class="flex-1" />

            <!-- Tags -->
            <div v-if="note.tags && note.tags.length" class="flex flex-wrap gap-1 mt-2">
                <span
                    v-for="tag in note.tags.slice(0, 2)"
                    :key="tag.id"
                    class="inline-flex items-center px-1.5 py-0.5 rounded border border-primary/50 bg-primary/10 text-[10px] font-medium text-primary"
                >
                    {{ tag.name }}
                </span>
                <span
                    v-if="note.tags.length > 2"
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] text-muted-foreground"
                >
                    +{{ note.tags.length - 2 }}
                </span>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-border/30 text-[10px] text-muted-foreground">
                <span>{{ formattedDate }}</span>
            </div>
        </div>

        <!-- Drop Preview: Side-by-side notes preview -->
        <div
            v-else
            class="absolute inset-0 rounded-xl bg-primary/5 ring-2 ring-primary ring-offset-2 p-3 flex gap-2"
        >
            <!-- Left mini-card (existing note) -->
            <div
                class="flex-1 rounded-lg border-2 bg-card p-2 flex flex-col overflow-hidden"
                :class="note.color ? '' : 'border-border/60'"
                :style="cardStyles"
            >
                <h4 class="font-medium text-[10px] leading-tight line-clamp-2 text-foreground mb-1">
                    {{ note.title }}
                </h4>
                <p class="text-[8px] text-muted-foreground line-clamp-3 flex-1">
                    {{ note.excerpt || 'No content' }}
                </p>
            </div>

            <!-- Right mini-card (dragging note) -->
            <div class="flex-1 rounded-lg border-2 border-dashed border-primary/50 bg-primary/5 p-2 flex flex-col overflow-hidden">
                <h4 class="font-medium text-[10px] leading-tight line-clamp-2 text-primary mb-1">
                    {{ draggingNote?.title || 'Note' }}
                </h4>
                <p class="text-[8px] text-primary/60 flex-1">
                    Drop to create group
                </p>
            </div>
        </div>
    </div>
</template>
